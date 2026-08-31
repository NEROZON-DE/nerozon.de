<?php

declare(strict_types=1);

// IONOS may execute shell PHP through CGI/FastCGI. Require an explicit
// command-line marker instead of relying on PHP_SAPI or REQUEST_METHOD.
$argv = $_SERVER['argv'] ?? [];
if (!is_array($argv) || !in_array('--init', $argv, true)) {
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== '') {
        http_response_code(404);
    }
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
    $message = "Dispatcher init failed: " . $e->getMessage() . "\n";
    if (defined('STDERR')) {
        fwrite(STDERR, $message);
    } else {
        echo $message;
    }
    exit(1);
}
