<?php
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
@ini_set('display_errors', '1');

echo "NEROZON Designer debug\n";
echo "PHP_VERSION=" . PHP_VERSION . "\n";
echo "PHP_SAPI=" . PHP_SAPI . "\n";
echo "SCRIPT_FILENAME=" . (isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '') . "\n";
echo "DOCUMENT_ROOT=" . (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '') . "\n";

$storage = dirname(__FILE__) . '/../../../runtime/designer/storage.php';
echo "STORAGE_PATH=" . $storage . "\n";
echo "STORAGE_EXISTS=" . (file_exists($storage) ? 'yes' : 'no') . "\n";
echo "STORAGE_READABLE=" . (is_readable($storage) ? 'yes' : 'no') . "\n";

$data = dirname(__FILE__) . '/../../../runtime/designer/data';
echo "DATA_PATH=" . $data . "\n";
echo "DATA_EXISTS=" . (is_dir($data) ? 'yes' : 'no') . "\n";
echo "DATA_WRITABLE=" . (is_writable($data) ? 'yes' : 'no') . "\n";

$history = $data . '/history';
echo "HISTORY_EXISTS=" . (is_dir($history) ? 'yes' : 'no') . "\n";
echo "HISTORY_WRITABLE=" . (is_writable($history) ? 'yes' : 'no') . "\n";
