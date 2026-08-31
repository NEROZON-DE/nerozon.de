<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    dispatcher_json(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$expectedToken = (string)dispatcher_setting('ingest_token');
$providedToken = '';

// Preferred internal header for environments where Authorization is altered by the web server.
$customHeader = $_SERVER['HTTP_X_NEROZON_INGEST_TOKEN'] ?? '';
if (is_string($customHeader) && trim($customHeader) !== '') {
    $providedToken = trim($customHeader);
}

if ($providedToken === '') {
    $headerSources = [];
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            $headerSources[] = $headers;
        }
    }
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (is_array($headers)) {
            $headerSources[] = $headers;
        }
    }

    foreach ($headerSources as $headers) {
        foreach ($headers as $name => $value) {
            if (strcasecmp((string)$name, 'X-Nerozon-Ingest-Token') === 0 && is_string($value)) {
                $providedToken = trim($value);
                break 2;
            }
        }
    }
}

// Standard Bearer auth remains supported as a fallback for compatible environments.
if ($providedToken === '') {
    $authorizationHeader = dispatcher_authorization_header();
    if (preg_match('/^Bearer\\s+(.+)$/i', $authorizationHeader, $bearerMatch)) {
        $providedToken = trim($bearerMatch[1]);
    }
}

if ($expectedToken === '' || $providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    dispatcher_json(['ok' => false, 'error' => 'unauthorized'], 401);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
if (!is_array($data)) {
    dispatcher_json(['ok' => false, 'error' => 'invalid_json'], 400);
}

$source = trim((string)($data['source'] ?? 'api'));
$type = trim((string)($data['type'] ?? 'llm.request'));
$provider = trim((string)($data['provider'] ?? dispatcher_setting('default_provider', 'openai')));
$payload = $data['payload'] ?? null;
if ($source === '' || $type === '' || $provider === '' || !is_array($payload)) {
    dispatcher_json(['ok' => false, 'error' => 'invalid_request'], 400);
}

$id = dispatcher_uuid();
$stmt = dispatcher_pdo()->prepare(
    'INSERT INTO dispatcher_jobs (id, source, job_type, provider, status, payload_json) VALUES (?, ?, ?, ?, \'queued\', ?)'
);
$stmt->execute([
    $id,
    $source,
    $type,
    $provider,
    json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
]);

dispatcher_log('info', 'Job queued', ['id' => $id, 'source' => $source, 'type' => $type], 'ingest');
dispatcher_json(['ok' => true, 'id' => $id, 'status' => 'queued'], 202);
