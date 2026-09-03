<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    dispatcher_json(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

dispatcher_require_bearer((string)dispatcher_setting('worker_trigger_token'));

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
if (!is_array($data)) {
    dispatcher_json(['ok' => false, 'error' => 'invalid_json'], 400);
}

$action = trim((string)($_GET['action'] ?? $data['action'] ?? ''));
$required = ['wo_id', 'target', 'repository', 'branch', 'path', 'commit', 'authority_repository', 'authority_branch', 'authority_path'];
foreach ($required as $key) {
    if (!isset($data[$key]) || !is_string($data[$key]) || trim($data[$key]) === '') {
        dispatcher_json(['ok' => false, 'error' => 'missing_' . $key], 400);
    }
}

$woId = trim($data['wo_id']);
$target = strtoupper(trim($data['target']));
$repository = trim($data['repository']);
$branch = trim($data['branch']);
$path = ltrim(trim($data['path']), '/');
$commit = strtolower(trim($data['commit']));
$authorityRepository = trim($data['authority_repository']);
$authorityBranch = trim($data['authority_branch']);
$authorityPath = ltrim(trim($data['authority_path']), '/');

if (!preg_match('/^WO-[0-9]{14}(?:-[A-Za-z0-9]+)?$/', $woId)) {
    dispatcher_json(['ok' => false, 'error' => 'invalid_wo_id'], 400);
}
if (!in_array($target, ['ENGINEERING', 'SOLUTION', 'IMPLEMENTATION', 'QUALITY-ASSURANCE'], true)) {
    dispatcher_json(['ok' => false, 'error' => 'invalid_target'], 400);
}
if (!preg_match('/^[0-9a-f]{40}$/', $commit)) {
    dispatcher_json(['ok' => false, 'error' => 'invalid_commit'], 400);
}
if ($repository !== 'NEROZON-DE/nerozon.de' || $branch !== 'dev1') {
    dispatcher_json(['ok' => false, 'error' => 'dev1_poc_only'], 400);
}

$expectedAuthorities = [
    'ENGINEERING' => 'NEROZON-DE/authority-engineering',
    'SOLUTION' => 'NEROZON-DE/authority-solution',
    'IMPLEMENTATION' => 'NEROZON-DE/authority-implementation',
    'QUALITY-ASSURANCE' => 'NEROZON-DE/authority-quality-assurance',
];
if (
    $authorityRepository !== $expectedAuthorities[$target]
    || $authorityBranch !== 'main'
    || $authorityPath !== 'ROLE.md'
) {
    dispatcher_json(['ok' => false, 'error' => 'invalid_authority_mapping'], 400);
}

$pdo = dispatcher_pdo();

if ($action === 'register') {
    if (!str_starts_with($path, 'workorders/request/')) {
        dispatcher_json(['ok' => false, 'error' => 'register_requires_request_path'], 400);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO dispatcher_workorders
        (wo_id, target, repository, branch_name, wo_path, commit_sha, authority_repository, authority_branch, authority_path, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'registered')
        ON DUPLICATE KEY UPDATE
          target=VALUES(target), repository=VALUES(repository), branch_name=VALUES(branch_name),
          wo_path=VALUES(wo_path), commit_sha=VALUES(commit_sha), authority_repository=VALUES(authority_repository),
          authority_branch=VALUES(authority_branch), authority_path=VALUES(authority_path)"
    );
    $stmt->execute([$woId, $target, $repository, $branch, $path, $commit, $authorityRepository, $authorityBranch, $authorityPath]);
    dispatcher_log('info', 'Work Order registered', ['wo' => $woId, 'target' => $target, 'branch' => $branch, 'path' => $path], 'workorder');
    dispatcher_json(['ok' => true, 'wo' => $woId, 'status' => 'registered'], 201);
}

if ($action === 'start') {
    if (!str_starts_with($path, 'workorders/queued/')) {
        dispatcher_json(['ok' => false, 'error' => 'start_requires_queued_path'], 400);
    }

    $select = $pdo->prepare('SELECT * FROM dispatcher_workorders WHERE wo_id = ?');
    $select->execute([$woId]);
    $workorder = $select->fetch();
    if (!$workorder) {
        dispatcher_json(['ok' => false, 'error' => 'workorder_not_registered'], 409);
    }
    if ((string)$workorder['openai_response_id'] !== '') {
        dispatcher_json([
            'ok' => true,
            'wo' => $woId,
            'status' => $workorder['status'],
            'openai_response_id' => $workorder['openai_response_id'],
            'idempotent' => true,
        ]);
    }

    $update = $pdo->prepare(
        "UPDATE dispatcher_workorders SET wo_path=?, commit_sha=?, status='starting', error_text=NULL WHERE wo_id=?"
    );
    $update->execute([$path, $commit, $woId]);

    $select->execute([$woId]);
    $workorder = $select->fetch();

    try {
        $execution = dispatcher_start_worker($workorder);
        $done = $pdo->prepare(
            "UPDATE dispatcher_workorders
             SET status='running', openai_response_id=?, openai_status=?, error_text=NULL
             WHERE wo_id=?"
        );
        $done->execute([$execution['response_id'], $execution['response_status'], $woId]);
        dispatcher_log('info', 'Worker background response started', [
            'wo' => $woId,
            'target' => $target,
            'response_id' => $execution['response_id'],
            'openai_status' => $execution['response_status'],
        ], 'worker');
        dispatcher_json([
            'ok' => true,
            'wo' => $woId,
            'status' => 'running',
            'openai_response_id' => $execution['response_id'],
            'openai_status' => $execution['response_status'],
        ], 202);
    } catch (Throwable $e) {
        $failed = $pdo->prepare("UPDATE dispatcher_workorders SET status='start_failed', error_text=? WHERE wo_id=?");
        $failed->execute([$e->getMessage(), $woId]);
        dispatcher_log('error', 'Worker start failed', ['wo' => $woId, 'error' => $e->getMessage()], 'worker');
        dispatcher_json(['ok' => false, 'error' => 'worker_start_failed', 'detail' => $e->getMessage()], 502);
    }
}

dispatcher_json(['ok' => false, 'error' => 'invalid_action'], 400);
