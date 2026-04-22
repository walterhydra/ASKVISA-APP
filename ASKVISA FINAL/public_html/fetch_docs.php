<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    exit('Forbidden');
}

$path = $_GET['path'] ?? '';
if (!$path) {
    http_response_code(400);
    exit('Missing path');
}

$path = urldecode($path);
$path = ltrim($path, '/');

if (strpos($path, '..') !== false || strpos($path, './') !== false) {
    http_response_code(400);
    exit('Invalid path');
}

// CORRECT BASE DIR (this is the key)
$BASE_DIR = __DIR__ . '/../gov_id/';

$fullPath = realpath($BASE_DIR . $path);

if (!$fullPath) {
    http_response_code(404);
    exit("File not found");
}

if (strpos($fullPath, realpath($BASE_DIR)) !== 0) {
    http_response_code(403);
    exit('Forbidden');
}

if (!is_file($fullPath)) {
    http_response_code(404);
    exit('File not found');
}

$mime = mime_content_type($fullPath) ?: 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($fullPath));
header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
header('X-Content-Type-Options: nosniff');

readfile($fullPath);
exit;
