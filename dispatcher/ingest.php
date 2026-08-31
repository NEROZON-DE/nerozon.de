<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    dispatcher_json(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$cfg = dispatcher_config();
dispatcher_require_bearer((string)$cfg['ingest_token']);
dispatcher_ensure_storage();

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);

if (!is_array($data)) {
    dispatcher_json(['ok' => false, 'error' => 'invalid_json'], 400);
}

$source = (string)($data['source'] ?? 'api');
$type = (string)($data['type'] ?? 'llm.request');
$payload = $data['payload'] ?? null;

if (!is_array($payload)) {
    dispatcher_json(['ok' => false, 'error' => 'missing_payload'], 400);
}

$id = dispatcher_uuid();
$job = [
    'id' => $id,
    'source' => $source,
    'type' => $type,
    'provider' => $data['provider'] ?? $cfg['default_provider'],
    'status' => 'queued',
    'attempts' => 0,
    'created_at' => dispatcher_now(),
    'updated_at' => dispatcher_now(),
    'payload' => $payload,
];

$path = dispatcher_data_path('queue/' . $id . '.json');
dispatcher_write_json($path, $job);
dispatcher_log('info', 'Job queued', ['id' => $id, 'source' => $source, 'type' => $type]);

dispatcher_json(['ok' => true, 'id' => $id, 'status' => 'queued'], 202);
