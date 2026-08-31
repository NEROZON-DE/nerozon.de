<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$bootstrap = dispatcher_bootstrap_config();
$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    $provided = (string)($_GET['key'] ?? '');
    $expected = (string)($bootstrap['init_key'] ?? '');
    if ($expected === '' || !hash_equals($expected, $provided)) {
        http_response_code(404);
        exit;
    }
}

try {
    $notes = [];

    if (($bootstrap['provision_database_access'] ?? false) === true) {
        $dbName = (string)($bootstrap['db_name'] ?? '');
        $dbUser = (string)($bootstrap['db_user'] ?? '');
        $dbPass = (string)($bootstrap['db_password'] ?? '');
        $dbHostGrant = (string)($bootstrap['db_user_host'] ?? '%');
        $provisionUser = (string)($bootstrap['provision_user'] ?? '');
        $provisionPass = (string)($bootstrap['provision_password'] ?? '');
        $host = (string)($bootstrap['db_host'] ?? '');
        $port = (int)($bootstrap['db_port'] ?? 3306);

        if (!preg_match('/^[A-Za-z0-9_]+$/', $dbName) || !preg_match('/^[A-Za-z0-9_.-]+$/', $dbUser) || !preg_match('/^[A-Za-z0-9_.%:-]+$/', $dbHostGrant)) {
            throw new RuntimeException('Invalid database provisioning identifiers.');
        }
        if ($provisionUser === '' || $host === '') {
            throw new RuntimeException('Provisioning enabled but provisioning credentials are incomplete.');
        }

        $admin = new PDO(
            'mysql:host=' . $host . ';port=' . $port . ';charset=utf8mb4',
            $provisionUser,
            $provisionPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
        );
        $admin->exec('CREATE DATABASE IF NOT EXISTS `' . $dbName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $account = "'" . str_replace("'", "''", $dbUser) . "'@'" . str_replace("'", "''", $dbHostGrant) . "'";
        $admin->exec('CREATE USER IF NOT EXISTS ' . $account . ' IDENTIFIED BY ' . $admin->quote($dbPass));
        $admin->exec('GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX ON `' . $dbName . '`.* TO ' . $account);
        $notes[] = 'database and application access ensured';
    }

    foreach (dispatcher_initialize_database() as $line) $notes[] = $line;

    echo "NEROZON Dispatcher init successful\n";
    foreach ($notes as $line) echo '- ' . $line . "\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Dispatcher init failed: " . $e->getMessage() . "\n";
    exit(1);
}
