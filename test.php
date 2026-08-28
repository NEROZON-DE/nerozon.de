<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/plain; charset=utf-8');

echo "PHP läuft.\n";
echo "PHP-Version: " . PHP_VERSION . "\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "Parent: " . dirname(__DIR__) . "\n";
?>