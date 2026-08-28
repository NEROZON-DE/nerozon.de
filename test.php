<?php

header('Content-Type: text/plain; charset=utf-8');

$base = dirname(__DIR__);

$source = $base . '/rename-test-source';
$target = $base . '/rename-test-target';

// Alten Testzustand aufräumen
if (is_dir($source)) {
    rmdir($source);
}

if (is_dir($target)) {
    rmdir($target);
}

// Testverzeichnis erzeugen
if (!mkdir($source, 0755)) {
    http_response_code(500);
    exit("FEHLER: Source-Verzeichnis konnte nicht erstellt werden.\n");
}

echo "Erstellt: $source\n";

// Der für unser Deployment entscheidende Test
if (!rename($source, $target)) {
    http_response_code(500);
    exit("FEHLER: rename() fehlgeschlagen.\n");
}

echo "Rename erfolgreich:\n";
echo "$source\n";
echo "->\n";
echo "$target\n";

// Hinter uns wieder sauber machen
if (!rmdir($target)) {
    http_response_code(500);
    exit("Rename erfolgreich, aber Cleanup fehlgeschlagen.\n");
}

echo "Cleanup erfolgreich.\n";
echo "TEST BESTANDEN\n";