<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$https = ($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off';
if (!$https) {
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if ($host !== '' && preg_match('/^[A-Za-z0-9.-]+(?::[0-9]+)?$/', $host)) {
        header('Location: https://' . $host . (string)($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit;
    }
    http_response_code(403);
    echo json_encode(['status' => 'error', 'service' => 'factory-bootstrap']);
    exit;
}

http_response_code(404);
echo json_encode(['status' => 'not_found', 'service' => 'factory-bootstrap']);
