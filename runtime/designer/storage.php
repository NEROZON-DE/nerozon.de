<?php
declare(strict_types=1);

const DESIGNER_HISTORY_LIMIT = 10;
const DESIGNER_MAX_BYTES = 2000000;

function designer_paths(): array
{
    $base = __DIR__ . '/data';
    return [
        'base' => $base,
        'document' => $base . '/designer.json',
        'history' => $base . '/history',
        'lock' => $base . '/designer.lock',
    ];
}

function designer_ensure_storage(): array
{
    $paths = designer_paths();
    foreach ([$paths['base'], $paths['history']] as $dir) {
        if (!is_dir($dir) && !@mkdir($dir, 0770, true) && !is_dir($dir)) {
            throw new RuntimeException('Designer storage is not writable: ' . $dir);
        }
    }
    return $paths;
}

function designer_default_document(): array
{
    return [
        'version' => 0,
        'updatedAt' => null,
        'nodes' => [
            [
                'id' => 'controller',
                'type' => 'module',
                'position' => ['x' => 100, 'y' => 150],
                'data' => [
                    'label' => 'Controller',
                    'description' => 'Orchestriert Worker und Laufzeit.',
                    'status' => 'active',
                    'phase' => 'Foundation',
                    'interfaces' => [
                        ['id' => 'tasks-out', 'name' => 'Tasks', 'direction' => 'out', 'protocol' => 'HTTP'],
                        ['id' => 'state-in', 'name' => 'State', 'direction' => 'in', 'protocol' => 'JSON'],
                    ],
                ],
            ],
            [
                'id' => 'worker',
                'type' => 'module',
                'position' => ['x' => 520, 'y' => 170],
                'data' => [
                    'label' => 'Worker',
                    'description' => 'Fuehrt spezialisierte Aufgaben aus.',
                    'status' => 'planned',
                    'phase' => 'PoC',
                    'interfaces' => [
                        ['id' => 'tasks-in', 'name' => 'Tasks', 'direction' => 'in', 'protocol' => 'HTTP'],
                        ['id' => 'result-out', 'name' => 'Result', 'direction' => 'out', 'protocol' => 'JSON'],
                    ],
                ],
            ],
        ],
        'edges' => [
            [
                'id' => 'edge-controller-worker',
                'source' => 'controller',
                'sourceHandle' => 'tasks-out',
                'target' => 'worker',
                'targetHandle' => 'tasks-in',
                'label' => 'dispatch',
            ],
        ],
        'roadmap' => [
            ['id' => 'foundation', 'name' => 'Foundation', 'order' => 10],
            ['id' => 'poc', 'name' => 'PoC', 'order' => 20],
            ['id' => 'production', 'name' => 'Production', 'order' => 30],
        ],
    ];
}

function designer_read_document(string $path): array
{
    if (!is_file($path)) {
        return designer_default_document();
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException('Could not read designer document.');
    }
    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data)) {
        throw new RuntimeException('Designer document is invalid.');
    }
    return $data;
}

function designer_validate_document(array $data): void
{
    if (!isset($data['version']) || !is_int($data['version']) || $data['version'] < 0) {
        throw new InvalidArgumentException('version must be a non-negative integer.');
    }
    foreach (['nodes', 'edges', 'roadmap'] as $key) {
        if (!isset($data[$key]) || !is_array($data[$key])) {
            throw new InvalidArgumentException($key . ' must be an array.');
        }
    }
}

function designer_json(array $data): string
{
    return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
}

function designer_atomic_write(string $path, string $contents): void
{
    $tmp = tempnam(dirname($path), '.designer-');
    if ($tmp === false) {
        throw new RuntimeException('Could not create temporary file.');
    }
    try {
        if (file_put_contents($tmp, $contents) !== strlen($contents)) {
            throw new RuntimeException('Could not write complete designer document.');
        }
        @chmod($tmp, 0660);
        if (!rename($tmp, $path)) {
            throw new RuntimeException('Could not replace designer document atomically.');
        }
    } finally {
        if (is_file($tmp)) {
            @unlink($tmp);
        }
    }
}

function designer_snapshot_current(array $paths, array $current): void
{
    if (!is_file($paths['document'])) {
        return;
    }
    $stamp = gmdate('Ymd\\THis') . 'Z';
    $name = sprintf('%s/%s-v%06d-%s.json', $paths['history'], $stamp, (int)($current['version'] ?? 0), bin2hex(random_bytes(3)));
    designer_atomic_write($name, designer_json($current));
    designer_prune_history($paths['history']);
}

function designer_prune_history(string $historyDir): void
{
    $files = glob($historyDir . '/*.json') ?: [];
    usort($files, static fn(string $a, string $b): int => strcmp(basename($b), basename($a)));
    foreach (array_slice($files, DESIGNER_HISTORY_LIMIT) as $oldFile) {
        if (!@unlink($oldFile)) {
            throw new RuntimeException('Could not delete old history file: ' . basename($oldFile));
        }
    }
    if (count(glob($historyDir . '/*.json') ?: []) > DESIGNER_HISTORY_LIMIT) {
        throw new RuntimeException('History pruning failed.');
    }
}

function designer_with_lock(callable $callback): mixed
{
    $paths = designer_ensure_storage();
    $handle = fopen($paths['lock'], 'c+');
    if ($handle === false) {
        throw new RuntimeException('Could not open designer lock file.');
    }
    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Could not acquire designer lock.');
        }
        return $callback($paths);
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
