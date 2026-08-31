<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$cfg = dispatcher_config();
$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    $token = (string)($_GET['token'] ?? '');
    if ($cfg['cron_token'] === '' || !hash_equals((string)$cfg['cron_token'], $token)) {
        http_response_code(404);
        exit;
    }
}

dispatcher_ensure_storage();
$limit = max(1, (int)$cfg['max_jobs_per_cron']);
$files = glob(dispatcher_data_path('queue/*.json')) ?: [];
sort($files);
$files = array_slice($files, 0, $limit);

$processed = 0;
$failed = 0;

foreach ($files as $file) {
    $job = dispatcher_read_json($file);
    $id = (string)($job['id'] ?? basename($file, '.json'));
    $processingPath = dispatcher_data_path('processing/' . $id . '.json');

    if (!rename($file, $processingPath)) {
        dispatcher_log('error', 'Could not move job to processing', ['id' => $id]);
        $failed++;
        continue;
    }

    try {
        $job['status'] = 'processing';
        $job['attempts'] = (int)($job['attempts'] ?? 0) + 1;
        $job['updated_at'] = dispatcher_now();
        dispatcher_write_json($processingPath, $job);

        if (($job['provider'] ?? 'openai') !== 'openai') {
            throw new RuntimeException('Unsupported provider: ' . (string)$job['provider']);
        }

        if ($cfg['openai_api_key'] === '') {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $result = dispatcher_openai_request($job);
        $job['status'] = 'done';
        $job['updated_at'] = dispatcher_now();
        $job['result'] = $result;

        dispatcher_write_json(dispatcher_data_path('done/' . $id . '.json'), $job);
        unlink($processingPath);
        dispatcher_log('info', 'Job done', ['id' => $id]);
        $processed++;
    } catch (Throwable $e) {
        $job['status'] = 'failed';
        $job['updated_at'] = dispatcher_now();
        $job['error'] = $e->getMessage();

        $target = ((int)($job['attempts'] ?? 1) <= (int)$cfg['max_retries'])
            ? dispatcher_data_path('queue/' . $id . '.json')
            : dispatcher_data_path('failed/' . $id . '.json');

        if (str_contains($target, '/queue/')) {
            $job['status'] = 'queued';
        }

        dispatcher_write_json($target, $job);
        unlink($processingPath);
        dispatcher_log('error', 'Job failed', ['id' => $id, 'error' => $e->getMessage()]);
        $failed++;
    }
}

$output = "processed={$processed} failed={$failed} queued=" . dispatcher_counts()['queue'] . "\n";

if ($isCli) {
    echo $output;
    exit;
}

http_response_code(200);
echo $output;
