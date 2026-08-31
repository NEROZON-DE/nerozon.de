<?php

declare(strict_types=1);

const DISPATCHER_VERSION = '0.1.0-dev2';

function dispatcher_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $defaults = [
        'admin_user' => getenv('DISPATCHER_ADMIN_USER') ?: 'rainer',
        'admin_password_hash' => getenv('DISPATCHER_ADMIN_PASSWORD_HASH') ?: '',
        'ingest_token' => getenv('DISPATCHER_INGEST_TOKEN') ?: '',
        'cron_token' => getenv('DISPATCHER_CRON_TOKEN') ?: '',
        'openai_api_key' => getenv('OPENAI_API_KEY') ?: '',
        'openai_base_url' => getenv('OPENAI_BASE_URL') ?: 'https://api.openai.com/v1',
        'default_provider' => getenv('DISPATCHER_DEFAULT_PROVIDER') ?: 'openai',
        'default_model' => getenv('DISPATCHER_DEFAULT_MODEL') ?: 'gpt-5.6-luna',
        'data_dir' => getenv('DISPATCHER_DATA_DIR') ?: dirname(__DIR__, 2) . '/../dispatcher-data',
        'max_jobs_per_cron' => (int)(getenv('DISPATCHER_MAX_JOBS_PER_CRON') ?: 5),
        'max_retries' => (int)(getenv('DISPATCHER_MAX_RETRIES') ?: 2),
    ];

    $external = dirname(__DIR__, 3) . '/config/dispatcher/config.php';

    if (is_file($external)) {
        $loaded = require $external;
        if (is_array($loaded)) {
            $defaults = array_replace($defaults, $loaded);
        }
    }

    $config = $defaults;
    return $config;
}

function dispatcher_json(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function dispatcher_data_path(string $part = ''): string
{
    $base = rtrim((string) dispatcher_config()['data_dir'], '/');
    return $part === '' ? $base : $base . '/' . ltrim($part, '/');
}

function dispatcher_ensure_storage(): void
{
    foreach (['queue', 'processing', 'done', 'failed', 'logs'] as $dir) {
        $path = dispatcher_data_path($dir);
        if (!is_dir($path)) {
            mkdir($path, 0750, true);
        }
    }
}

function dispatcher_uuid(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}

function dispatcher_now(): string
{
    return gmdate('Y-m-d\TH:i:s\Z');
}

function dispatcher_write_json(string $path, array $data): void
{
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) {
        throw new RuntimeException('JSON encoding failed.');
    }
    file_put_contents($path, $json . "\n", LOCK_EX);
}

function dispatcher_read_json(string $path): array
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException('Could not read file.');
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new RuntimeException('Invalid JSON file.');
    }
    return $data;
}

function dispatcher_require_bearer(string $expected): void
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $match)) {
        dispatcher_json(['ok' => false, 'error' => 'unauthorized'], 401);
    }

    if ($expected === '' || !hash_equals($expected, trim($match[1]))) {
        dispatcher_json(['ok' => false, 'error' => 'unauthorized'], 401);
    }
}

function dispatcher_counts(): array
{
    dispatcher_ensure_storage();
    $counts = [];
    foreach (['queue', 'processing', 'done', 'failed'] as $dir) {
        $files = glob(dispatcher_data_path($dir) . '/*.json') ?: [];
        $counts[$dir] = count($files);
    }
    return $counts;
}

function dispatcher_tail_log(int $limit = 20): array
{
    $path = dispatcher_data_path('logs/dispatcher.log');
    if (!is_file($path)) {
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    return array_slice($lines, -$limit);
}

function dispatcher_log(string $level, string $message, array $context = []): void
{
    dispatcher_ensure_storage();
    $entry = [
        'ts' => dispatcher_now(),
        'level' => $level,
        'message' => $message,
        'context' => $context,
    ];
    file_put_contents(
        dispatcher_data_path('logs/dispatcher.log'),
        json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
        FILE_APPEND | LOCK_EX
    );
}

function dispatcher_safe_config(): array
{
    $cfg = dispatcher_config();
    return [
        'version' => DISPATCHER_VERSION,
        'default_provider' => $cfg['default_provider'],
        'default_model' => $cfg['default_model'],
        'data_dir_configured' => $cfg['data_dir'] !== '',
        'openai_configured' => $cfg['openai_api_key'] !== '',
        'ingest_configured' => $cfg['ingest_token'] !== '',
        'cron_configured' => $cfg['cron_token'] !== '',
        'admin_configured' => $cfg['admin_password_hash'] !== '',
        'max_jobs_per_cron' => $cfg['max_jobs_per_cron'],
        'max_retries' => $cfg['max_retries'],
    ];
}

function dispatcher_openai_request(array $job): array
{
    $cfg = dispatcher_config();
    $payload = $job['payload'] ?? [];
    $input = $payload['input'] ?? $payload['prompt'] ?? '';

    if (!is_string($input) || trim($input) === '') {
        throw new RuntimeException('Missing payload.input.');
    }

    $body = [
        'model' => $payload['model'] ?? $cfg['default_model'],
        'input' => $input,
    ];

    if (isset($payload['metadata']) && is_array($payload['metadata'])) {
        $body['metadata'] = $payload['metadata'];
    }

    $ch = curl_init(rtrim((string) $cfg['openai_base_url'], '/') . '/responses');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $cfg['openai_api_key'],
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT => 60,
    ]);

    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $raw === '') {
        throw new RuntimeException('OpenAI request failed: ' . $error);
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('OpenAI returned invalid JSON.');
    }

    if ($status < 200 || $status >= 300) {
        throw new RuntimeException('OpenAI HTTP ' . $status . ': ' . substr($raw, 0, 500));
    }

    return [
        'provider' => 'openai',
        'status' => $status,
        'response' => $decoded,
    ];
}
