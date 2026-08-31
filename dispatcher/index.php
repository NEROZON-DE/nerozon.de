<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

session_start();
$error = null;
$message = null;

if (($_GET['logout'] ?? '') === '1') {
    $_SESSION = [];
    session_destroy();
    header('Location: /index.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['login'])) {
    $user = (string)($_POST['user'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $expectedUser = (string)dispatcher_setting('admin_user');
    $hash = (string)dispatcher_setting('admin_password_hash');
    if ($expectedUser !== '' && hash_equals($expectedUser, $user) && $hash !== '' && password_verify($password, $hash)) {
        session_regenerate_id(true);
        $_SESSION['dispatcher_admin'] = true;
        header('Location: /index.php');
        exit;
    }
    $error = 'Login fehlgeschlagen.';
}

$loggedIn = ($_SESSION['dispatcher_admin'] ?? false) === true;
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

$status = dispatcher_safe_config();
$environment = (string)($status['environment'] ?? 'UNKNOWN');

if (!$loggedIn): ?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<title>NEROZON Dispatcher Login</title>
<style>
:root{color-scheme:dark}*{box-sizing:border-box}body{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;margin:0;background:#0a0d12;color:#f5f7fa;display:grid;place-items:center;min-height:100vh}.login{width:min(420px,calc(100vw - 40px));background:#121722;border:1px solid #273043;border-radius:22px;padding:28px;box-shadow:0 24px 70px rgba(0,0,0,.35)}.eyebrow{color:#8fa0bb;font-size:.8rem;text-transform:uppercase;letter-spacing:.14em;font-weight:800}h1{margin:.35rem 0 .25rem;font-size:2rem}p{color:#9eabbf}input,button{width:100%;box-sizing:border-box;padding:13px 14px;margin:7px 0;border-radius:11px;border:1px solid #354158;background:#0e131d;color:#fff;font:inherit}button{background:#f4f7fb;color:#0b1017;font-weight:800;cursor:pointer}.error{color:#ff8f8f}
</style>
</head>
<body><main class="login"><div class="eyebrow">NEROZON · <?=htmlspecialchars($environment)?></div><h1>Dispatcher</h1><p>Control Login</p><?php if($error):?><p class="error"><?=htmlspecialchars($error)?></p><?php endif;?><form method="post"><input type="hidden" name="login" value="1"><input name="user" autocomplete="username" placeholder="Benutzer" required><input name="password" type="password" autocomplete="current-password" placeholder="Passwort" required><button>Einloggen</button></form></main></body></html>
<?php exit; endif;

$pdo = dispatcher_pdo();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['process_queue'])) {
    $limit = max(1, min(20, (int)dispatcher_setting('max_jobs_per_cron', '5')));
    $select = $pdo->prepare(
        "SELECT * FROM dispatcher_workorders
         WHERE openai_response_id IS NULL
           AND status IN ('queued','start_failed')
         ORDER BY updated_at ASC
         LIMIT " . $limit
    );
    $select->execute();
    $workordersToStart = $select->fetchAll();

    $started = 0;
    $failed = 0;
    foreach ($workordersToStart as $workorder) {
        $woId = (string)$workorder['wo_id'];
        $path = (string)$workorder['wo_path'];
        if (str_starts_with($path, 'workorders/request/')) {
            $path = 'workorders/queued/' . basename($path);
            $repair = $pdo->prepare("UPDATE dispatcher_workorders SET wo_path=?, status='queued' WHERE wo_id=?");
            $repair->execute([$path, $woId]);
            $workorder['wo_path'] = $path;
            $workorder['status'] = 'queued';
        }

        try {
            $execution = dispatcher_start_worker($workorder);
            $done = $pdo->prepare(
                "UPDATE dispatcher_workorders
                 SET status='running', openai_response_id=?, openai_status=?, error_text=NULL
                 WHERE wo_id=?"
            );
            $done->execute([$execution['response_id'], $execution['response_status'], $woId]);
            dispatcher_log('info', 'Worker background response started from control queue', [
                'wo' => $woId,
                'target' => (string)$workorder['target'],
                'response_id' => $execution['response_id'],
                'openai_status' => $execution['response_status'],
            ], 'worker');
            $started++;
        } catch (Throwable $e) {
            $failedUpdate = $pdo->prepare("UPDATE dispatcher_workorders SET status='queued', error_text=? WHERE wo_id=?");
            $failedUpdate->execute([$e->getMessage(), $woId]);
            dispatcher_log('error', 'Worker start failed from control queue; Work Order remains queued', [
                'wo' => $woId,
                'error' => $e->getMessage(),
            ], 'worker');
            $failed++;
        }
    }

    $message = 'Queue verarbeitet: ' . $started . ' gestartet, ' . $failed . ' fehlgeschlagen.';
    if (!$workordersToStart) {
        $message = 'Queue verarbeitet: keine startfähigen Work Orders gefunden.';
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['save_settings'])) {
    $allowed = ['admin_user','openai_base_url','default_provider','default_model','max_jobs_per_cron','max_retries','retry_delay_seconds','cron_enabled'];
    foreach ($allowed as $key) {
        if (array_key_exists($key, $_POST)) {
            dispatcher_save_setting($key, trim((string)$_POST[$key]));
        }
    }

    foreach (['openai_api_key','ingest_token','cron_token','worker_trigger_token'] as $key) {
        if (isset($_POST['clear_' . $key])) {
            dispatcher_save_setting($key, '');
            continue;
        }
        $value = trim((string)($_POST[$key] ?? ''));
        if ($value !== '') {
            dispatcher_save_setting($key, $value);
        }
    }

    $newPassword = (string)($_POST['admin_password'] ?? '');
    if ($newPassword !== '') {
        dispatcher_save_setting('admin_password_hash', password_hash($newPassword, PASSWORD_DEFAULT));
    }
    $message = 'Einstellungen gespeichert.';
}

$status = dispatcher_safe_config();
$environment = (string)($status['environment'] ?? 'UNKNOWN');
$counts = dispatcher_counts();
$logs = dispatcher_tail_log(80);
$settings = dispatcher_settings();
$jobs = $pdo->query(
    'SELECT id, source, job_type, provider, status, attempts, error_text, created_at, updated_at '
    . 'FROM dispatcher_jobs ORDER BY created_at DESC LIMIT 25'
)->fetchAll();
$workorders = $pdo->query(
    'SELECT wo_id, target, branch_name, wo_path, status, openai_status, error_text, updated_at '
    . 'FROM dispatcher_workorders ORDER BY updated_at DESC LIMIT 25'
)->fetchAll();
$workorderQueued = (int)$pdo->query(
    "SELECT COUNT(*) FROM dispatcher_workorders WHERE openai_response_id IS NULL AND status IN ('queued','start_failed')"
)->fetchColumn();
$lastCron = $pdo->query(
    'SELECT started_at, finished_at, status, processed_count, failed_count, queued_remaining '
    . 'FROM dispatcher_cron_runs ORDER BY id DESC LIMIT 1'
)->fetch();

function control_status_class(string $status): string
{
    return match ($status) {
        'done', 'info', 'completed' => 'good',
        'queued', 'warning', 'registered' => 'warn',
        'processing', 'running' => 'active',
        'failed', 'error', 'start_failed' => 'bad',
        default => 'muted',
    };
}
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<title>NEROZON Dispatcher Control</title>
<style>
:root{--bg:#f3f5f8;--panel:#fff;--line:#dfe4ec;--text:#17202c;--muted:#68758a;--good:#147a48;--goodbg:#eaf8f0;--bad:#b33131;--badbg:#fff0f0;--warn:#946000;--warnbg:#fff7df;--active:#2759a7;--activebg:#edf4ff;--shadow:0 8px 30px rgba(34,45,67,.07)}*{box-sizing:border-box}body{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;margin:0;background:var(--bg);color:var(--text)}a{color:inherit}.shell{width:min(1220px,calc(100% - 40px));margin:0 auto;padding:34px 0 70px}header{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:22px}.brand{display:flex;align-items:center;gap:14px}.mark{width:42px;height:42px;border-radius:13px;background:#111827;color:#fff;display:grid;place-items:center;font-weight:900}.eyebrow{font-size:.76rem;letter-spacing:.13em;text-transform:uppercase;color:var(--muted);font-weight:800}h1{font-size:1.75rem;margin:.15rem 0 0}.logout{font-size:.9rem;color:var(--muted);text-decoration:none}.panel{background:var(--panel);border:1px solid var(--line);border-radius:18px;box-shadow:var(--shadow)}.overview{display:grid;grid-template-columns:1.4fr 1fr;gap:14px;margin-bottom:18px}.status-panel{padding:18px}.panel-title{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px}.panel-title h2{font-size:1rem;margin:0}.subtle{font-size:.82rem;color:var(--muted)}.chips{display:flex;flex-wrap:wrap;gap:8px}.chip{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border-radius:999px;font-size:.82rem;font-weight:750;background:#f0f2f6;color:#536076}.dot{width:8px;height:8px;border-radius:50%;background:currentColor}.good{color:var(--good);background:var(--goodbg)}.bad{color:var(--bad);background:var(--badbg)}.warn{color:var(--warn);background:var(--warnbg)}.active{color:var(--active);background:var(--activebg)}.muted{color:#6d788a;background:#f0f2f6}.queue{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}.metric{padding:12px;border-radius:13px;background:#f6f8fb;border:1px solid #edf0f5}.metric strong{display:block;font-size:1.45rem;line-height:1}.metric span{display:block;color:var(--muted);font-size:.76rem;margin-top:6px;text-transform:uppercase;letter-spacing:.05em}.msg{margin:0 0 16px;padding:11px 14px;border-radius:12px;background:var(--goodbg);color:var(--good);font-weight:750}.section{margin-top:18px}.section>.panel{padding:18px}.jobs-wrap,.log-wrap{overflow:auto;border:1px solid var(--line);border-radius:13px}table{width:100%;border-collapse:collapse;min-width:760px}th{font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);background:#f7f8fa}td,th{padding:10px 12px;text-align:left;border-bottom:1px solid #edf0f4;vertical-align:top}tbody tr:last-child td{border-bottom:0}td{font-size:.86rem}.mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.78rem}.badge{display:inline-flex;padding:4px 8px;border-radius:999px;font-size:.73rem;font-weight:800}.error-text{max-width:390px;color:var(--bad);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.toolbar{display:flex;gap:9px;flex-wrap:wrap;margin-bottom:12px}.toolbar input,.toolbar select{padding:9px 11px;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--text);font:inherit;font-size:.85rem}.toolbar input{min-width:240px;flex:1}.log-message{font-weight:700}.log-context{margin-top:5px;color:var(--muted);max-width:620px;white-space:pre-wrap;overflow-wrap:anywhere}.log-row[data-level="error"]{background:#fffafa}.log-row[data-level="warning"]{background:#fffdf7}details.settings{margin-top:18px}.settings summary{list-style:none;cursor:pointer;padding:17px 18px;font-weight:800;display:flex;justify-content:space-between;align-items:center}.settings summary::-webkit-details-marker{display:none}.settings summary::after{content:"＋";font-size:1.25rem;color:var(--muted)}.settings[open] summary::after{content:"−"}.settings-body{border-top:1px solid var(--line);padding:18px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:13px}label{display:grid;gap:6px;font-weight:700;font-size:.82rem}input,select,button{font:inherit}label input,label select{padding:10px 11px;border:1px solid #ccd3de;border-radius:9px;background:#fff}button{padding:10px 14px;border:0;border-radius:10px;background:#17202c;color:#fff;font-weight:800;cursor:pointer}.queue-action{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.queue-action button{background:#17202c}.secret-clear{display:flex;align-items:center;gap:7px;font-weight:500;color:var(--muted);font-size:.75rem}.secret-clear input{width:auto}.footnote{font-size:.78rem;color:var(--muted);margin-top:12px}@media(max-width:800px){.overview{grid-template-columns:1fr}.queue{grid-template-columns:repeat(2,1fr)}.shell{width:min(100% - 24px,1220px);padding-top:20px}header{align-items:flex-start}}
</style>
</head>
<body><main class="shell">
<header><div class="brand"><div class="mark">N</div><div><div class="eyebrow">NEROZON · <?=htmlspecialchars($environment)?></div><h1>Dispatcher Control</h1></div></div><a class="logout" href="?logout=1">Logout</a></header>
<?php if($message):?><p class="msg"><?=htmlspecialchars($message)?></p><?php endif;?>

<div class="overview">
<section class="panel status-panel"><div class="panel-title"><h2>System</h2><span class="subtle">v<?=htmlspecialchars((string)$status['version'])?></span></div><div class="chips">
<span class="chip <?=($status['database']?'good':'bad')?>"><span class="dot"></span>DB</span>
<span class="chip <?=($status['openai_configured']?'good':'bad')?>"><span class="dot"></span>OpenAI</span>
<span class="chip <?=($status['ingest_configured']?'good':'bad')?>"><span class="dot"></span>Ingest</span>
<span class="chip <?=($status['worker_trigger_configured']?'good':'bad')?>"><span class="dot"></span>Worker Token</span>
<span class="chip <?=($status['cron_configured']?'good':'bad')?>"><span class="dot"></span>Cron Token</span>
<span class="chip <?=($status['cron_enabled']?'good':'warn')?>"><span class="dot"></span>Cron <?=($status['cron_enabled']?'aktiv':'aus')?></span>
</div><p class="subtle" style="margin:14px 0 0">Provider: <strong><?=htmlspecialchars((string)$status['default_provider'])?></strong> · Modell: <strong><?=htmlspecialchars((string)$status['default_model'])?></strong><?php if($lastCron):?> · Letzter Lauf: <strong><?=htmlspecialchars((string)$lastCron['status'])?></strong> (<?=htmlspecialchars((string)$lastCron['started_at'])?>)<?php endif;?></p></section>
<section class="panel status-panel"><div class="panel-title"><h2>Queue</h2><span class="subtle"><?=$workorderQueued?> Work Orders startfähig</span></div><div class="queue-action"><form method="post"><input type="hidden" name="process_queue" value="1"><button type="submit">Process Queue</button></form><span class="subtle">Startet registrierte, noch nicht von OpenAI angenommene Work Orders erneut.</span></div></section>
</div>

<section class="section"><div class="panel"><div class="panel-title"><h2>Work Orders</h2><span class="subtle">25 aktuellste</span></div><div class="jobs-wrap"><table><thead><tr><th>Zeit</th><th>Status</th><th>WO</th><th>Target</th><th>Branch</th><th>Pfad</th><th>OpenAI</th><th>Fehler</th></tr></thead><tbody>
<?php foreach($workorders as $wo):?><tr><td><?=htmlspecialchars((string)$wo['updated_at'])?></td><td><span class="badge <?=control_status_class((string)$wo['status'])?>"><?=htmlspecialchars((string)$wo['status'])?></span></td><td class="mono"><?=htmlspecialchars((string)$wo['wo_id'])?></td><td><?=htmlspecialchars((string)$wo['target'])?></td><td><?=htmlspecialchars((string)$wo['branch_name'])?></td><td class="mono"><?=htmlspecialchars((string)$wo['wo_path'])?></td><td><?=htmlspecialchars((string)($wo['openai_status'] ?? ''))?></td><td class="error-text" title="<?=htmlspecialchars((string)($wo['error_text'] ?? ''))?>"><?=htmlspecialchars((string)($wo['error_text'] ?? ''))?></td></tr><?php endforeach;?>
<?php if(!$workorders):?><tr><td colspan="8" class="subtle">Noch keine Work Orders.</td></tr><?php endif;?>
</tbody></table></div></div></section>

<section class="section"><div class="panel"><div class="panel-title"><h2>Letzte Jobs</h2><span class="subtle">25 aktuellste</span></div><div class="jobs-wrap"><table><thead><tr><th>Zeit</th><th>Status</th><th>Quelle / Typ</th><th>Provider</th><th>Versuche</th><th>Job ID</th><th>Fehler</th></tr></thead><tbody>
<?php foreach($jobs as $job):?><tr><td><?=htmlspecialchars((string)$job['created_at'])?></td><td><span class="badge <?=control_status_class((string)$job['status'])?>"><?=htmlspecialchars((string)$job['status'])?></span></td><td><strong><?=htmlspecialchars((string)$job['source'])?></strong><br><span class="subtle"><?=htmlspecialchars((string)$job['job_type'])?></span></td><td><?=htmlspecialchars((string)$job['provider'])?></td><td><?=(int)$job['attempts']?></td><td class="mono"><?=htmlspecialchars((string)$job['id'])?></td><td class="error-text" title="<?=htmlspecialchars((string)($job['error_text'] ?? ''))?>"><?=htmlspecialchars((string)($job['error_text'] ?? ''))?></td></tr><?php endforeach;?>
<?php if(!$jobs):?><tr><td colspan="7" class="subtle">Noch keine Jobs.</td></tr><?php endif;?>
</tbody></table></div></div></section>

<section class="section"><div class="panel"><div class="panel-title"><h2>Betriebslog</h2><span class="subtle">80 aktuellste Ereignisse</span></div><div class="toolbar"><input id="logSearch" type="search" placeholder="Logs durchsuchen …"><select id="logLevel"><option value="">Alle Level</option><option value="info">Info</option><option value="warning">Warning</option><option value="error">Error</option></select><select id="logComponent"><option value="">Alle Komponenten</option></select></div><div class="log-wrap"><table><thead><tr><th>Zeit</th><th>Level</th><th>Komponente</th><th>Ereignis / Kontext</th></tr></thead><tbody id="logBody">
<?php foreach(array_reverse($logs) as $row):?><tr class="log-row" data-level="<?=htmlspecialchars((string)$row['level'])?>" data-component="<?=htmlspecialchars((string)$row['component'])?>"><td><?=htmlspecialchars((string)$row['created_at'])?></td><td><span class="badge <?=control_status_class((string)$row['level'])?>"><?=htmlspecialchars((string)$row['level'])?></span></td><td><?=htmlspecialchars((string)$row['component'])?></td><td><div class="log-message"><?=htmlspecialchars((string)$row['message'])?></div><?php if((string)$row['context_json']!==''):?><div class="log-context mono"><?=htmlspecialchars((string)$row['context_json'])?></div><?php endif;?></td></tr><?php endforeach;?>
</tbody></table></div></div></section>

<details class="panel settings"><summary>Einstellungen <span class="subtle">nur bei Bedarf öffnen</span></summary><div class="settings-body"><form method="post"><input type="hidden" name="save_settings" value="1"><div class="grid">
<label>Admin Benutzer<input name="admin_user" value="<?=htmlspecialchars($settings['admin_user']??'')?>"></label>
<label>Neues Admin Passwort<input type="password" name="admin_password" placeholder="leer = unverändert"></label>
<label>OpenAI API Key<input type="password" name="openai_api_key" placeholder="leer = unverändert"><span class="secret-clear"><input type="checkbox" name="clear_openai_api_key" value="1"> gespeicherten Wert löschen</span></label>
<label>OpenAI Base URL<input name="openai_base_url" value="<?=htmlspecialchars($settings['openai_base_url']??'')?>"></label>
<label>Default Provider<input name="default_provider" value="<?=htmlspecialchars($settings['default_provider']??'')?>"></label>
<label>Default Model<input name="default_model" value="<?=htmlspecialchars($settings['default_model']??'')?>"></label>
<label>Worker Trigger Token<input type="password" name="worker_trigger_token" placeholder="leer = unverändert"><span class="secret-clear"><input type="checkbox" name="clear_worker_trigger_token" value="1"> gespeicherten Wert löschen</span></label>
<label>Ingest Token<input type="password" name="ingest_token" placeholder="leer = unverändert"><span class="secret-clear"><input type="checkbox" name="clear_ingest_token" value="1"> gespeicherten Wert löschen</span></label>
<label>Cron Token<input type="password" name="cron_token" placeholder="leer = unverändert"><span class="secret-clear"><input type="checkbox" name="clear_cron_token" value="1"> gespeicherten Wert löschen</span></label>
<label>Jobs pro Cron<input type="number" min="1" name="max_jobs_per_cron" value="<?=htmlspecialchars($settings['max_jobs_per_cron']??'5')?>"></label>
<label>Max Retries<input type="number" min="0" name="max_retries" value="<?=htmlspecialchars($settings['max_retries']??'2')?>"></label>
<label>Retry Delay Sekunden<input type="number" min="0" name="retry_delay_seconds" value="<?=htmlspecialchars($settings['retry_delay_seconds']??'60')?>"></label>
<label>Cron aktiv<select name="cron_enabled"><option value="1" <?=($settings['cron_enabled']??'1')==='1'?'selected':''?>>ja</option><option value="0" <?=($settings['cron_enabled']??'1')==='0'?'selected':''?>>nein</option></select></label>
</div><p><button>Einstellungen speichern</button></p><div class="footnote">Secret-Felder bleiben absichtlich leer. Ein leerer Wert verändert das vorhandene Secret nicht; über die Checkbox kann es explizit gelöscht werden.</div></form></div></details>
</main>
<script>
(() => {
  const rows = [...document.querySelectorAll('.log-row')];
  const search = document.getElementById('logSearch');
  const level = document.getElementById('logLevel');
  const component = document.getElementById('logComponent');
  [...new Set(rows.map(r => r.dataset.component).filter(Boolean))].sort().forEach(value => {
    const option = document.createElement('option'); option.value = value; option.textContent = value; component.appendChild(option);
  });
  const filter = () => {
    const q = search.value.trim().toLowerCase();
    rows.forEach(row => {
      const matchesText = !q || row.textContent.toLowerCase().includes(q);
      const matchesLevel = !level.value || row.dataset.level === level.value;
      const matchesComponent = !component.value || row.dataset.component === component.value;
      row.hidden = !(matchesText && matchesLevel && matchesComponent);
    });
  };
  [search, level, component].forEach(el => el.addEventListener('input', filter));
})();
</script>
</body></html>
