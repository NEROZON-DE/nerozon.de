<?php

declare(strict_types=1);

const NEROZON_GITHUB_ADAPTER_VERSION = '0.1.0-prep';

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
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
    }

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach ($headers as $name => $value) {
                if (strcasecmp((string)$name, 'Authorization') === 0 && is_string($value)) {
                    return trim($value);
                }
            }
        }
    }

    return '';
}

function load_runtime_config(): array
{
    $root = dirname(__DIR__);
    $configFile = $root . '/env-config/github.php';
    $secretFile = $root . '/._secrets/github.php';

    if (!is_file($configFile) || !is_readable($configFile)) {
        throw new RuntimeException('GitHub adapter environment configuration is unavailable.');
    }
    if (!is_file($secretFile) || !is_readable($secretFile)) {
        throw new RuntimeException('GitHub adapter secrets are unavailable.');
    }

    $config = require $configFile;
    $secrets = require $secretFile;

    if (!is_array($config) || !is_array($secrets)) {
        throw new RuntimeException('Invalid GitHub adapter runtime configuration.');
    }

    $allowedRepositories = $config['allowed_repositories'] ?? [];
    if (!is_array($allowedRepositories) || $allowedRepositories === []) {
        throw new RuntimeException('No GitHub repositories are allowed.');
    }

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
    if ($path === '' || str_contains($path, '..')) {
        json_response(['ok' => false, 'error' => 'invalid_path'], 400);
    }
    return $path;
}

function require_ref(mixed $value): string
{
    $ref = trim((string)$value);
    if ($ref === '' || strlen($ref) > 200 || preg_match('/[\x00-\x1F\x7F]/', $ref)) {
        json_response(['ok' => false, 'error' => 'invalid_ref'], 400);
    }
    return $ref;
}

function github_request(array $runtime, string $url): array
{
    $headers = [
        'Accept: application/vnd.github+json',
        'X-GitHub-Api-Version: 2022-11-28',
        'User-Agent: NEROZON-GitHub-Adapter/' . NEROZON_GITHUB_ADAPTER_VERSION,
    ];

    if ($runtime['github_token'] !== '') {
        $headers[] = 'Authorization: Bearer ' . $runtime['github_token'];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    $body = curl_exec($ch);
    $curlError = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($body === false) {
        throw new RuntimeException('GitHub request failed: ' . $curlError);
    }

    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('GitHub returned invalid JSON.');
    }

    if ($status < 200 || $status >= 300) {
        $message = isset($decoded['message']) ? (string)$decoded['message'] : 'GitHub request failed.';
        json_response(['ok' => false, 'error' => 'github_error', 'github_status' => $status, 'message' => $message], $status === 404 ? 404 : 502);
    }

    return $decoded;
}

function read_file(array $runtime, string $repository, string $path, string $ref): never
{
    $url = $runtime['github_api_base'] . '/repos/' . rawurlencode(explode('/', $repository, 2)[0]) . '/' . rawurlencode(explode('/', $repository, 2)[1])
        . '/contents/' . implode('/', array_map('rawurlencode', explode('/', $path)))
        . '?ref=' . rawurlencode($ref);

    $item = github_request($runtime, $url);
    if (($item['type'] ?? '') !== 'file') {
        json_response(['ok' => false, 'error' => 'not_a_file'], 400);
    }

    $encoding = (string)($item['encoding'] ?? '');
    $content = (string)($item['content'] ?? '');
    if ($encoding !== 'base64') {
        json_response(['ok' => false, 'error' => 'unsupported_github_encoding'], 502);
    }

    $decoded = base64_decode(str_replace(["\r", "\n"], '', $content), true);
    if ($decoded === false) {
        json_response(['ok' => false, 'error' => 'invalid_github_content'], 502);
    }

    json_response([
        'ok' => true,
        'operation' => 'read_file',
        'repository' => $repository,
        'ref' => $ref,
        'path' => $path,
        'sha' => (string)($item['sha'] ?? ''),
        'size' => (int)($item['size'] ?? strlen($decoded)),
        'content' => $decoded,
    ]);
}

function list_path(array $runtime, string $repository, string $path, string $ref): never
{
    $url = $runtime['github_api_base'] . '/repos/' . rawurlencode(explode('/', $repository, 2)[0]) . '/' . rawurlencode(explode('/', $repository, 2)[1])
        . '/contents/' . implode('/', array_map('rawurlencode', explode('/', $path)))
        . '?ref=' . rawurlencode($ref);

    $items = github_request($runtime, $url);
    if (!array_is_list($items)) {
        json_response(['ok' => false, 'error' => 'not_a_directory'], 400);
    }

    $result = [];
    foreach ($items as $item) {
        if (!is_array($item)) continue;
        $result[] = [
            'name' => (string)($item['name'] ?? ''),
            'path' => (string)($item['path'] ?? ''),
            'type' => (string)($item['type'] ?? ''),
            'sha' => (string)($item['sha'] ?? ''),
            'size' => (int)($item['size'] ?? 0),
        ];
    }

    json_response([
        'ok' => true,
        'operation' => 'list_path',
        'repository' => $repository,
        'ref' => $ref,
        'path' => $path,
        'items' => $result,
    ]);
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
        json_response([
            'ok' => true,
            'service' => 'NEROZON GitHub Adapter',
            'version' => NEROZON_GITHUB_ADAPTER_VERSION,
            'mode' => 'read-only',
        ]);
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
    }

    $runtime = load_runtime_config();
    require_adapter_auth($runtime);

    $raw = file_get_contents('php://input');
    $request = json_decode($raw === false ? '' : $raw, true);
    if (!is_array($request)) {
        json_response(['ok' => false, 'error' => 'invalid_json'], 400);
    }

    $operation = trim((string)($request['operation'] ?? ''));
    $repository = require_repository($runtime, $request['repository'] ?? '');
    $path = require_repo_path($request['path'] ?? '');
    $ref = require_ref($request['ref'] ?? 'main');

    if ($operation === 'read_file') {
        read_file($runtime, $repository, $path, $ref);
    }
    if ($operation === 'list_path') {
        list_path($runtime, $repository, $path, $ref);
    }

    json_response(['ok' => false, 'error' => 'unsupported_operation'], 400);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => 'adapter_unavailable',
        'message' => $e->getMessage(),
    ], 503);
}
