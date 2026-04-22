<?php
$file = $_GET['file'] ?? '';

$baseDir = realpath(__DIR__ . '/../GOV_IDS/uploads/');
$fullPath = realpath($baseDir . '/' . $file);

if ($fullPath && strpos($fullPath, $baseDir) === 0 && file_exists($fullPath)) {
    header('Content-Type: ' . mime_content_type($fullPath));
    readfile($fullPath);
    exit;
}

http_response_code(404);
echo "File not found";
