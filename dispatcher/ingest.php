<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    dispatcher_json(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

dispatcher_require_bearer((string)dispatcher_setting('ingest_token'));

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
