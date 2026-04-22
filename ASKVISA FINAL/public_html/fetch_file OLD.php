<?php
session_start();
if (!isset($_SESSION['step'])) exit;

$relative_path = $_GET['path'] ?? '';
if (strpos($relative_path, '..') !== false) exit;

$home_dir = dirname($_SERVER['DOCUMENT_ROOT']);
$full_path = $home_dir . '/gov_id/' . $relative_path;

if (file_exists($full_path)) {
    $mime = mime_content_type($full_path);
    header("Content-Type: $mime");
    readfile($full_path);
    exit;
}