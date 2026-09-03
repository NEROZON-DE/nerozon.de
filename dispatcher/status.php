<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

dispatcher_json([
    'ok' => true,
    'service' => 'nerozon-dispatcher',
    'status' => dispatcher_safe_config(),
    'queue' => dispatcher_counts(),
    'time' => dispatcher_now(),
]);
