<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function dispatcher_worker_tools(): array
{
    return [
        [
            'type' => 'function',
            'name' => 'github_read_file',
            'description' => 'Read a UTF-8 text file from an allowed NEROZON GitHub repository at a specific ref.',
            'strict' => true,
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'repository' => ['type' => 'string'],
                    'path' => ['type' => 'string'],
                    'ref' => ['type' => 'string'],
                ],
                'required' => ['repository', 'path', 'ref'],
                'additionalProperties' => false,
            ],
        ],
        [
            'type' => 'function',
            'name' => 'github_list_path',
            'description' => 'List a directory in an allowed NEROZON GitHub repository at a specific ref.',
            'strict' => true,
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'repository' => ['type' => 'string'],
                    'path' => ['type' => 'string'],
                    'ref' => ['type' => 'string'],
                ],
                'required' => ['repository', 'path', 'ref'],
                'additionalProperties' => false,
            ],
        ],
        [
            'type' => 'function',
            'name' => 'github_write_file',
            'description' => 'Create or update a UTF-8 text file in an allowed NEROZON GitHub repository. For updates provide the current blob SHA as expected_sha; for creates use an empty string.',
            'strict' => true,
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'repository' => ['type' => 'string'],
                    'path' => ['type' => 'string'],
                    'ref' => ['type' => 'string'],
                    'content' => ['type' => 'string'],
                    'message' => ['type' => 'string'],
                    'expected_sha' => ['type' => 'string'],
                ],
                'required' => ['repository', 'path', 'ref', 'content', 'message', 'expected_sha'],
                'additionalProperties' => false,
            ],
        ],
        [
            'type' => 'function',
            'name' => 'github_move_file',
            'description' => 'Move a file inside an allowed NEROZON GitHub repository and branch. Use this for Work Order lifecycle transitions.',
            'strict' => true,
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'repository' => ['type' => 'string'],
                    'source_path' => ['type' => 'string'],
                    'destination_path' => ['type' => 'string'],
                    'ref' => ['type' => 'string'],
                    'message' => ['type' => 'string'],
                    'expected_sha' => ['type' => 'string'],
                ],
                'required' => ['repository', 'source_path', 'destination_path', 'ref', 'message', 'expected_sha'],
                'additionalProperties' => false,
            ],
        ],
        [
            'type' => 'function',
            'name' => 'report_workorder_status',
            'description' => 'Report a Work Order lifecycle status to the Dispatcher after the corresponding Git transition has been committed and pushed. Only in_progress and closed are valid.',
            'strict' => true,
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'wo_id' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['in_progress', 'closed']],
                    'commit' => ['type' => 'string'],
                ],
                'required' => ['wo_id', 'status', 'commit'],
                'additionalProperties' => false,
            ],
        ],
    ];
}

function dispatcher_openai_get_response(string $responseId): array
{
    $key = (string)dispatcher_setting('openai_api_key');
    if ($key === '') throw new RuntimeException('OpenAI API key is not configured.');
    $url = rtrim((string)dispatcher_setting('openai_base_url', 'https://api.openai.com/v1'), '/') . '/responses/' . rawurlencode($responseId);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key, 'Content-Type: application/json'],
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($raw === false || $raw === '') throw new RuntimeException('OpenAI response retrieval failed: ' . $error);
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) throw new RuntimeException('OpenAI returned invalid JSON.');
    if ($status < 200 || $status >= 300) throw new RuntimeException('OpenAI HTTP ' . $status . ': ' . substr($raw, 0, 500));
    return $decoded;
}

function dispatcher_github_adapter_runtime(): array
{
    $root = dirname(__DIR__, 2);
    $secretFile = $root . '/._secrets/github.php';
    if (!is_file($secretFile) || !is_readable($secretFile)) throw new RuntimeException('GitHub adapter secrets are unavailable to Dispatcher.');
    $secrets = require $secretFile;
    if (!is_array($secrets)) throw new RuntimeException('Invalid GitHub adapter secrets.');
    $token = trim((string)($secrets['adapter_token'] ?? ''));
    if ($token === '') throw new RuntimeException('GitHub adapter token is not configured.');

    $dispatcherBase = dispatcher_runtime_base_url();
    if ($dispatcherBase === '') throw new RuntimeException('Dispatcher runtime URL is unavailable.');
    $parts = parse_url($dispatcherBase);
    $host = (string)($parts['host'] ?? '');
    if ($host === '' || !str_starts_with($host, 'dispatcher')) throw new RuntimeException('Cannot derive GitHub adapter host from Dispatcher host.');
    $githubHost = 'github' . substr($host, strlen('dispatcher'));
    $scheme = (string)($parts['scheme'] ?? 'https');
    return ['url' => $scheme . '://' . $githubHost . '/', 'token' => $token];
}

function dispatcher_call_github_adapter(array $payload, string $woId, string $toolName): array
{
    $runtime = dispatcher_github_adapter_runtime();
    $started = microtime(true);
    $ch = curl_init($runtime['url']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $runtime['token'], 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 35,
    ]);
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    $durationMs = (int)round((microtime(true) - $started) * 1000);

    if ($raw === false || $raw === '') {
        dispatcher_log('error', 'GitHub adapter call failed', ['wo' => $woId, 'tool' => $toolName, 'error' => $error, 'duration_ms' => $durationMs], 'github');
        throw new RuntimeException('GitHub adapter request failed: ' . $error);
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) throw new RuntimeException('GitHub adapter returned invalid JSON.');
    dispatcher_log(($status >= 200 && $status < 300 && ($decoded['ok'] ?? false)) ? 'info' : 'error', 'GitHub adapter tool call', [
        'wo' => $woId,
        'tool' => $toolName,
        'operation' => $payload['operation'] ?? null,
        'repository' => $payload['repository'] ?? null,
        'ref' => $payload['ref'] ?? null,
        'path' => $payload['path'] ?? ($payload['source_path'] ?? null),
        'http_status' => $status,
        'ok' => $decoded['ok'] ?? false,
        'duration_ms' => $durationMs,
    ], 'github');
    if ($status < 200 || $status >= 300 || !($decoded['ok'] ?? false)) throw new RuntimeException('GitHub adapter tool failed: ' . json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    return $decoded;
}

function dispatcher_report_workorder_status(array $args): array
{
    $woId = trim((string)($args['wo_id'] ?? ''));
    $status = strtolower(trim((string)($args['status'] ?? '')));
    $commit = strtolower(trim((string)($args['commit'] ?? '')));
    if (!preg_match('/^WO-[0-9]{14}(?:-[A-Za-z0-9]+)?$/', $woId)) throw new RuntimeException('Invalid Work Order id.');
    if (!in_array($status, ['in_progress', 'closed'], true)) throw new RuntimeException('Invalid Work Order status.');
    if ($commit !== '' && !preg_match('/^[0-9a-f]{40}$/', $commit)) throw new RuntimeException('Invalid commit SHA.');

    $pdo = dispatcher_pdo();
    $stmt = $pdo->prepare('SELECT * FROM dispatcher_workorders WHERE wo_id=?');
    $stmt->execute([$woId]);
    $workorder = $stmt->fetch();
    if (!$workorder) throw new RuntimeException('Unknown Work Order.');
    if ((string)$workorder['openai_response_id'] === '') throw new RuntimeException('Work Order is not assigned to a worker.');

    $current = (string)$workorder['status'];
    $allowed = ['in_progress' => ['running', 'in_progress'], 'closed' => ['in_progress', 'closed']];
    if (!in_array($current, $allowed[$status], true)) throw new RuntimeException('Invalid Work Order status transition from ' . $current . ' to ' . $status . '.');
    if ($current === $status) return ['ok' => true, 'wo' => $woId, 'status' => $status, 'idempotent' => true];

    $path = 'workorders/' . $status . '/' . basename((string)$workorder['wo_path']);
    if ($commit !== '') {
        $update = $pdo->prepare('UPDATE dispatcher_workorders SET status=?, wo_path=?, commit_sha=?, error_text=NULL WHERE wo_id=?');
        $update->execute([$status, $path, $commit, $woId]);
    } else {
        $update = $pdo->prepare('UPDATE dispatcher_workorders SET status=?, wo_path=?, error_text=NULL WHERE wo_id=?');
        $update->execute([$status, $path, $woId]);
    }
    dispatcher_log('info', 'Work Order status reported by worker tool', ['wo' => $woId, 'from' => $current, 'to' => $status, 'path' => $path, 'commit' => $commit !== '' ? $commit : null], 'worker');
    return ['ok' => true, 'wo' => $woId, 'status' => $status];
}

function dispatcher_execute_worker_tool(string $name, array $args, array $workorder): array
{
    $woId = (string)$workorder['wo_id'];
    return match ($name) {
        'github_read_file' => dispatcher_call_github_adapter(['operation' => 'read_file', 'repository' => (string)($args['repository'] ?? ''), 'path' => (string)($args['path'] ?? ''), 'ref' => (string)($args['ref'] ?? 'main')], $woId, $name),
        'github_list_path' => dispatcher_call_github_adapter(['operation' => 'list_path', 'repository' => (string)($args['repository'] ?? ''), 'path' => (string)($args['path'] ?? ''), 'ref' => (string)($args['ref'] ?? 'main')], $woId, $name),
        'github_write_file' => dispatcher_call_github_adapter(['operation' => 'write_file', 'repository' => (string)($args['repository'] ?? ''), 'path' => (string)($args['path'] ?? ''), 'ref' => (string)($args['ref'] ?? ''), 'content' => (string)($args['content'] ?? ''), 'message' => (string)($args['message'] ?? ''), 'expected_sha' => (string)($args['expected_sha'] ?? '')], $woId, $name),
        'github_move_file' => dispatcher_call_github_adapter(['operation' => 'move_file', 'repository' => (string)($args['repository'] ?? ''), 'source_path' => (string)($args['source_path'] ?? ''), 'destination_path' => (string)($args['destination_path'] ?? ''), 'ref' => (string)($args['ref'] ?? ''), 'message' => (string)($args['message'] ?? ''), 'expected_sha' => (string)($args['expected_sha'] ?? '')], $woId, $name),
        'report_workorder_status' => dispatcher_report_workorder_status($args),
        default => throw new RuntimeException('Unsupported worker tool: ' . $name),
    };
}

function dispatcher_continue_worker(array $workorder, array $response): array
{
    $responseId = trim((string)($response['id'] ?? ''));
    if ($responseId === '') throw new RuntimeException('Current OpenAI response id is missing.');
    $toolOutputs = [];
    foreach (($response['output'] ?? []) as $item) {
        if (!is_array($item) || ($item['type'] ?? '') !== 'function_call') continue;
        $callId = trim((string)($item['call_id'] ?? ''));
        $name = trim((string)($item['name'] ?? ''));
        $args = json_decode((string)($item['arguments'] ?? '{}'), true);
        if ($callId === '' || $name === '' || !is_array($args)) throw new RuntimeException('Invalid function call returned by OpenAI.');
        try {
            $output = dispatcher_execute_worker_tool($name, $args, $workorder);
        } catch (Throwable $e) {
            $output = ['ok' => false, 'error' => $e->getMessage()];
            dispatcher_log('error', 'Worker tool execution failed', ['wo' => (string)$workorder['wo_id'], 'tool' => $name, 'error' => $e->getMessage()], 'worker');
        }
        $toolOutputs[] = ['type' => 'function_call_output', 'call_id' => $callId, 'output' => json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
    }

    $model = trim((string)dispatcher_setting('default_model'));
    if ($model === '') throw new RuntimeException('No default model configured.');
    $body = [
        'model' => $model,
        'previous_response_id' => $responseId,
        'tools' => dispatcher_worker_tools(),
        'background' => true,
        'store' => true,
        'metadata' => ['nerozen_type' => 'worker.execute', 'wo' => (string)$workorder['wo_id'], 'target' => (string)$workorder['target'], 'branch' => (string)$workorder['branch_name']],
    ];
    $body['input'] = $toolOutputs !== []
        ? $toolOutputs
        : 'Continue executing the current Work Order. Use the provided tools to load the required Authority and project sources, perform the work within your Authority, maintain the Work Order lifecycle in Git, and report in_progress and closed only after the corresponding Git transitions.';

    $result = dispatcher_openai_http($body);
    $next = $result['response'];
    $nextId = trim((string)($next['id'] ?? ''));
    if ($nextId === '') throw new RuntimeException('OpenAI continuation response has no id.');
    return ['response_id' => $nextId, 'response_status' => (string)($next['status'] ?? 'unknown'), 'tool_calls_processed' => count($toolOutputs)];
}
