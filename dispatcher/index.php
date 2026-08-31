<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

session_start();
$cfg = dispatcher_config();
$error = null;

if (($_GET['logout'] ?? '') === '1') {
    $_SESSION = [];
    session_destroy();
    header('Location: /index.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $user = (string)($_POST['user'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (
        hash_equals((string)$cfg['admin_user'], $user)
        && $cfg['admin_password_hash'] !== ''
        && password_verify($password, (string)$cfg['admin_password_hash'])
    ) {
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
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NEROZON Dispatcher Login</title>
    <style>
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, sans-serif; margin: 0; background: #0b0d10; color: #f5f5f5; display: grid; place-items: center; min-height: 100vh; }
        main { width: min(420px, calc(100vw - 40px)); }
        input, button { width: 100%; box-sizing: border-box; padding: 14px; margin: 8px 0; border-radius: 10px; border: 1px solid #333; }
        button { background: #fff; color: #000; font-weight: 700; cursor: pointer; }
        .error { color: #ff8080; }
    </style>
</head>
<body>
<main>
    <h1>Dispatcher</h1>
    <p>Control Login</p>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
        <input name="user" autocomplete="username" placeholder="Benutzer" required>
        <input name="password" type="password" autocomplete="current-password" placeholder="Passwort" required>
        <button type="submit">Einloggen</button>
    </form>
</main>
</body>
</html>
<?php exit; endif;

$status = dispatcher_safe_config();
$counts = dispatcher_counts();
$logs = dispatcher_tail_log();
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NEROZON Dispatcher Control</title>
    <style>
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, sans-serif; margin: 40px; background: #f6f6f6; color: #151515; }
        header { display: flex; justify-content: space-between; align-items: baseline; gap: 20px; }
        a { color: inherit; }
        section { background: white; border-radius: 16px; padding: 20px; margin: 20px 0; box-shadow: 0 1px 8px rgba(0,0,0,.08); }
        dl { display: grid; grid-template-columns: minmax(180px, 260px) 1fr; gap: 8px 18px; }
        dt { font-weight: 700; }
        code, pre { background: #111; color: #eee; border-radius: 10px; padding: 12px; overflow: auto; }
        .ok { color: #087d31; font-weight: 700; }
        .bad { color: #ad1d1d; font-weight: 700; }
    </style>
</head>
<body>
<header>
    <div>
        <h1>NEROZON Dispatcher</h1>
        <p>Control Seite — DEV2 Stand</p>
    </div>
    <a href="?logout=1">Logout</a>
</header>

<section>
    <h2>Status</h2>
    <dl>
        <?php foreach ($status as $key => $value): ?>
            <dt><?= htmlspecialchars((string)$key) ?></dt>
            <dd class="<?= $value === false || $value === '' ? 'bad' : 'ok' ?>"><?= htmlspecialchars(is_bool($value) ? ($value ? 'ja' : 'nein') : (string)$value) ?></dd>
        <?php endforeach; ?>
    </dl>
</section>

<section>
    <h2>Queue</h2>
    <dl>
        <?php foreach ($counts as $key => $value): ?>
            <dt><?= htmlspecialchars($key) ?></dt>
            <dd><?= (int)$value ?></dd>
        <?php endforeach; ?>
    </dl>
</section>

<section>
    <h2>Payload annehmen</h2>
    <p>Produktiver Eingang bleibt <code>POST /ingest.php</code> mit Bearer Token. Die Control-Seite zeigt erstmal nur Status und Betriebslage.</p>
</section>

<section>
    <h2>Letzte Logs</h2>
    <pre><?= htmlspecialchars(implode("\n", $logs)) ?></pre>
</section>
</body>
</html>
