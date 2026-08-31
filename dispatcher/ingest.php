<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    dispatcher_json(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$expectedToken = (string)dispatcher_setting('ingest_token');
$providedToken = '';

// Canonical contract: Authorization: Bearer <token>.
// On the verified IONOS CGI/FastCGI runtime the root .htaccess preserves
// Authorization as REDIRECT_HTTP_AUTHORIZATION, which the helper reads.
$authorizationHeader = dispatcher_authorization_header();
if (preg_match('/^Bearer\s+(.+)$/i', $authorizationHeader, $bearerMatch)) {
    $providedToken = trim($bearerMatch[1]);
}

// DEV/internal fallback. Normal X-* headers are passed through unchanged by IONOS.
if ($providedToken === '') {
    $customHeader = $_SERVER['HTTP_X_NEROZON_INGEST_TOKEN'] ?? '';
    if (is_string($customHeader) && trim($customHeader) !== '') {
        $providedToken = trim($customHeader);
    }
}

if ($providedToken === '' && function_exists('getallheaders')) {
    $headers = getallheaders();
    if (is_array($headers)) {
        foreach ($headers as $name => $value) {
            if (strcasecmp((string)$name, 'X-Nerozon-Ingest-Token') === 0 && is_string($value)) {
                $providedToken = trim($value);
                break;
            }
        }
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
