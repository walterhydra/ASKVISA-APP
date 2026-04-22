<?php
// preview.php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Simple security check
if (!isset($_SESSION['step'])) {
    header('HTTP/1.0 403 Forbidden');
    die('Access denied. Please start a new application.');
}

if (isset($_GET['path'])) {
    $relative_path = base64_decode(urldecode($_GET['path']));
    
    if (empty($relative_path)) {
        die('Invalid file path');
    }
    
    // Define storage path - CHANGE THIS TO YOUR ACTUAL PATH
    $secure_storage_path = '/home/u123456789/gov_id/'; // Replace with your actual path
    
    $full_path = $secure_storage_path . $relative_path;
    
    // Security check - prevent directory traversal
    $full_path = realpath($full_path);
    $secure_base = realpath($secure_storage_path);
    
    if ($full_path === false || $secure_base === false) {
        die('Invalid path');
    }
    
    if (strpos($full_path, $secure_base) !== 0) {
        die('Access denied');
    }
    
    if (!file_exists($full_path)) {
        die('File not found');
    }
    
    // Get file info
    $mime_type = mime_content_type($full_path);
    $file_size = filesize($full_path);
    $filename = basename($full_path);
    
    // Set appropriate headers
    header("Content-Type: $mime_type");
    header("Content-Length: $file_size");
    
    // For PDFs, we need to set specific headers
    if ($mime_type === 'application/pdf') {
        // Allow embedding in iframe
        header("X-Frame-Options: ALLOWALL");
        // Set inline display for PDF viewer
        header("Content-Disposition: inline; filename=\"$filename\"");
        // Add PDF-specific headers
        header("Content-Transfer-Encoding: binary");
        header("Accept-Ranges: bytes");
    } else {
        // For images, display inline
        header("Content-Disposition: inline; filename=\"$filename\"");
    }
    
    // Add cache headers
    header("Cache-Control: private, max-age=3600");
    
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Output the file
    readfile($full_path);
    exit;
}

// If no path specified
header('HTTP/1.0 400 Bad Request');
echo 'No file specified';