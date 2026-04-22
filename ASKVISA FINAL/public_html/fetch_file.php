<?php
session_start();
if (!isset($_SESSION['step'])) exit;

$relative_path = $_GET['path'] ?? '';
if (strpos($relative_path, '..') !== false) exit;

$home_dir = dirname($_SERVER['DOCUMENT_ROOT']);
$base_dir = $home_dir . '/gov_id/';
$full_path = $base_dir . $relative_path;

// Security check: Ensure the requested path doesn't try to go up directories
if (strpos($relative_path, '../') !== false || strpos($relative_path, '..\\') !== false) {
    exit;
}

// Ensure the file actually exists
if (!file_exists($full_path)) {
    // Attempted directory traversal or file does not exist
    exit;
}


// Security Check: IDOR Protection
// Allow access if the path contains the current session's temp order folder
// OR if the payment was successful and the path contains the new Order_{ID} folder
$allowed = false;
if (isset($_SESSION['order_folder_name']) && strpos($relative_path, $_SESSION['order_folder_name']) !== false) {
    $allowed = true;
}

if (isset($_SESSION['payment_success_order_id']) && strpos($relative_path, 'Order_' . $_SESSION['payment_success_order_id']) !== false) {
    $allowed = true;
}

if (!$allowed) {
    // Attempt to access file outside of current session's ownership
    http_response_code(403);
    exit;
}

if (file_exists($full_path)) {
    $mime = mime_content_type($full_path);
    header("Content-Type: $mime");
    readfile($full_path);
    exit;
}