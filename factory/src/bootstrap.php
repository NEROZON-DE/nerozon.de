<?php

declare(strict_types=1);

function factory_bootstrap_state(): array
{
    $configPath = dirname(__DIR__, 2) . '/env-config/database.php';
    if (!is_file($configPath) || !is_readable($configPath)) {
        throw new RuntimeException('Bootstrap configuration unavailable.');
    }

    $config = require $configPath;
    if (!is_array($config)) {
        throw new RuntimeException('Bootstrap configuration invalid.');
    }

    $environment = strtoupper(trim((string)($config['environment'] ?? '')));
    if (!in_array($environment, ['DEV1', 'DEV2', 'DEV3', 'PREP', 'PROD'], true)) {
        throw new RuntimeException('Bootstrap environment invalid.');
    }

    $revisionPath = dirname(__DIR__) . '/.revision';
    $revision = is_readable($revisionPath) ? trim((string)file_get_contents($revisionPath)) : '';
    if (!preg_match('/^[a-f0-9]{40}$/', $revision)) {
        throw new RuntimeException('Deployment revision unavailable.');
    }

    return ['environment' => $environment, 'revision' => $revision];
}
