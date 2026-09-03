<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    dispatcher_json(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
if (!is_array($data)) {
    dispatcher_json(['ok' => false, 'error' => 'invalid_json'], 400);
}

$woId = trim((string)($data['wo_id'] ?? ''));
$status = strtolower(trim((string)($data['status'] ?? '')));
$commit = strtolower(trim((string)($data['commit'] ?? '')));

if (!preg_match('/^WO-[0-9]{14}(?:-[A-Za-z0-9]+)?$/', $woId)) {
    dispatcher_json(['ok' => false, 'error' => 'invalid_wo_id'], 400);
}
if (!in_array($status, ['in_progress', 'closed'], true)) {
    dispatcher_json(['ok' => false, 'error' => 'invalid_status'], 400);
}
if ($commit !== '' && !preg_match('/^[0-9a-f]{40}$/', $commit)) {
    dispatcher_json(['ok' => false, 'error' => 'invalid_commit'], 400);
}

$pdo = dispatcher_pdo();
$select = $pdo->prepare('SELECT * FROM dispatcher_workorders WHERE wo_id = ?');
$select->execute([$woId]);
$workorder = $select->fetch();
if (!$workorder) {
    dispatcher_json(['ok' => false, 'error' => 'unknown_workorder'], 404);
}

$current = (string)$workorder['status'];
if ((string)$workorder['openai_response_id'] === '') {
    dispatcher_json(['ok' => false, 'error' => 'workorder_not_assigned_to_worker'], 409);
}

$allowed = [
    'in_progress' => ['running', 'in_progress'],
    'closed' => ['in_progress', 'closed'],
];
if (!in_array($current, $allowed[$status], true)) {
    dispatcher_json([
        'ok' => false,
        'error' => 'invalid_status_transition',
        'current_status' => $current,
        'requested_status' => $status,
    ], 409);
}

if ($current === $status) {
    dispatcher_json([
        'ok' => true,
        'wo' => $woId,
        'status' => $status,
        'idempotent' => true,
    ]);
}

$path = 'workorders/' . $status . '/' . basename((string)$workorder['wo_path']);
if ($commit !== '') {
    $update = $pdo->prepare('UPDATE dispatcher_workorders SET status=?, wo_path=?, commit_sha=?, error_text=NULL WHERE wo_id=?');
    $update->execute([$status, $path, $commit, $woId]);
} else {
    $update = $pdo->prepare('UPDATE dispatcher_workorders SET status=?, wo_path=?, error_text=NULL WHERE wo_id=?');
    $update->execute([$status, $path, $woId]);
}

dispatcher_log('info', 'Work Order status reported by worker', [
    'wo' => $woId,
    'from' => $current,
    'to' => $status,
    'path' => $path,
    'commit' => $commit !== '' ? $commit : null,
], 'worker');

dispatcher_json([
    'ok' => true,
    'wo' => $woId,
    'status' => $status,
]);
