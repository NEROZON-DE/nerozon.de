<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/src/bootstrap.php';

try {
    echo "NEROZON Dispatcher init\n";
    echo "Database: " . dispatcher_bootstrap_config()['db_name'] . "\n";

    foreach (dispatcher_initialize_database() as $line) {
        echo '- ' . $line . "\n";
    }

    echo "Init successful\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Dispatcher init failed: " . $e->getMessage() . "\n");
    exit(1);
}
