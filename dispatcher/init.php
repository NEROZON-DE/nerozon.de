<?php

declare(strict_types=1);

// IONOS may expose the shell PHP binary as CGI/FastCGI rather than CLI.
// A real web request has REQUEST_METHOD set; a shell invocation does not.
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== '') {
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
    $message = "Dispatcher init failed: " . $e->getMessage() . "\n";
    if (defined('STDERR')) {
        fwrite(STDERR, $message);
    } else {
        echo $message;
    }
    exit(1);
}
