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
        'schemaVersion' => 2,
        'updatedAt' => null,
        'modules' => [
            [
                'id' => 'ui',
                'name' => 'UI',
                'description' => 'Interaktion mit dem HUMAN.',
                'status' => 'active',
                'components' => [
                    ['id' => 'ui-actions', 'name' => 'Actions', 'description' => 'Erfasst Benutzeraktionen.'],
                ],
                'interfaces' => [
                    ['id' => 'ui-command-out', 'name' => 'Command', 'direction' => 'out', 'description' => 'Uebergibt Benutzeraktionen intern an den ADAPTER.'],
                ],
            ],
            [
                'id' => 'adapter',
                'name' => 'ADAPTER',
                'description' => 'Grenze zwischen externem Aufruf/UI und internen Gateway-Modulen.',
                'status' => 'active',
                'components' => [
                    ['id' => 'adapter-normalizer', 'name' => 'Normalizer', 'description' => 'Normalisiert Requests auf interne Commands.'],
                ],
                'interfaces' => [
                    ['id' => 'adapter-command-in', 'name' => 'Command In', 'direction' => 'in', 'description' => 'Nimmt interne UI-Kommandos an.'],
                    ['id' => 'adapter-deploy-out', 'name' => 'Deploy Command', 'direction' => 'out', 'description' => 'Uebergibt validierte Deploy-Auftraege an DEPLOY.'],
                ],
            ],
            [
                'id' => 'deploy',
                'name' => 'DEPLOY',
                'description' => 'Fuehrt Deployment-Aktionen aus.',
                'status' => 'planned',
                'components' => [
                    ['id' => 'deploy-runner', 'name' => 'Runner', 'description' => 'Fuehrt einen Deployment-Schritt aus.'],
                ],
                'interfaces' => [
                    ['id' => 'deploy-command-in', 'name' => 'Deploy Command', 'direction' => 'in', 'description' => 'Nimmt Deploy-Auftraege an.'],
                ],
            ],
        ],
        'connections' => [
            [
                'id' => 'conn-ui-adapter',
                'source' => ['moduleId' => 'ui', 'interfaceId' => 'ui-command-out'],
                'target' => ['moduleId' => 'adapter', 'interfaceId' => 'adapter-command-in'],
                'label' => 'command',
                'description' => '',
            ],
            [
                'id' => 'conn-adapter-deploy',
                'source' => ['moduleId' => 'adapter', 'interfaceId' => 'adapter-deploy-out'],
                'target' => ['moduleId' => 'deploy', 'interfaceId' => 'deploy-command-in'],
                'label' => 'deploy',
                'description' => '',
            ],
        ],
        'processes' => [
            [
                'id' => 'process-rc-deploy',
                'name' => 'RC Deployment',
                'purpose' => 'Ein HUMAN loest ueber die UI ein RC Deployment aus.',
                'preconditions' => ['RC ist vorbereitet.'],
                'expectedResult' => 'Der Deployment-Auftrag wurde an DEPLOY uebergeben.',
                'steps' => [
                    ['id' => 'step-human', 'name' => 'Deployment anfordern', 'actor' => 'HUMAN', 'moduleId' => null, 'interfaceId' => null, 'targetModuleId' => 'ui', 'targetInterfaceId' => null, 'description' => 'HUMAN startet den Vorgang.'],
                    ['id' => 'step-ui', 'name' => 'Aktion erfassen', 'actor' => 'MODULE', 'moduleId' => 'ui', 'interfaceId' => 'ui-command-out', 'targetModuleId' => 'adapter', 'targetInterfaceId' => 'adapter-command-in', 'description' => 'UI bildet die Benutzeraktion auf einen Command ab.'],
                    ['id' => 'step-adapter', 'name' => 'Deploy-Auftrag erzeugen', 'actor' => 'MODULE', 'moduleId' => 'adapter', 'interfaceId' => 'adapter-deploy-out', 'targetModuleId' => 'deploy', 'targetInterfaceId' => 'deploy-command-in', 'description' => 'ADAPTER validiert und transformiert den Request.'],
                    ['id' => 'step-deploy', 'name' => 'Deployment ausfuehren', 'actor' => 'MODULE', 'moduleId' => 'deploy', 'interfaceId' => null, 'targetModuleId' => null, 'targetInterfaceId' => null, 'description' => 'DEPLOY uebernimmt die technische Ausfuehrung.'],
                ],
            ],
        ],
        'roadmap' => [
            ['id' => 'road-ui', 'name' => 'UI PoC', 'start' => '2026-09-15', 'end' => '2026-09-22', 'status' => 'active', 'targetType' => 'module', 'targetId' => 'ui', 'group' => 'Gateway'],
            ['id' => 'road-adapter', 'name' => 'ADAPTER PoC', 'start' => '2026-09-18', 'end' => '2026-10-02', 'status' => 'active', 'targetType' => 'module', 'targetId' => 'adapter', 'group' => 'Gateway'],
            ['id' => 'road-deploy', 'name' => 'DEPLOY PoC', 'start' => '2026-09-25', 'end' => '2026-10-10', 'status' => 'planned', 'targetType' => 'module', 'targetId' => 'deploy', 'group' => 'Gateway'],
            ['id' => 'road-process', 'name' => 'RC Deployment End-to-End', 'start' => '2026-09-20', 'end' => '2026-10-12', 'status' => 'planned', 'targetType' => 'process', 'targetId' => 'process-rc-deploy', 'group' => 'Processes'],
        ],
        'views' => [
            'architecture' => [
                'positions' => [
                    'ui' => ['x' => 80, 'y' => 160],
                    'adapter' => ['x' => 430, 'y' => 160],
                    'deploy' => ['x' => 790, 'y' => 160],
                ],
            ],
            'processes' => [],
            'roadmap' => [],
        ],
    ];
}

function designer_normalize_document(array $data): array
{
    if (isset($data['modules']) && isset($data['processes']) && isset($data['connections'])) {
        $data['schemaVersion'] = 2;
        $data['views'] = isset($data['views']) && is_array($data['views']) ? $data['views'] : [];
        return $data;
    }

    // One-way compatibility migration from the initial PoC schema.
    if (isset($data['nodes']) && is_array($data['nodes'])) {
        $modules = [];
        $positions = [];
        foreach ($data['nodes'] as $node) {
            if (!is_array($node)) {
                continue;
            }
            $id = (string)($node['id'] ?? 'module-' . count($modules));
            $nodeData = is_array($node['data'] ?? null) ? $node['data'] : [];
            $modules[] = [
                'id' => $id,
                'name' => (string)($nodeData['label'] ?? $id),
                'description' => (string)($nodeData['description'] ?? ''),
                'status' => (string)($nodeData['status'] ?? 'planned'),
                'components' => [],
                'interfaces' => is_array($nodeData['interfaces'] ?? null) ? $nodeData['interfaces'] : [],
            ];
            if (is_array($node['position'] ?? null)) {
                $positions[$id] = $node['position'];
            }
        }

        $connections = [];
        foreach (($data['edges'] ?? []) as $edge) {
            if (!is_array($edge)) {
                continue;
            }
            $connections[] = [
                'id' => (string)($edge['id'] ?? 'conn-' . count($connections)),
                'source' => [
                    'moduleId' => (string)($edge['source'] ?? ''),
                    'interfaceId' => $edge['sourceHandle'] ?? null,
                ],
                'target' => [
                    'moduleId' => (string)($edge['target'] ?? ''),
                    'interfaceId' => $edge['targetHandle'] ?? null,
                ],
                'label' => (string)($edge['label'] ?? ''),
                'description' => '',
            ];
        }

        return [
            'version' => (int)($data['version'] ?? 0),
            'schemaVersion' => 2,
            'updatedAt' => $data['updatedAt'] ?? null,
            'modules' => $modules,
            'connections' => $connections,
            'processes' => [],
            'roadmap' => [],
            'views' => ['architecture' => ['positions' => $positions], 'processes' => [], 'roadmap' => []],
        ];
    }

    return $data;
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
    return designer_normalize_document($data);
}

function designer_validate_document(array $data): void
{
    if (!isset($data['version']) || !is_int($data['version']) || $data['version'] < 0) {
        throw new InvalidArgumentException('version must be a non-negative integer.');
    }
    foreach (['modules', 'connections', 'processes', 'roadmap', 'views'] as $key) {
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
