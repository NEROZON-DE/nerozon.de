<?php

declare(strict_types=1);

/*
 * NEROZON Deployment Controller
 *
 * deploy.nerozon.de -> /nerozon.de/deploy
 *
 * Erlaubte Operationen:
 * - prepare-www
 * - deploy-www
 * - prepare-api
 * - deploy-api
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

/*
 * Erwartete URLs:
 *
 * /prepare-www/dpl-<timestamp>-<random>
 * /deploy-www/dpl-<timestamp>-<random>
 * /prepare-api/dpl-<timestamp>-<random>
 * /deploy-api/dpl-<timestamp>-<random>
 */

$path = trim(
    (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH),
    '/'
);

if (!preg_match(
    '/^(prepare|deploy)-(www|api)\/(dpl-[0-9]{13}-[a-f0-9]{32})$/',
    $path,
    $matches
)) {
    exit;
}

$operation = $matches[1];
$component = $matches[2];
$token     = $matches[3];

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
 * Feste Pfade.
 */

$live     = $base . '/' . $component;
$staging  = $base . '/' . $component . '-deploy';
$rollback = $base . '/' . $component . '-rollback';


/*
 * Rekursives Löschen.
 *
 * Wird nur mit intern definierten Pfaden aufgerufen.
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
 * ------------------------------------------------------------
 * PREPARE
 * ------------------------------------------------------------
 *
 * - vorhandenes staging vollständig löschen
 * - staging frisch anlegen
 *
 * Token bleibt bestehen und wird erst beim Deploy verbraucht.
 */

if ($operation === 'prepare') {

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
 * ------------------------------------------------------------
 * DEPLOY
 * ------------------------------------------------------------
 */


/*
 * Staging muss vorhanden und nicht leer sein.
 */

if (!is_dir($staging)) {
    http_response_code(500);
    exit("Deployment failed: staging missing.\n");
}

$items = array_diff(
    scandir($staging) ?: [],
    ['.', '..']
);

if (count($items) === 0) {
    http_response_code(500);
    exit("Deployment failed: staging empty.\n");
}


/*
 * Token jetzt verbrauchen.
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
     * Falls möglich alten Stand sofort zurückholen.
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