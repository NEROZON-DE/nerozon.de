<?php

declare(strict_types=1);

/*
 * NEROZON Deployment Controller
 *
 * deploy.nerozon.de -> /nerozon.de/deploy
 *
 * Aufruf:
 *
 * POST /index.php?action=prepare&component=www&token=...
 * POST /index.php?action=deploy&component=www&token=...
 *
 * component:
 * - www
 * - api
 *
 * Ungültige Aufrufe liefern nur HTTP 404.
 */

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    exit;
}

$base = dirname(__DIR__);

$action    = $_GET['action'] ?? '';
$component = $_GET['component'] ?? '';
$token     = $_GET['token'] ?? '';


/*
 * Nur exakt erlaubte Aktionen.
 */

if (!in_array($action, ['prepare', 'deploy'], true)) {
    exit;
}


/*
 * Nur exakt erlaubte Komponenten.
 */

if (!in_array($component, ['www', 'api'], true)) {
    exit;
}


/*
 * Tokenformat prüfen.
 */

if (!preg_match(
    '/^dpl-[0-9]{13}-[a-f0-9]{32}$/',
    $token
)) {
    exit;
}


$tokenFile = $base . '/' . $token;


/*
 * Token muss existieren.
 */

if (!is_file($tokenFile)) {
    exit;
}


/*
 * Token maximal 20 Minuten gültig.
 */

$age = time() - filemtime($tokenFile);

if ($age < 0 || $age > 1200) {
    @unlink($tokenFile);
    exit;
}


/*
 * Fest definierte Verzeichnisse.
 */

$live     = $base . '/' . $component;
$staging  = $base . '/' . $component . '-deploy';
$rollback = $base . '/' . $component . '-rollback';


/*
 * Rekursives Löschen.
 *
 * Wird ausschließlich mit intern erzeugten
 * Pfaden aufgerufen.
 */

function removeTree(string $path): bool
{
    if (!file_exists($path)) {
        return true;
    }

    if (is_link($path) || is_file($path)) {
        return unlink($path);
    }

    $items = scandir($path);

    if ($items === false) {
        return false;
    }

    foreach ($items as $item) {

        if ($item === '.' || $item === '..') {
            continue;
        }

        if (!removeTree($path . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    return rmdir($path);
}


/*
 * ============================================================
 * PREPARE
 * ============================================================
 *
 * - vorhandenes staging löschen
 * - staging neu anlegen
 *
 * Token bleibt bestehen.
 */

if ($action === 'prepare') {

    if (!removeTree($staging)) {
        http_response_code(500);
        exit("Prepare failed: staging could not be removed.\n");
    }

    if (!mkdir($staging, 0755)) {
        http_response_code(500);
        exit("Prepare failed: staging could not be created.\n");
    }

    http_response_code(200);

    echo "NEROZON {$component} prepare successful\n";
    exit;
}


/*
 * ============================================================
 * DEPLOY
 * ============================================================
 */


/*
 * Staging muss existieren.
 */

if (!is_dir($staging)) {
    http_response_code(500);
    exit("Deployment failed: staging missing.\n");
}


/*
 * Staging darf nicht leer sein.
 */

$items = array_diff(
    scandir($staging) ?: [],
    ['.', '..']
);

if (count($items) === 0) {
    http_response_code(500);
    exit("Deployment failed: staging empty.\n");
}


/*
 * Token erst jetzt verbrauchen.
 */

if (!unlink($tokenFile)) {
    http_response_code(500);
    exit("Deployment failed: token could not be consumed.\n");
}


/*
 * Alten Rollback-Stand entfernen.
 */

if (!removeTree($rollback)) {
    http_response_code(500);
    exit("Deployment failed: old rollback could not be removed.\n");
}


/*
 * Aktuellen Produktionsstand sichern.
 */

if (is_dir($live)) {

    if (!rename($live, $rollback)) {
        http_response_code(500);
        exit("Deployment failed: live -> rollback.\n");
    }
}


/*
 * Neuen Stand aktivieren.
 */

if (!rename($staging, $live)) {

    /*
     * Falls möglich sofort alten Stand zurückholen.
     */

    if (is_dir($rollback) && !is_dir($live)) {
        rename($rollback, $live);
    }

    http_response_code(500);
    exit("Deployment failed: staging -> live.\n");
}


/*
 * Erfolg.
 */

http_response_code(200);

echo "NEROZON {$component} deployment successful\n";