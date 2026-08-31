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
    $result = dispatcher_initialize_database();
    echo "NEROZON Dispatcher init successful\n";
    foreach ($result as $line) {
        echo '- ' . $line . "\n";
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo "Dispatcher init failed: " . $e->getMessage() . "\n";
    exit(1);
}
