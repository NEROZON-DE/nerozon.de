<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$variant = defined('NEROZON_DIAGNOSTIC_VARIANT') ? NEROZON_DIAGNOSTIC_VARIANT : 'unknown';

function diagnostic_headers_from(string $functionName): array
{
    if (!function_exists($functionName)) {
        return ['available' => false, 'headers' => null];
    }

    $headers = $functionName();
    if (!is_array($headers)) {
        return ['available' => true, 'headers' => null];
    }

    ksort($headers, SORT_NATURAL | SORT_FLAG_CASE);
    return ['available' => true, 'headers' => $headers];
}

function diagnostic_server_subset(): array
{
    $result = [];
    foreach ($_SERVER as $key => $value) {
        if (!is_string($key)) {
            continue;
        }

        if (
            str_starts_with($key, 'HTTP_')
            || str_starts_with($key, 'REDIRECT_')
            || str_starts_with($key, 'CONTENT_')
            || in_array($key, [
                'AUTH_TYPE',
                'REMOTE_USER',
                'PHP_AUTH_USER',
                'PHP_AUTH_PW',
                'PHP_AUTH_DIGEST',
                'REQUEST_METHOD',
                'REQUEST_URI',
                'SERVER_PROTOCOL',
                'SERVER_SOFTWARE',
                'GATEWAY_INTERFACE',
                'SCRIPT_NAME',
                'SCRIPT_FILENAME',
            ], true)
        ) {
            $result[$key] = $value;
        }
    }

    ksort($result);
    return $result;
}

$rawBody = file_get_contents('php://input');
if (!is_string($rawBody)) {
    $rawBody = '';
}

$response = [
    'warning' => 'DEV2 diagnostic endpoint. Send dummy values only.',
    'variant' => $variant,
    'runtime' => [
        'php_sapi' => PHP_SAPI,
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? null,
        'gateway_interface' => $_SERVER['GATEWAY_INTERFACE'] ?? null,
    ],
    'request' => [
        'method' => $_SERVER['REQUEST_METHOD'] ?? null,
        'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? null,
        'uri' => $_SERVER['REQUEST_URI'] ?? null,
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? null,
        'content_length' => $_SERVER['CONTENT_LENGTH'] ?? null,
        'body_length' => strlen($rawBody),
        'body' => $rawBody,
    ],
    'direct_server_lookups' => [
        'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
        'REDIRECT_HTTP_AUTHORIZATION' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
        'HTTP_X_AUTHORIZATION' => $_SERVER['HTTP_X_AUTHORIZATION'] ?? null,
        'HTTP_X_NEROZON_PROBE' => $_SERVER['HTTP_X_NEROZON_PROBE'] ?? null,
        'HTTP_X_NEROZON_INGEST_TOKEN' => $_SERVER['HTTP_X_NEROZON_INGEST_TOKEN'] ?? null,
        'NEROZON_PROBE_SEEN' => $_SERVER['NEROZON_PROBE_SEEN'] ?? null,
        'REDIRECT_NEROZON_PROBE_SEEN' => $_SERVER['REDIRECT_NEROZON_PROBE_SEEN'] ?? null,
    ],
    'getenv_lookups' => [
        'HTTP_AUTHORIZATION' => getenv('HTTP_AUTHORIZATION') ?: null,
        'REDIRECT_HTTP_AUTHORIZATION' => getenv('REDIRECT_HTTP_AUTHORIZATION') ?: null,
        'HTTP_X_AUTHORIZATION' => getenv('HTTP_X_AUTHORIZATION') ?: null,
        'HTTP_X_NEROZON_PROBE' => getenv('HTTP_X_NEROZON_PROBE') ?: null,
        'HTTP_X_NEROZON_INGEST_TOKEN' => getenv('HTTP_X_NEROZON_INGEST_TOKEN') ?: null,
        'NEROZON_PROBE_SEEN' => getenv('NEROZON_PROBE_SEEN') ?: null,
        'REDIRECT_NEROZON_PROBE_SEEN' => getenv('REDIRECT_NEROZON_PROBE_SEEN') ?: null,
    ],
    'header_functions' => [
        'getallheaders' => diagnostic_headers_from('getallheaders'),
        'apache_request_headers' => diagnostic_headers_from('apache_request_headers'),
    ],
    'server_subset' => diagnostic_server_subset(),
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
