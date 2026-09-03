<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    $token = (string)($_GET['token'] ?? '');
    $expected = (string)dispatcher_setting('cron_token');
    if ($expected === '' || !hash_equals($expected, $token)) {
        http_response_code(404);
        exit;
    }
}

if (dispatcher_setting('cron_enabled', '1') !== '1') {
    echo "cron disabled\n";
    exit;
}

$pdo = dispatcher_pdo();
$run = $pdo->prepare('INSERT INTO dispatcher_cron_runs (status) VALUES (\'running\')');
$run->execute();
$runId = (int)$pdo->lastInsertId();

$processed = 0;
$failed = 0;
$limit = max(1, (int)dispatcher_setting('max_jobs_per_cron', '5'));
$maxRetries = max(0, (int)dispatcher_setting('max_retries', '2'));
$retryDelay = max(0, (int)dispatcher_setting('retry_delay_seconds', '60'));

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT * FROM dispatcher_jobs WHERE status='queued' AND available_at <= CURRENT_TIMESTAMP ORDER BY created_at LIMIT {$limit} FOR UPDATE");
    $stmt->execute();
    $jobs = $stmt->fetchAll();

    $claim = $pdo->prepare("UPDATE dispatcher_jobs SET status='processing', attempts=attempts+1, locked_at=CURRENT_TIMESTAMP WHERE id=? AND status='queued'");
    foreach ($jobs as &$job) {
        $claim->execute([$job['id']]);
        if ($claim->rowCount() === 1) {
            $job['attempts'] = (int)$job['attempts'] + 1;
        }
    }
    unset($job);
    $pdo->commit();

    foreach ($jobs as $job) {
        try {
            if (($job['provider'] ?? 'openai') !== 'openai') {
                throw new RuntimeException('Unsupported provider: ' . (string)$job['provider']);
            }

            $result = dispatcher_openai_request($job);
            $done = $pdo->prepare("UPDATE dispatcher_jobs SET status='done', result_json=?, error_text=NULL, locked_at=NULL WHERE id=?");
            $done->execute([json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $job['id']]);
            dispatcher_log('info', 'Job done', ['id' => $job['id']], 'cron');
            $processed++;
        } catch (Throwable $e) {
            $attempts = (int)$job['attempts'];
            if ($attempts <= $maxRetries) {
                $retry = $pdo->prepare("UPDATE dispatcher_jobs SET status='queued', error_text=?, locked_at=NULL, available_at=DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ? SECOND) WHERE id=?");
                $retry->execute([$e->getMessage(), $retryDelay, $job['id']]);
            } else {
                $dead = $pdo->prepare("UPDATE dispatcher_jobs SET status='failed', error_text=?, locked_at=NULL WHERE id=?");
                $dead->execute([$e->getMessage(), $job['id']]);
            }
            dispatcher_log('error', 'Job failed', ['id' => $job['id'], 'attempts' => $attempts, 'error' => $e->getMessage()], 'cron');
            $failed++;
        }
    }

    $remaining = dispatcher_counts()['queued'];
    $finish = $pdo->prepare("UPDATE dispatcher_cron_runs SET finished_at=CURRENT_TIMESTAMP, status='done', processed_count=?, failed_count=?, queued_remaining=? WHERE id=?");
    $finish->execute([$processed, $failed, $remaining, $runId]);
    echo "processed={$processed} failed={$failed} queued={$remaining}\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $finish = $pdo->prepare("UPDATE dispatcher_cron_runs SET finished_at=CURRENT_TIMESTAMP, status='failed', processed_count=?, failed_count=?, queued_remaining=?, message=? WHERE id=?");
    $finish->execute([$processed, $failed, dispatcher_counts()['queued'], $e->getMessage(), $runId]);
    dispatcher_log('error', 'Cron run failed', ['run_id' => $runId, 'error' => $e->getMessage()], 'cron');
    http_response_code(500);
    echo "cron failed\n";
    exit(1);
}
