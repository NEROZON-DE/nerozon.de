<?php

declare(strict_types=1);

const NEROZON_GITHUB_ADAPTER_VERSION = '0.2.1-prep';

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function authorization_header(): string
{
    foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $key) {
        $value = $_SERVER[$key] ?? '';
        if (is_string($value) && trim($value) !== '') return trim($value);
    }
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach ($headers as $name => $value) {
                if (strcasecmp((string)$name, 'Authorization') === 0 && is_string($value)) return trim($value);
            }
        }
    }
    return '';
}

function load_runtime_config(): array
{
    $root = dirname(__DIR__);
    $configFile = $root . '/env-config/github.php';
    $secretFile = $root . '/env-config/github-token.php';
    if (!is_file($configFile) || !is_readable($configFile)) throw new RuntimeException('GitHub adapter environment configuration is unavailable.');
    if (!is_file($secretFile) || !is_readable($secretFile)) throw new RuntimeException('GitHub adapter token configuration is unavailable.');

    $config = require $configFile;
    $secrets = require $secretFile;
    if (!is_array($config) || !is_array($secrets)) throw new RuntimeException('Invalid GitHub adapter runtime configuration.');

    $allowedRepositories = $config['allowed_repositories'] ?? [];
    if (!is_array($allowedRepositories) || $allowedRepositories === []) throw new RuntimeException('No GitHub repositories are allowed.');

    return [
        'github_api_base' => rtrim((string)($config['github_api_base'] ?? 'https://api.github.com'), '/'),
        'allowed_repositories' => array_values(array_map('strval', $allowedRepositories)),
        'adapter_token' => (string)($secrets['adapter_token'] ?? ''),
        'github_token' => (string)($secrets['github_token'] ?? ''),
    ];
}

function require_adapter_auth(array $runtime): void
{
    $expected = $runtime['adapter_token'];
    $header = authorization_header();
    if ($expected === '' || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches) || !hash_equals($expected, trim($matches[1]))) {
        json_response(['ok' => false, 'error' => 'unauthorized'], 401);
    }
}

function require_repository(array $runtime, mixed $value): string
{
    $repository = trim((string)$value);
    if ($repository === '' || !in_array($repository, $runtime['allowed_repositories'], true)) {
        json_response(['ok' => false, 'error' => 'repository_not_allowed'], 403);
    }
    return $repository;
}

function require_repo_path(mixed $value): string
{
    $path = trim((string)$value, "/ \t\n\r\0\x0B");
    if ($path === '' || str_contains($path, '..')) json_response(['ok' => false, 'error' => 'invalid_path'], 400);
    return $path;
}

function require_ref(mixed $value): string
{
    $ref = trim((string)$value);
    if ($ref === '' || strlen($ref) > 200 || preg_match('/[\x00-\x1F\x7F]/', $ref)) json_response(['ok' => false, 'error' => 'invalid_ref'], 400);
    return $ref;
}

function github_request(array $runtime, string $url, string $method = 'GET', ?array $payload = null): array
{
    $headers = [
        'Accept: application/vnd.github+json',
        'X-GitHub-Api-Version: 2022-11-28',
        'User-Agent: NEROZON-GitHub-Adapter/' . NEROZON_GITHUB_ADAPTER_VERSION,
    ];
    if ($runtime['github_token'] !== '') $headers[] = 'Authorization: Bearer ' . $runtime['github_token'];
    if ($payload !== null) $headers[] = 'Content-Type: application/json';

    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CUSTOMREQUEST => $method,
    ];
    if ($payload !== null) $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    curl_setopt_array($ch, $options);

    $body = curl_exec($ch);
    $curlError = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($body === false) throw new RuntimeException('GitHub request failed: ' . $curlError);

    $decoded = $body === '' ? [] : json_decode($body, true);
    if (!is_array($decoded)) throw new RuntimeException('GitHub returned invalid JSON.');
    if ($status < 200 || $status >= 300) {
        $message = isset($decoded['message']) ? (string)$decoded['message'] : 'GitHub request failed.';
        json_response(['ok' => false, 'error' => 'github_error', 'github_status' => $status, 'message' => $message], $status === 404 ? 404 : 502);
    }
    return $decoded;
}

function github_contents_url(array $runtime, string $repository, string $path): string
{
    [$owner, $repo] = explode('/', $repository, 2);
    return $runtime['github_api_base'] . '/repos/' . rawurlencode($owner) . '/' . rawurlencode($repo)
        . '/contents/' . implode('/', array_map('rawurlencode', explode('/', $path)));
}

function read_file(array $runtime, string $repository, string $path, string $ref): never
{
    $item = github_request($runtime, github_contents_url($runtime, $repository, $path) . '?ref=' . rawurlencode($ref));
    if (($item['type'] ?? '') !== 'file') json_response(['ok' => false, 'error' => 'not_a_file'], 400);
    if ((string)($item['encoding'] ?? '') !== 'base64') json_response(['ok' => false, 'error' => 'unsupported_github_encoding'], 502);
    $decoded = base64_decode(str_replace(["\r", "\n"], '', (string)($item['content'] ?? '')), true);
    if ($decoded === false) json_response(['ok' => false, 'error' => 'invalid_github_content'], 502);
    json_response(['ok' => true, 'operation' => 'read_file', 'repository' => $repository, 'ref' => $ref, 'path' => $path, 'sha' => (string)($item['sha'] ?? ''), 'size' => (int)($item['size'] ?? strlen($decoded)), 'content' => $decoded]);
}

function list_path(array $runtime, string $repository, string $path, string $ref): never
{
    $items = github_request($runtime, github_contents_url($runtime, $repository, $path) . '?ref=' . rawurlencode($ref));
    if (!array_is_list($items)) json_response(['ok' => false, 'error' => 'not_a_directory'], 400);
    $result = [];
    foreach ($items as $item) {
        if (!is_array($item)) continue;
        $result[] = ['name' => (string)($item['name'] ?? ''), 'path' => (string)($item['path'] ?? ''), 'type' => (string)($item['type'] ?? ''), 'sha' => (string)($item['sha'] ?? ''), 'size' => (int)($item['size'] ?? 0)];
    }
    json_response(['ok' => true, 'operation' => 'list_path', 'repository' => $repository, 'ref' => $ref, 'path' => $path, 'items' => $result]);
}

function write_file(array $runtime, string $repository, string $path, string $branch, string $content, string $message, string $expectedSha): never
{
    if ($runtime['github_token'] === '') json_response(['ok' => false, 'error' => 'github_write_token_unavailable'], 503);
    if ($message === '') json_response(['ok' => false, 'error' => 'missing_commit_message'], 400);
    $payload = ['message' => $message, 'content' => base64_encode($content), 'branch' => $branch];
    if ($expectedSha !== '') $payload['sha'] = $expectedSha;
    $result = github_request($runtime, github_contents_url($runtime, $repository, $path), 'PUT', $payload);
    json_response([
        'ok' => true,
        'operation' => 'write_file',
        'repository' => $repository,
        'ref' => $branch,
        'path' => $path,
        'sha' => (string)($result['content']['sha'] ?? ''),
        'commit' => (string)($result['commit']['sha'] ?? ''),
    ]);
}

function move_file(array $runtime, string $repository, string $sourcePath, string $destinationPath, string $branch, string $message, string $expectedSha): never
{
    if ($runtime['github_token'] === '') json_response(['ok' => false, 'error' => 'github_write_token_unavailable'], 503);
    if ($message === '') json_response(['ok' => false, 'error' => 'missing_commit_message'], 400);

    $source = github_request($runtime, github_contents_url($runtime, $repository, $sourcePath) . '?ref=' . rawurlencode($branch));
    if (($source['type'] ?? '') !== 'file') json_response(['ok' => false, 'error' => 'source_not_a_file'], 400);
    $sourceSha = (string)($source['sha'] ?? '');
    if ($expectedSha !== '' && !hash_equals($expectedSha, $sourceSha)) json_response(['ok' => false, 'error' => 'source_sha_mismatch'], 409);
    if ((string)($source['encoding'] ?? '') !== 'base64') json_response(['ok' => false, 'error' => 'unsupported_github_encoding'], 502);

    $create = github_request($runtime, github_contents_url($runtime, $repository, $destinationPath), 'PUT', [
        'message' => $message . ' (create destination)',
        'content' => str_replace(["\r", "\n"], '', (string)($source['content'] ?? '')),
        'branch' => $branch,
    ]);
    $createCommit = (string)($create['commit']['sha'] ?? '');

    $delete = github_request($runtime, github_contents_url($runtime, $repository, $sourcePath), 'DELETE', [
        'message' => $message . ' (remove source)',
        'sha' => $sourceSha,
        'branch' => $branch,
    ]);
    $deleteCommit = (string)($delete['commit']['sha'] ?? '');

    json_response([
        'ok' => true,
        'operation' => 'move_file',
        'repository' => $repository,
        'ref' => $branch,
        'source_path' => $sourcePath,
        'destination_path' => $destinationPath,
        'create_commit' => $createCommit,
        'commit' => $deleteCommit !== '' ? $deleteCommit : $createCommit,
    ]);
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
        if (($_GET['check'] ?? '') === '1') {
            $runtime = load_runtime_config();
            json_response([
                'ok' => true,
                'service' => 'NEROZON GitHub Adapter',
                'version' => NEROZON_GITHUB_ADAPTER_VERSION,
                'runtime_configured' => true,
                'adapter_token_configured' => $runtime['adapter_token'] !== '',
                'github_token_configured' => $runtime['github_token'] !== '',
                'allowed_repositories' => $runtime['allowed_repositories'],
            ]);
        }
        json_response(['ok' => true, 'service' => 'NEROZON GitHub Adapter', 'version' => NEROZON_GITHUB_ADAPTER_VERSION, 'mode' => 'read-write']);
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);

    $runtime = load_runtime_config();
    require_adapter_auth($runtime);
    $raw = file_get_contents('php://input');
    $request = json_decode($raw === false ? '' : $raw, true);
    if (!is_array($request)) json_response(['ok' => false, 'error' => 'invalid_json'], 400);

    $operation = trim((string)($request['operation'] ?? ''));
    $repository = require_repository($runtime, $request['repository'] ?? '');
    $ref = require_ref($request['ref'] ?? 'main');

    if ($operation === 'read_file') read_file($runtime, $repository, require_repo_path($request['path'] ?? ''), $ref);
    if ($operation === 'list_path') list_path($runtime, $repository, require_repo_path($request['path'] ?? ''), $ref);
    if ($operation === 'write_file') write_file($runtime, $repository, require_repo_path($request['path'] ?? ''), $ref, (string)($request['content'] ?? ''), trim((string)($request['message'] ?? '')), strtolower(trim((string)($request['expected_sha'] ?? ''))));
    if ($operation === 'move_file') move_file($runtime, $repository, require_repo_path($request['source_path'] ?? ''), require_repo_path($request['destination_path'] ?? ''), $ref, trim((string)($request['message'] ?? '')), strtolower(trim((string)($request['expected_sha'] ?? ''))));

    json_response(['ok' => false, 'error' => 'unsupported_operation'], 400);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => 'adapter_unavailable', 'message' => $e->getMessage()], 503);
}
