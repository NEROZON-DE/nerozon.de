<?php

declare(strict_types=1);

require_once __DIR__ . '/worker-runtime.php';

function dispatcher_start_worker_with_tools(array $workorder): array
{
    $model = trim((string)dispatcher_setting('default_model'));
    if ($model === '') throw new RuntimeException('No default model configured.');

    $sequence = implode("\n", [
        '',
        'Execution sequence is mandatory:',
        '1. Read the assigned Authority ROLE.md and WORK-ORDERS.md.',
        '2. Read the current Work Order and the authoritative project/component sources it references.',
        '3. Only after successful bootstrap, move the Work Order from queued to in_progress in Git.',
        '4. Report in_progress only after that Git transition succeeds.',
        '5. Perform the assigned work and verify the result.',
        '6. Update CR History with material decisions, findings, risks, deviations, and outcome when required by the loaded rules.',
        '7. Move the Work Order from in_progress to closed in Git only when the assignment and completion conditions are satisfied.',
        '8. Report closed only after that Git transition succeeds.',
        '',
        'Do not finish with a prose-only response while a required lifecycle or implementation action remains.',
        'If a required action cannot be completed, stop and leave the Work Order in its current canonical Git state. Do not fabricate a successful transition.',
    ]);

    $body = [
        'model' => $model,
        'input' => dispatcher_worker_bootstrap_prompt($workorder) . $sequence,
        'tools' => dispatcher_worker_tools(),
        'background' => true,
        'store' => true,
        'metadata' => [
            'nerozen_type' => 'worker.execute',
            'wo' => (string)$workorder['wo_id'],
            'target' => (string)$workorder['target'],
            'branch' => (string)$workorder['branch_name'],
        ],
    ];

    $result = dispatcher_openai_http($body);
    $response = $result['response'];
    $responseId = trim((string)($response['id'] ?? ''));
    if ($responseId === '') throw new RuntimeException('OpenAI background response has no id.');

    dispatcher_log('info', 'Worker started with runtime tools', [
        'wo' => (string)$workorder['wo_id'],
        'response_id' => $responseId,
        'openai_status' => (string)($response['status'] ?? 'unknown'),
        'tool_count' => count(dispatcher_worker_tools()),
    ], 'worker');

    return [
        'response_id' => $responseId,
        'response_status' => (string)($response['status'] ?? 'unknown'),
        'http_status' => $result['http_status'],
    ];
}
