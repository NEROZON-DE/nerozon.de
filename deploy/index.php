<?php

declare(strict_types=1);

/*
 * NEROZON Deployment Controller
 *
 * deploy.nerozon.de -> /nerozon.de/deploy
 *
 * Ungültige Aufrufe liefern ausschließlich HTTP 404.
 */

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    exit;
}

$base = dirname(__DIR__);

/*
 * Erwartete URL:
 *
 * /dpl-www-<timestamp>-<random>
 * /dpl-api-<timestamp>-<random>
 */

$token = trim(
    (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH),
    '/'
);

if (!preg_match(
    '/^dpl-(www|api)-([0-9]{13})-([a-f0-9]{32})$/',
    $token,
    $matches
)) {
    exit;
}

$component = $matches[1];

$tokenFile = $base . '/' . $token;

/*
 * Token muss von GitHub vorher per SFTP
 * ins Root-Verzeichnis gelegt worden sein.
 */

if (!is_file($tokenFile)) {
    exit;
}

/*
 * Maximal 20 Minuten gültig.
 */

$age = time() - filemtime($tokenFile);

if ($age < 0 || $age > 1200) {
    @unlink($tokenFile);
    exit;
}

/*
 * Token sofort verbrauchen.
 */

if (!unlink($tokenFile)) {
    exit;
}


/*
 * Ab hier ist der Auftrag gültig.
 *
 * Es werden ausschließlich intern erzeugte,
 * fest definierte Pfade verwendet.
 */

$live     = $base . '/' . $component;
$staging  = $base . '/' . $component . '-deploy';
$rollback = $base . '/' . $component . '-rollback';


/*
 * Rekursives Löschen ausschließlich für
 * intern definierte Deployment-Verzeichnisse.
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
 * Staging muss existieren und darf nicht leer sein.
 */

if (!is_dir($staging)) {
    http_response_code(500);
    exit("Deployment failed: {$component}-deploy missing.\n");
}

$items = array_diff(scandir($staging) ?: [], ['.', '..']);

if (count($items) === 0) {
    http_response_code(500);
    exit("Deployment failed: staging directory empty.\n");
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
        exit("Deployment failed: {$component} -> {$component}-rollback.\n");
    }
}


/*
 * Neuen Stand aktivieren.
 */

if (!rename($staging, $live)) {

    /*
     * Falls möglich sofort alten Stand wiederherstellen.
     */

    if (is_dir($rollback) && !is_dir($live)) {
        rename($rollback, $live);
    }

    http_response_code(500);
    exit("Deployment failed: {$component}-deploy -> {$component}.\n");
}


/*
 * Erfolgreich.
 */

http_response_code(200);

echo "NEROZON {$component} deployment successful\n";