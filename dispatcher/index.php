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

if (!$loggedIn): ?>
<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>NEROZON Dispatcher Login</title>
<style>body{font-family:system-ui,-apple-system,sans-serif;margin:0;background:#0b0d10;color:#f5f5f5;display:grid;place-items:center;min-height:100vh}main{width:min(420px,calc(100vw - 40px))}input,button{width:100%;box-sizing:border-box;padding:14px;margin:8px 0;border-radius:10px;border:1px solid #333}button{background:#fff;color:#000;font-weight:700;cursor:pointer}.error{color:#ff8080}</style></head><body><main><h1>Dispatcher</h1><p>Control Login</p><?php if($error):?><p class="error"><?=htmlspecialchars($error)?></p><?php endif;?><form method="post"><input type="hidden" name="login" value="1"><input name="user" autocomplete="username" placeholder="Benutzer" required><input name="password" type="password" autocomplete="current-password" placeholder="Passwort" required><button>Einloggen</button></form></main></body></html>
<?php exit; endif;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['save_settings'])) {
    $allowed = ['admin_user','openai_base_url','default_provider','default_model','max_jobs_per_cron','max_retries','retry_delay_seconds','cron_enabled'];
    foreach ($allowed as $key) {
        if (array_key_exists($key, $_POST)) dispatcher_save_setting($key, trim((string)$_POST[$key]));
    }
    foreach (['openai_api_key','ingest_token','cron_token'] as $key) {
        $value = trim((string)($_POST[$key] ?? ''));
        if ($value !== '') dispatcher_save_setting($key, $value);
    }
    $newPassword = (string)($_POST['admin_password'] ?? '');
    if ($newPassword !== '') dispatcher_save_setting('admin_password_hash', password_hash($newPassword, PASSWORD_DEFAULT));
    $message = 'Einstellungen gespeichert.';
}

$status = dispatcher_safe_config();
$counts = dispatcher_counts();
$logs = dispatcher_tail_log(30);
$settings = dispatcher_settings();
?>
<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>NEROZON Dispatcher Control</title>
<style>body{font-family:system-ui,-apple-system,sans-serif;margin:40px;background:#f6f6f6;color:#151515}header{display:flex;justify-content:space-between;align-items:baseline;gap:20px}section{background:white;border-radius:16px;padding:20px;margin:20px 0;box-shadow:0 1px 8px rgba(0,0,0,.08)}dl{display:grid;grid-template-columns:minmax(180px,260px) 1fr;gap:8px 18px}dt{font-weight:700}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px}label{display:grid;gap:6px;font-weight:600}input,select,button{padding:10px;border:1px solid #bbb;border-radius:8px}button{cursor:pointer;font-weight:700}.ok{color:#087d31}.bad{color:#ad1d1d}table{width:100%;border-collapse:collapse}td,th{padding:8px;text-align:left;border-bottom:1px solid #ddd;font-size:.9rem}code{background:#eee;padding:2px 5px;border-radius:5px}.msg{color:#087d31;font-weight:700}</style></head><body>
<header><div><h1>NEROZON Dispatcher</h1><p>Control — DEV2</p></div><a href="?logout=1">Logout</a></header>
<?php if($message):?><p class="msg"><?=htmlspecialchars($message)?></p><?php endif;?>
<section><h2>Status</h2><dl><?php foreach($status as $key=>$value):?><dt><?=htmlspecialchars((string)$key)?></dt><dd class="<?=($value===false||$value==='')?'bad':'ok'?>"><?=htmlspecialchars(is_bool($value)?($value?'ja':'nein'):(string)$value)?></dd><?php endforeach;?></dl></section>
<section><h2>Queue</h2><dl><?php foreach($counts as $key=>$value):?><dt><?=htmlspecialchars($key)?></dt><dd><?=(int)$value?></dd><?php endforeach;?></dl></section>
<section><h2>Einstellungen</h2><form method="post"><input type="hidden" name="save_settings" value="1"><div class="grid">
<label>Admin Benutzer<input name="admin_user" value="<?=htmlspecialchars($settings['admin_user']??'')?>"></label>
<label>Neues Admin Passwort<input type="password" name="admin_password" placeholder="leer = unverändert"></label>
<label>OpenAI API Key<input type="password" name="openai_api_key" placeholder="leer = unverändert"></label>
<label>OpenAI Base URL<input name="openai_base_url" value="<?=htmlspecialchars($settings['openai_base_url']??'')?>"></label>
<label>Default Provider<input name="default_provider" value="<?=htmlspecialchars($settings['default_provider']??'')?>"></label>
<label>Default Model<input name="default_model" value="<?=htmlspecialchars($settings['default_model']??'')?>"></label>
<label>Ingest Token<input type="password" name="ingest_token" placeholder="leer = unverändert"></label>
<label>Cron Token<input type="password" name="cron_token" placeholder="leer = unverändert"></label>
<label>Jobs pro Cron<input type="number" min="1" name="max_jobs_per_cron" value="<?=htmlspecialchars($settings['max_jobs_per_cron']??'5')?>"></label>
<label>Max Retries<input type="number" min="0" name="max_retries" value="<?=htmlspecialchars($settings['max_retries']??'2')?>"></label>
<label>Retry Delay Sekunden<input type="number" min="0" name="retry_delay_seconds" value="<?=htmlspecialchars($settings['retry_delay_seconds']??'60')?>"></label>
<label>Cron aktiv<select name="cron_enabled"><option value="1" <?=($settings['cron_enabled']??'1')==='1'?'selected':''?>>ja</option><option value="0" <?=($settings['cron_enabled']??'1')==='0'?'selected':''?>>nein</option></select></label>
</div><p><button>Einstellungen speichern</button></p></form></section>
<section><h2>Letzte Logs</h2><table><thead><tr><th>Zeit</th><th>Level</th><th>Komponente</th><th>Meldung</th><th>Kontext</th></tr></thead><tbody><?php foreach($logs as $row):?><tr><td><?=htmlspecialchars((string)$row['created_at'])?></td><td><?=htmlspecialchars((string)$row['level'])?></td><td><?=htmlspecialchars((string)$row['component'])?></td><td><?=htmlspecialchars((string)$row['message'])?></td><td><code><?=htmlspecialchars((string)$row['context_json'])?></code></td></tr><?php endforeach;?></tbody></table></section>
</body></html>
