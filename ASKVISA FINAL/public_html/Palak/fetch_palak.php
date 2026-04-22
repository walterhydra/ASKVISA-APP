<?php
session_start();

// Basic security - only allow if user is logged in or has valid session
if (!isset($_SESSION['step']) && !isset($_SESSION['edit_verified'])) {
    http_response_code(403);
    exit('Access denied');
}

// Get the path parameter
$path = $_GET['path'] ?? '';

if (empty($path)) {
    http_response_code(400);
    exit('No file specified');
}

// Decode the URL-encoded path
$path = urldecode($path);

// Remove any 'fetch_file.php?path=' prefix if it exists
$path = str_replace('fetch_file.php?path=', '', $path);
$path = str_replace('fetch_file.php?file=', '', $path);

// Clean the path - remove any directory traversal attempts
$path = str_replace(['../', '..\\', './', '.\\'], '', $path);

// Base directory - adjust this to your actual upload directory
$base_dir = dirname($_SERVER['DOCUMENT_ROOT']) . '/gov_id/';

// Full path to the file
$full_path = $base_dir . $path;

// Security: Check if file exists and is within the base directory
$real_base = realpath($base_dir);
$real_path = realpath($full_path);

if ($real_path === false || strpos($real_path, $real_base) !== 0) {
    http_response_code(404);
    exit('File not found');
}

// Check if file exists
if (!file_exists($real_path)) {
    http_response_code(404);
    exit('File does not exist');
}

// Get file mime type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $real_path);
finfo_close($finfo);

// If mime type detection failed, guess based on extension
if (!$mime || $mime == 'application/octet-stream') {
    $ext = strtolower(pathinfo($real_path, PATHINFO_EXTENSION));
    $mime_types = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf',
        'txt' => 'text/plain',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    $mime = $mime_types[$ext] ?? 'application/octet-stream';
}

// Set headers
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($real_path));
header('Content-Disposition: inline; filename="' . basename($real_path) . '"');
header('Cache-Control: public, max-age=86400');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');

// Output file
readfile($real_path);
exit;
