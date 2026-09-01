<?php

declare(strict_types=1);

require_once __DIR__ . '/src/worker-runtime.php';

session_start();
$bootstrap = dispatcher_bootstrap_config();
$prepE2e = (($bootstrap['environment'] ?? '') === 'PREP') && (($_GET['prep_e2e'] ?? '') === '1');
if (($_SESSION['dispatcher_admin'] ?? false) !== true && !$prepE2e) {
    dispatcher_json(['ok' => false, 'error' => 'unauthorized'], 401);
}

$woId = trim((string)($_GET['wo'] ?? $_POST['wo'] ?? ''));
if (!preg_match('/^WO-[0-9]{14}(?:-[A-Za-z0-9]+)?$/', $woId)) {
    dispatcher_json(['ok' => false, 'error' => 'invalid_wo_id'], 400);
}

$pdo = dispatcher_pdo();
$stmt = $pdo->prepare('SELECT * FROM dispatcher_workorders WHERE wo_id=?');
$stmt->execute([$woId]);
$workorder = $stmt->fetch();
if (!$workorder) dispatcher_json(['ok' => false, 'error' => 'unknown_workorder'], 404);

$responseId = trim((string)$workorder['openai_response_id']);
if ($responseId === '') dispatcher_json(['ok' => false, 'error' => 'worker_not_started'], 409);

try {
    $response = dispatcher_openai_get_response($responseId);
    $openaiStatus = (string)($response['status'] ?? 'unknown');
    $pdo->prepare('UPDATE dispatcher_workorders SET openai_status=? WHERE wo_id=?')->execute([$openaiStatus, $woId]);

    $functionCalls = [];
    $functionCallDiagnostics = [];
    foreach (($response['output'] ?? []) as $item) {
        if (!is_array($item) || ($item['type'] ?? '') !== 'function_call') continue;
        $functionCalls[] = $item;
        $args = json_decode((string)($item['arguments'] ?? '{}'), true);
        if (!is_array($args)) $args = ['_invalid_arguments' => true];
        if (array_key_exists('content', $args)) {
            $args['content'] = '[content omitted; ' . strlen((string)$args['content']) . ' bytes]';
        }
        $functionCallDiagnostics[] = [
            'name' => (string)($item['name'] ?? ''),
            'call_id' => (string)($item['call_id'] ?? ''),
            'arguments' => $args,
        ];
    }

    dispatcher_log('info', 'Worker response reconciled', [
        'wo' => $woId,
        'response_id' => $responseId,
        'openai_status' => $openaiStatus,
        'function_calls' => count($functionCalls),
        'function_call_names' => array_column($functionCallDiagnostics, 'name'),
    ], 'worker');

    if (in_array($openaiStatus, ['queued', 'in_progress'], true)) {
        dispatcher_json(['ok' => true, 'wo' => $woId, 'action' => 'wait', 'response_id' => $responseId, 'openai_status' => $openaiStatus, 'function_calls' => $functionCallDiagnostics]);
    }

    if (in_array($openaiStatus, ['failed', 'cancelled', 'incomplete'], true)) {
        dispatcher_json(['ok' => false, 'wo' => $woId, 'action' => 'terminal_failure', 'response_id' => $responseId, 'openai_status' => $openaiStatus, 'error' => $response['error'] ?? $response['incomplete_details'] ?? null, 'function_calls' => $functionCallDiagnostics], 409);
    }

    if ($openaiStatus === 'completed' && $functionCalls === []) {
        $message = 'Worker completed without requesting a tool call; reconciliation stopped to prevent a continuation loop.';
        $pdo->prepare('UPDATE dispatcher_workorders SET error_text=? WHERE wo_id=?')->execute([$message, $woId]);
        dispatcher_log('warning', 'Worker completed without action', [
            'wo' => $woId,
            'response_id' => $responseId,
            'workorder_status' => (string)$workorder['status'],
            'wo_path' => (string)$workorder['wo_path'],
        ], 'worker');
        dispatcher_json([
            'ok' => false,
            'wo' => $woId,
            'action' => 'stalled',
            'error' => 'worker_completed_without_action',
            'message' => $message,
            'response_id' => $responseId,
            'openai_status' => $openaiStatus,
            'workorder_status' => (string)$workorder['status'],
            'wo_path' => (string)$workorder['wo_path'],
            'function_calls' => [],
        ], 409);
    }

    $continuation = dispatcher_continue_worker($workorder, $response);
    $pdo->prepare('UPDATE dispatcher_workorders SET openai_response_id=?, openai_status=?, error_text=NULL WHERE wo_id=?')
        ->execute([$continuation['response_id'], $continuation['response_status'], $woId]);

    dispatcher_log('info', 'Worker continuation started', [
        'wo' => $woId,
        'previous_response_id' => $responseId,
        'response_id' => $continuation['response_id'],
        'openai_status' => $continuation['response_status'],
        'tool_calls_processed' => $continuation['tool_calls_processed'],
        'function_call_names' => array_column($functionCallDiagnostics, 'name'),
    ], 'worker');

    dispatcher_json([
        'ok' => true,
        'wo' => $woId,
        'action' => 'continued',
        'previous_response_id' => $responseId,
        'response_id' => $continuation['response_id'],
        'openai_status' => $continuation['response_status'],
        'tool_calls_processed' => $continuation['tool_calls_processed'],
        'function_calls' => $functionCallDiagnostics,
    ]);
} catch (Throwable $e) {
    $pdo->prepare('UPDATE dispatcher_workorders SET error_text=? WHERE wo_id=?')->execute([$e->getMessage(), $woId]);
    dispatcher_log('error', 'Worker reconciliation failed', ['wo' => $woId, 'response_id' => $responseId, 'error' => $e->getMessage()], 'worker');
    dispatcher_json(['ok' => false, 'wo' => $woId, 'error' => 'reconcile_failed', 'message' => $e->getMessage()], 500);
}
