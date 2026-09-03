<?php

declare(strict_types=1);

$adminModePath = dirname(__DIR__) . '/env-config/admin-mode.php';
$adminMode = is_file($adminModePath) ? require $adminModePath : false;

if ($adminMode !== true) {
    http_response_code(404);
    exit;
}

require __DIR__ . '/src/bootstrap.php';

try {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');

    echo "NEROZON Dispatcher init\n";
    echo "Database: " . dispatcher_bootstrap_config()['db_name'] . "\n";

    foreach (dispatcher_initialize_database() as $line) {
        echo '- ' . $line . "\n";
    }

    echo "Init successful\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Dispatcher init failed: " . $e->getMessage() . "\n";
    exit(1);
}
