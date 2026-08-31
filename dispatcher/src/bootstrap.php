<?php

declare(strict_types=1);

const DISPATCHER_VERSION = '0.2.0-dev2';

function dispatcher_bootstrap_config(): array
{
    static $config = null;
    if ($config !== null) return $config;

    $path = dirname(__DIR__, 3) . '/config/dispatcher/config.php';
    if (!is_file($path)) {
        throw new RuntimeException('Missing /config/dispatcher/config.php');
    }
    $loaded = require $path;
    if (!is_array($loaded)) {
        throw new RuntimeException('Invalid dispatcher bootstrap config.');
    }
    $config = $loaded;
    return $config;
}

function dispatcher_pdo(bool $withoutDatabase = false): PDO
{
    static $pdo = null;
    if (!$withoutDatabase && $pdo instanceof PDO) return $pdo;

    $cfg = dispatcher_bootstrap_config();
    $host = (string)($cfg['db_host'] ?? '');
    $port = (int)($cfg['db_port'] ?? 3306);
    $name = (string)($cfg['db_name'] ?? '');
    $user = (string)($cfg['db_user'] ?? '');
    $pass = (string)($cfg['db_password'] ?? '');
    if ($host === '' || $user === '' || (!$withoutDatabase && $name === '')) {
        throw new RuntimeException('Incomplete database bootstrap configuration.');
    }

    $dsn = 'mysql:host=' . $host . ';port=' . $port . ';charset=utf8mb4';
    if (!$withoutDatabase) $dsn .= ';dbname=' . $name;

    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    if (!$withoutDatabase) $pdo = $conn;
    return $conn;
}

function dispatcher_initialize_database(): array
{
    $cfg = dispatcher_bootstrap_config();
    $db = (string)($cfg['db_name'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_]+$/', $db)) {
        throw new RuntimeException('Invalid db_name.');
    }

    $notes = [];
    if (($cfg['create_database_if_possible'] ?? false) === true) {
        $admin = dispatcher_pdo(true);
        $admin->exec('CREATE DATABASE IF NOT EXISTS `' . $db . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $notes[] = 'database ensured';
    }

    $pdo = dispatcher_pdo();
    $ddl = [
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    foreach ($ddl as $sql) $pdo->exec($sql);
    $notes[] = 'tables ensured';

    $defaults = [
        'admin_user' => ['admin', 0],
        'admin_password_hash' => ['', 1],
        'ingest_token' => [bin2hex(random_bytes(32)), 1],
        'cron_token' => [bin2hex(random_bytes(32)), 1],
        'openai_api_key' => ['', 1],
        'openai_base_url' => ['https://api.openai.com/v1', 0],
        'default_provider' => ['openai', 0],
        'default_model' => ['gpt-5.6-luna', 0],
        'max_jobs_per_cron' => ['5', 0],
        'max_retries' => ['2', 0],
        'retry_delay_seconds' => ['60', 0],
        'cron_enabled' => ['1', 0],
    ];
    $stmt = $pdo->prepare('INSERT IGNORE INTO dispatcher_settings (setting_key, setting_value, is_secret) VALUES (?, ?, ?)');
    foreach ($defaults as $key => [$value, $secret]) $stmt->execute([$key, $value, $secret]);
    $notes[] = 'missing settings seeded';
    return $notes;
}

function dispatcher_settings(): array
{
    static $settings = null;
    if ($settings !== null) return $settings;
    $rows = dispatcher_pdo()->query('SELECT setting_key, setting_value FROM dispatcher_settings')->fetchAll();
    $settings = [];
    foreach ($rows as $row) $settings[$row['setting_key']] = (string)($row['setting_value'] ?? '');
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

function dispatcher_now(): string { return gmdate('Y-m-d\\TH:i:s\\Z'); }

function dispatcher_require_bearer(string $expected): void
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\\s+(.+)$/i', $header, $m) || $expected === '' || !hash_equals($expected, trim($m[1]))) {
        dispatcher_json(['ok' => false, 'error' => 'unauthorized'], 401);
    }
}

function dispatcher_counts(): array
{
    $rows = dispatcher_pdo()->query("SELECT status, COUNT(*) c FROM dispatcher_jobs GROUP BY status")->fetchAll();
    $counts = ['queued'=>0,'processing'=>0,'done'=>0,'failed'=>0];
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
    return [
        'version' => DISPATCHER_VERSION,
        'database' => true,
        'default_provider' => dispatcher_setting('default_provider'),
        'default_model' => dispatcher_setting('default_model'),
        'openai_configured' => dispatcher_setting('openai_api_key') !== '',
        'ingest_configured' => dispatcher_setting('ingest_token') !== '',
        'cron_configured' => dispatcher_setting('cron_token') !== '',
        'admin_configured' => dispatcher_setting('admin_password_hash') !== '',
        'cron_enabled' => dispatcher_setting('cron_enabled', '1') === '1',
        'max_jobs_per_cron' => (int)dispatcher_setting('max_jobs_per_cron', '5'),
        'max_retries' => (int)dispatcher_setting('max_retries', '2'),
    ];
}

function dispatcher_openai_request(array $job): array
{
    $payload = json_decode((string)$job['payload_json'], true);
    if (!is_array($payload)) throw new RuntimeException('Invalid payload JSON.');
    $input = $payload['input'] ?? $payload['prompt'] ?? '';
    if (!is_string($input) || trim($input) === '') throw new RuntimeException('Missing payload.input.');

    $body = ['model' => $payload['model'] ?? dispatcher_setting('default_model'), 'input' => $input];
    if (isset($payload['metadata']) && is_array($payload['metadata'])) $body['metadata'] = $payload['metadata'];

    $key = (string)dispatcher_setting('openai_api_key');
    if ($key === '') throw new RuntimeException('OpenAI API key is not configured.');
    $url = rtrim((string)dispatcher_setting('openai_base_url', 'https://api.openai.com/v1'), '/') . '/responses';

    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$key,'Content-Type: application/json'], CURLOPT_POSTFIELDS=>json_encode($body), CURLOPT_TIMEOUT=>60]);
    $raw = curl_exec($ch); $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); $error = curl_error($ch); curl_close($ch);
    if ($raw === false || $raw === '') throw new RuntimeException('OpenAI request failed: '.$error);
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) throw new RuntimeException('OpenAI returned invalid JSON.');
    if ($status < 200 || $status >= 300) throw new RuntimeException('OpenAI HTTP '.$status.': '.substr($raw,0,500));
    return ['provider'=>'openai','status'=>$status,'response'=>$decoded];
}
