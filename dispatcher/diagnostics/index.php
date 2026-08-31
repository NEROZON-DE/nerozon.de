<?php

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NEROZON DEV2 HTTP Diagnostics</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 900px; margin: 40px auto; padding: 0 20px; line-height: 1.5; }
        code { background: #f3f3f3; padding: 2px 5px; border-radius: 4px; }
        .warn { padding: 12px 16px; background: #fff3cd; border-radius: 8px; }
    </style>
</head>
<body>
    <h1>NEROZON DEV2 HTTP Diagnostics</h1>
    <p class="warn"><strong>Nur Dummy-Werte senden.</strong> Die Diagnose-Endpunkte spiegeln Header und ausgewählte Server-Variablen absichtlich vollständig zurück.</p>
    <p>Diese temporären Endpunkte untersuchen, wie die IONOS/Apache/PHP-Kette Request-Header an PHP übergibt.</p>
    <ul>
        <li><a href="plain/">plain</a> – keine lokale Header-Regel</li>
        <li><a href="setenvif/">setenvif</a> – <code>SetEnvIf Authorization ... HTTP_AUTHORIZATION</code></li>
        <li><a href="rewrite/">rewrite</a> – <code>RewriteRule ... E=HTTP_AUTHORIZATION:%{HTTP:Authorization}</code></li>
        <li><a href="custom-env/">custom-env</a> – Kontrolltest: <code>SetEnvIf X-Nerozon-Probe ... NEROZON_PROBE_SEEN</code></li>
    </ul>
    <p>Die JSON-Antwort zeigt PHP-SAPI, Request-Metadaten, direkte <code>$_SERVER</code>-Lookups, <code>getenv()</code>, <code>getallheaders()</code>, <code>apache_request_headers()</code> und relevante Server-Variablen.</p>
</body>
</html>
