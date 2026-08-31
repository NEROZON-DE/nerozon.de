<?php

declare(strict_types=1);

const DISPATCHER_VERSION = '0.4.2';

function dispatcher_bootstrap_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $path = dirname(__DIR__, 2) . '/env-config/database.php';
    if (!is_file($path) || !is_readable($path)) {
        throw new RuntimeException('Missing or unreadable /env-config/database.php');
    }

    $loaded = require $path;
    if (!is_array($loaded)) {
        throw new RuntimeException('Invalid /env-config/database.php: expected array.');
    }

    $config = [
        'environment' => strtoupper(trim((string)($loaded['environment'] ?? 'UNKNOWN'))),
        'db_host' => (string)($loaded['host'] ?? ''),
        'db_port' => (int)($loaded['port'] ?? 3306),
        'db_name' => (string)($loaded['database'] ?? ''),
        'db_user' => (string)($loaded['username'] ?? ''),
        'db_password' => (string)($loaded['password'] ?? ''),
        'db_charset' => (string)($loaded['charset'] ?? 'utf8mb4'),
    ];

    if ($config['db_host'] === '' || $config['db_name'] === '' || $config['db_user'] === '') {
        throw new RuntimeException('Incomplete database environment configuration.');
    }
    if (!preg_match('/^[A-Za-z0-9_]+$/', $config['db_name'])) {
        throw new RuntimeException('Invalid database name in environment configuration.');
    }
    if (!preg_match('/^[A-Za-z0-9_]+$/', $config['db_charset'])) {
        throw new RuntimeException('Invalid database charset in environment configuration.');
    }

    return $config;
}

function dispatcher_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = dispatcher_bootstrap_config();
    $dsn = 'mysql:host=' . $cfg['db_host']
        . ';port=' . $cfg['db_port']
        . ';dbname=' . $cfg['db_name']
        . ';charset=' . $cfg['db_charset'];

    $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function dispatcher_initialize_database(): array
{
    $pdo = dispatcher_pdo();
    $notes = [];

    $schema = [
        "CREATE TABLE IF NOT EXISTS dispatcher_settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value LONGTEXT NULL,
            is_secret TINYINT(1) NOT NULL DEFAULT 0,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS dispatcher_jobs (
            id CHAR(36) PRIMARY KEY,
            source VARCHAR(100) NOT NULL,
            job_type VARCHAR(100) NOT NULL,
            provider VARCHAR(50) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'queued',
            payload_json LONGTEXT NOT NULL,
            result_json LONGTEXT NULL,
            error_text TEXT NULL,
            attempts INT NOT NULL DEFAULT 0,
            available_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            locked_at TIMESTAMP NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_jobs_pick (status, available_at, created_at),
            INDEX idx_jobs_source (source, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS dispatcher_workorders (
            wo_id VARCHAR(40) PRIMARY KEY,
            target VARCHAR(40) NOT NULL,
            repository VARCHAR(200) NOT NULL,
            branch_name VARCHAR(200) NOT NULL,
            wo_path VARCHAR(500) NOT NULL,
            commit_sha CHAR(40) NOT NULL,
            authority_repository VARCHAR(200) NOT NULL,
            authority_branch VARCHAR(200) NOT NULL DEFAULT 'main',
            authority_path VARCHAR(500) NOT NULL DEFAULT 'ROLE.md',
            status VARCHAR(30) NOT NULL DEFAULT 'registered',
            openai_response_id VARCHAR(100) NULL,
            openai_status VARCHAR(30) NULL,
            error_text TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_workorders_status (status, updated_at),
            INDEX idx_workorders_branch (repository, branch_name, updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS dispatcher_cron_runs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            finished_at TIMESTAMP NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'running',
            processed_count INT NOT NULL DEFAULT 0,
            failed_count INT NOT NULL DEFAULT 0,
            queued_remaining INT NOT NULL DEFAULT 0,
            message TEXT NULL,
            INDEX idx_cron_started (started_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS dispatcher_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            level VARCHAR(20) NOT NULL,
            component VARCHAR(50) NOT NULL,
            message VARCHAR(500) NOT NULL,
            context_json LONGTEXT NULL,
            INDEX idx_log_created (created_at),
            INDEX idx_log_level (level, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    foreach ($schema as $sql) {
        $pdo->exec($sql);
    }
    $notes[] = 'dispatcher tables ensured';

    $initialPassword = bin2hex(random_bytes(10));
    $initialIngest = bin2hex(random_bytes(32));
    $initialCron = bin2hex(random_bytes(32));
    $initialWorkerTrigger = bin2hex(random_bytes(32));

    $defaults = [
        'admin_user' => ['admin', 0],
        'admin_password_hash' => [password_hash($initialPassword, PASSWORD_DEFAULT), 1],
        'ingest_token' => [$initialIngest, 1],
        'cron_token' => [$initialCron, 1],
        'worker_trigger_token' => [$initialWorkerTrigger, 1],
        'openai_api_key' => ['', 1],
        'openai_base_url' => ['https://api.openai.com/v1', 0],
        'default_provider' => ['openai', 0],
        'default_model' => ['', 0],
        'max_jobs_per_cron' => ['5', 0],
        'max_retries' => ['2', 0],
        'retry_delay_seconds' => ['60', 0],
        'cron_enabled' => ['1', 0],
    ];

    $stmt = $pdo->prepare('INSERT IGNORE INTO dispatcher_settings (setting_key, setting_value, is_secret) VALUES (?, ?, ?)');
    foreach ($defaults as $key => [$value, $secret]) {
        $stmt->execute([$key, $value, $secret]);
        if ($stmt->rowCount() === 1) {
            if ($key === 'admin_password_hash') $notes[] = 'INITIAL admin password: ' . $initialPassword;
            if ($key === 'ingest_token') $notes[] = 'INITIAL ingest token: ' . $initialIngest;
            if ($key === 'cron_token') $notes[] = 'INITIAL cron token: ' . $initialCron;
            if ($key === 'worker_trigger_token') $notes[] = 'INITIAL worker trigger token: ' . $initialWorkerTrigger;
        }
    }

    $notes[] = 'missing settings seeded; existing settings unchanged';
    return $notes;
}

function dispatcher_settings(): array
{
    static $settings = null;
    if ($settings !== null) return $settings;

    $rows = dispatcher_pdo()->query('SELECT setting_key, setting_value FROM dispatcher_settings')->fetchAll();
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = (string)($row['setting_value'] ?? '');
    }
    return $settings;
}

function dispatcher_setting(string $key, mixed $default = ''): mixed
{
    $all = dispatcher_settings();
    return array_key_exists($key, $all) ? $all[$key] : $default;
}

function dispatcher_save_setting(string $key, string $value): void
{
    $stmt = dispatcher_pdo()->prepare('UPDATE dispatcher_settings SET setting_value = ? WHERE setting_key = ?');
    $stmt->execute([$value, $key]);
}

function dispatcher_json(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function dispatcher_uuid(): string
{
    $b = random_bytes(16);
    $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
    $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}

function dispatcher_now(): string
{
    return gmdate('Y-m-d\\TH:i:s\\Z');
}

function dispatcher_authorization_header(): string
{
    foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $key) {
        $value = $_SERVER[$key] ?? '';
        if (is_string($value) && trim($value) !== '') return trim($value);
    }

    $headerSources = [];
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) $headerSources[] = $headers;
    }
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (is_array($headers)) $headerSources[] = $headers;
    }
    foreach ($headerSources as $headers) {
        foreach ($headers as $name => $value) {
            if (strcasecmp((string)$name, 'Authorization') === 0 && is_string($value)) return trim($value);
        }
    }
    return '';
}

function dispatcher_require_bearer(string $expected): void
{
    $header = dispatcher_authorization_header();
    if (!preg_match('/^Bearer\\s+(.+)$/i', $header, $m) || $expected === '' || !hash_equals($expected, trim($m[1]))) {
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
    if ($host === '') {
        return '';
    }

    $forwardedProto = strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    $https = strtolower(trim((string)($_SERVER['HTTPS'] ?? '')));
    $scheme = ($forwardedProto === 'https' || ($https !== '' && $https !== 'off')) ? 'https' : 'http';
    return $scheme . '://' . $host;
}

function dispatcher_worker_bootstrap_prompt(array $workorder): string
{
    $callbackBase = dispatcher_runtime_base_url();
    $statusUrl = $callbackBase !== '' ? $callbackBase . '/worker-status.php' : '/worker-status.php';

    return implode("\n", [
        'You are operating as the ' . $workorder['target'] . ' fundamental role.',
        '',
        'Load your Authority first:',
        $workorder['authority_repository'],
        $workorder['authority_branch'],
        '/' . ltrim($workorder['authority_path'], '/'),
        '',
        'Your current Work Order is:',
        $workorder['repository'],
        $workorder['branch_name'],
        '/' . ltrim($workorder['wo_path'], '/'),
        'commit: ' . $workorder['commit_sha'],
        '',
        'Load these sources and continue according to the loaded Authority, Work Order, CR, and other authoritative truths.',
        'Do not claim the Work Order until the information and Authority required to perform it are accessible.',
        '',
        'Runtime Work Order status callback:',
        'POST ' . $statusUrl,
        'Content-Type: application/json',
        'Report only after the corresponding Git lifecycle transition has been committed and pushed.',
        'When the Work Order has been moved from queued to in_progress, send:',
        '{"wo_id":"' . $workorder['wo_id'] . '","status":"in_progress","commit":"<git commit sha>"}',
        'When the Work Order has been moved from in_progress to closed, send:',
        '{"wo_id":"' . $workorder['wo_id'] . '","status":"closed","commit":"<git commit sha>"}',
        'The commit field may be omitted only if the current commit SHA is not available.',
    ]);
}

function dispatcher_start_worker(array $workorder): array
{
    $model = trim((string)dispatcher_setting('default_model'));
    if ($model === '') throw new RuntimeException('No default model configured.');

    $body = [
        'model' => $model,
        'input' => dispatcher_worker_bootstrap_prompt($workorder),
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

    return [
        'response_id' => $responseId,
        'response_status' => (string)($response['status'] ?? 'unknown'),
        'http_status' => $result['http_status'],
    ];
}
