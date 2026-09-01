<?php

declare(strict_types=1);

const DISPATCHER_VERSION = '0.4.2';

function dispatcher_bootstrap_config(): array
{
    static $config;
    if ($config !== null) return $config;

    $root = dirname(__DIR__, 2);
    $file = $root . '/env-config/database.php';
    if (!is_file($file) || !is_readable($file)) throw new RuntimeException('Database configuration is unavailable.');
    $loaded = require $file;
    if (!is_array($loaded)) throw new RuntimeException('Invalid database configuration.');
    foreach (['host', 'database', 'username', 'password'] as $key) {
        if (!array_key_exists($key, $loaded)) throw new RuntimeException('Missing database configuration: ' . $key);
    }
    $loaded['environment'] = strtoupper(trim((string)($loaded['environment'] ?? 'UNKNOWN')));
    $config = $loaded;
    return $config;
}

function dispatcher_pdo(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    $c = dispatcher_bootstrap_config();
    $dsn = 'mysql:host=' . $c['host'] . ';dbname=' . $c['database'] . ';charset=utf8mb4';
    $pdo = new PDO($dsn, $c['username'], $c['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function dispatcher_setting(string $key, ?string $default = null): ?string
{
    $stmt = dispatcher_pdo()->prepare('SELECT setting_value FROM dispatcher_settings WHERE setting_key=?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? (string)$row['setting_value'] : $default;
}

function dispatcher_set_setting(string $key, string $value): void
{
    $stmt = dispatcher_pdo()->prepare('INSERT INTO dispatcher_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
    $stmt->execute([$key, $value]);
}

function dispatcher_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function dispatcher_authorization_header(): string
{
    foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $key) {
        if (!empty($_SERVER[$key])) return trim((string)$_SERVER[$key]);
    }
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            if (strcasecmp((string)$name, 'Authorization') === 0) return trim((string)$value);
        }
    }
    return '';
}

function dispatcher_require_bearer(string $expected): void
{
    $header = dispatcher_authorization_header();
    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m) || $expected === '' || !hash_equals($expected, trim($m[1]))) {
        dispatcher_json(['ok' => false, 'error' => 'unauthorized'], 401);
    }
}

function dispatcher_counts(): array
{
    $rows = dispatcher_pdo()->query('SELECT status, COUNT(*) c FROM dispatcher_jobs GROUP BY status')->fetchAll();
    $counts = ['queued' => 0, 'processing' => 0, 'done' => 0, 'failed' => 0];
    foreach ($rows as $row) $counts[$row['status']] = (int)$row['c'];
    return $counts;
}

function dispatcher_log(string $level, string $message, array $context = [], string $component = 'dispatcher'): void
{
    $stmt = dispatcher_pdo()->prepare('INSERT INTO dispatcher_log (level, component, message, context_json) VALUES (?, ?, ?, ?)');
    $stmt->execute([$level, $component, $message, json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
}

function dispatcher_tail_log(int $limit = 20): array
{
    $limit = max(1, min(200, $limit));
    $rows = dispatcher_pdo()->query('SELECT created_at, level, component, message, context_json FROM dispatcher_log ORDER BY id DESC LIMIT ' . $limit)->fetchAll();
    return array_reverse($rows);
}

function dispatcher_safe_config(): array
{
    $bootstrap = dispatcher_bootstrap_config();
    return [
        'version' => DISPATCHER_VERSION,
        'environment' => $bootstrap['environment'],
        'database' => true,
        'default_provider' => dispatcher_setting('default_provider'),
        'default_model' => dispatcher_setting('default_model'),
        'openai_configured' => dispatcher_setting('openai_api_key') !== '',
        'ingest_configured' => dispatcher_setting('ingest_token') !== '',
        'worker_trigger_configured' => dispatcher_setting('worker_trigger_token') !== '',
        'cron_configured' => dispatcher_setting('cron_token') !== '',
        'admin_configured' => dispatcher_setting('admin_password_hash') !== '',
        'cron_enabled' => dispatcher_setting('cron_enabled', '1') === '1',
        'max_jobs_per_cron' => (int)dispatcher_setting('max_jobs_per_cron', '5'),
        'max_retries' => (int)dispatcher_setting('max_retries', '2'),
    ];
}

function dispatcher_openai_http(array $body): array
{
    $key = (string)dispatcher_setting('openai_api_key');
    if ($key === '') throw new RuntimeException('OpenAI API key is not configured.');

    $url = rtrim((string)dispatcher_setting('openai_base_url', 'https://api.openai.com/v1'), '/') . '/responses';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 45,
    ]);

    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $raw === '') throw new RuntimeException('OpenAI request failed: ' . $error);
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) throw new RuntimeException('OpenAI returned invalid JSON.');
    if ($status < 200 || $status >= 300) throw new RuntimeException('OpenAI HTTP ' . $status . ': ' . substr($raw, 0, 500));
    return ['http_status' => $status, 'response' => $decoded];
}

function dispatcher_openai_request(array $job): array
{
    $payload = json_decode((string)$job['payload_json'], true);
    if (!is_array($payload)) throw new RuntimeException('Invalid payload JSON.');

    $input = $payload['input'] ?? $payload['prompt'] ?? '';
    if (!is_string($input) || trim($input) === '') throw new RuntimeException('Missing payload.input.');

    $model = $payload['model'] ?? dispatcher_setting('default_model');
    if (!is_string($model) || trim($model) === '') throw new RuntimeException('No default model configured.');

    $body = ['model' => $model, 'input' => $input];
    if (isset($payload['metadata']) && is_array($payload['metadata'])) $body['metadata'] = $payload['metadata'];

    $result = dispatcher_openai_http($body);
    return ['provider' => 'openai', 'status' => $result['http_status'], 'response' => $result['response']];
}

function dispatcher_runtime_base_url(): string
{
    $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') return '';
    $forwardedProto = strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    $https = strtolower(trim((string)($_SERVER['HTTPS'] ?? '')));
    $scheme = ($forwardedProto === 'https' || ($https !== '' && $https !== 'off')) ? 'https' : 'http';
    return $scheme . '://' . $host;
}

function dispatcher_worker_tools(): array
{
    return [
        ['type'=>'function','name'=>'github_read_file','description'=>'Read a UTF-8 text file from an allowed NEROZON GitHub repository at a specific ref.','strict'=>true,'parameters'=>['type'=>'object','properties'=>['repository'=>['type'=>'string'],'path'=>['type'=>'string'],'ref'=>['type'=>'string']],'required'=>['repository','path','ref'],'additionalProperties'=>false]],
        ['type'=>'function','name'=>'github_list_path','description'=>'List a directory in an allowed NEROZON GitHub repository at a specific ref.','strict'=>true,'parameters'=>['type'=>'object','properties'=>['repository'=>['type'=>'string'],'path'=>['type'=>'string'],'ref'=>['type'=>'string']],'required'=>['repository','path','ref'],'additionalProperties'=>false]],
        ['type'=>'function','name'=>'github_write_file','description'=>'Create or update a UTF-8 text file in an allowed NEROZON GitHub repository. For updates provide the current blob SHA as expected_sha; for creates use an empty string.','strict'=>true,'parameters'=>['type'=>'object','properties'=>['repository'=>['type'=>'string'],'path'=>['type'=>'string'],'ref'=>['type'=>'string'],'content'=>['type'=>'string'],'message'=>['type'=>'string'],'expected_sha'=>['type'=>'string']],'required'=>['repository','path','ref','content','message','expected_sha'],'additionalProperties'=>false]],
        ['type'=>'function','name'=>'github_move_file','description'=>'Move a file inside an allowed NEROZON GitHub repository and branch. Use this for Work Order lifecycle transitions.','strict'=>true,'parameters'=>['type'=>'object','properties'=>['repository'=>['type'=>'string'],'source_path'=>['type'=>'string'],'destination_path'=>['type'=>'string'],'ref'=>['type'=>'string'],'message'=>['type'=>'string'],'expected_sha'=>['type'=>'string']],'required'=>['repository','source_path','destination_path','ref','message','expected_sha'],'additionalProperties'=>false]],
        ['type'=>'function','name'=>'report_workorder_status','description'=>'Report a Work Order lifecycle status to the Dispatcher after the corresponding Git transition has been committed and pushed. Only in_progress and closed are valid.','strict'=>true,'parameters'=>['type'=>'object','properties'=>['wo_id'=>['type'=>'string'],'status'=>['type'=>'string','enum'=>['in_progress','closed']],'commit'=>['type'=>'string']],'required'=>['wo_id','status','commit'],'additionalProperties'=>false]],
    ];
}

function dispatcher_worker_bootstrap_prompt(array $workorder): string
{
    $woPath = ltrim((string)$workorder['wo_path'], '/');
    $inProgressPath = 'workorders/in_progress/' . basename($woPath);

    return implode("\n", [
        'You are operating as the ' . $workorder['target'] . ' fundamental role.',
        'GitHub and Work Order status tools are available in this response and MUST be used for repository access and lifecycle transitions.',
        '',
        'BOOTSTRAP PROTOCOL — execute in this order before doing implementation work:',
        '1. Read the Authority entry point using github_read_file:',
        '   repository: ' . $workorder['authority_repository'],
        '   ref: ' . $workorder['authority_branch'],
        '   path: ' . ltrim($workorder['authority_path'], '/'),
        '2. Follow the Authority mandatory entry points, including WORK-ORDERS.md, using github_read_file as required.',
        '3. Read the assigned Work Order using github_read_file:',
        '   repository: ' . $workorder['repository'],
        '   ref: ' . $workorder['branch_name'],
        '   path: ' . $woPath,
        '4. Load the CR and other authoritative sources required to determine that the Work Order can be performed.',
        '5. If the required Authority or task information cannot be loaded, STOP and do not claim the Work Order.',
        '6. If the Work Order can be performed, your FIRST repository write MUST be the Work Order claim: move it with github_move_file from:',
        '   ' . $woPath,
        '   to:',
        '   ' . $inProgressPath,
        '   on ref: ' . $workorder['branch_name'],
        '7. After the move succeeds, immediately call report_workorder_status with status in_progress and the Git commit SHA returned by the move.',
        '8. Only after steps 1-7 have succeeded may you make implementation writes.',
        '',
        'EXECUTION PROTOCOL:',
        '- Perform the Work Order within the loaded Authority and project truths.',
        '- Record material decisions/findings in CR History as required by WORK-ORDERS.md.',
        '- When complete, move the Work Order from in_progress to closed using github_move_file.',
        '- After that Git move succeeds, immediately call report_workorder_status with status closed and the returned Git commit SHA.',
        '- A lifecycle transition is not complete until both the Git move and its runtime status report have succeeded.',
        '- If a required lifecycle operation fails, report/retain the failure explicitly; do not pretend the Work Order completed.',
        '',
        'Assigned Work Order id: ' . $workorder['wo_id'],
        'Assigned Work Order commit at registration: ' . $workorder['commit_sha'],
    ]);
}

function dispatcher_start_worker(array $workorder): array
{
    $model = trim((string)dispatcher_setting('default_model'));
    if ($model === '') throw new RuntimeException('No default model configured.');

    $body = [
        'model' => $model,
        'input' => dispatcher_worker_bootstrap_prompt($workorder),
        'tools' => dispatcher_worker_tools(),
        'background' => true,
        'store' => true,
        'metadata' => [
            'nerozen_type' => 'worker.execute',
            'wo' => (string)$workorder['wo_id'],
            'target' => (string)$workorder['target'],
            'branch' => (string)$workorder['branch_name'],
        ],
    ];

    $result = dispatcher_openai_http($body);
    $response = $result['response'];
    $responseId = trim((string)($response['id'] ?? ''));
    if ($responseId === '') throw new RuntimeException('OpenAI background response has no id.');

    return ['response_id'=>$responseId,'response_status'=>(string)($response['status'] ?? 'unknown'),'http_status'=>$result['http_status']];
}
