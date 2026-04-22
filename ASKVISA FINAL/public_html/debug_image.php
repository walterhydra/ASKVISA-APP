<?php
session_start();
require_once 'config.php';
require_once 'db.php';

echo "<h2>Image Path Diagnostic Tool</h2>";
echo "<p>This tool checks your database and server to find exactly why images are breaking after payment.</p>";

$home_dir = dirname($_SERVER['DOCUMENT_ROOT']);
$base_gov_id = $home_dir . '/gov_id/';
echo "<b>Server Base gov_id Path:</b> " . htmlspecialchars($base_gov_id) . " <br>";
echo "<b>Does Base Path Exist?</b> " . (is_dir($base_gov_id) ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "<br><hr>";

// 1. Check Chat Session Messages
echo "<h3>1. Chatbot Session Images (\$_SESSION['messages'])</h3>";
if (isset($_SESSION['messages'])) {
    $found_imgs = 0;
    foreach ($_SESSION['messages'] as $msg) {
        if (isset($msg['img'])) {
            $found_imgs++;
            $url = $msg['img'];
            echo "Found Image URL: " . htmlspecialchars($url) . "<br>";
            
            $parsed_url = parse_url($url);
            parse_str($parsed_url['query'] ?? '', $query_params);
            if (isset($query_params['path'])) {
                $file_path = $base_gov_id . $query_params['path'];
                echo "&rarr; Physical File: " . htmlspecialchars($file_path) . "<br>";
                if (file_exists($file_path)) {
                    echo "&rarr; Status: <b style='color:green'>File Exists</b><br>";
                } else {
                    echo "&rarr; Status: <b style='color:red'>FILE MISSING</b><br>";
                }
            }
        }
    }
    if ($found_imgs == 0) echo "No images found in chat session right now.<br>";
} else {
    echo "Chat session is empty.<br>";
}

echo "<hr>";

// 2. Check Database for Last 5 Orders
echo "<h3>2. Database Saved Images (Last 5 Uploads)</h3>";
try {
    $stmt = $pdo->query("SELECT id, order_id, file_path FROM applicant_files ORDER BY id DESC LIMIT 5");
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($files) > 0) {
        foreach ($files as $file) {
            echo "Order #" . htmlspecialchars($file['order_id']) . " | URL: " . htmlspecialchars($file['file_path']) . "<br>";
            
            $parsed_url = parse_url($file['file_path']);
            parse_str($parsed_url['query'] ?? '', $query_params);
            if (isset($query_params['path'])) {
                $file_path = $base_gov_id . $query_params['path'];
                echo "&rarr; Physical File: " . htmlspecialchars($file_path) . "<br>";
                if (file_exists($file_path)) {
                    echo "&rarr; Status: <b style='color:green'>File Exists</b><br>";
                } else {
                    echo "&rarr; Status: <b style='color:red'>FILE MISSING</b><br>";
                }
            }
            echo "<br>";
        }
    } else {
        echo "No uploaded files found in the database.<br>";
    }
} catch (Exception $e) {
    echo "Error checking database: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// 3. Scan the gov_id directory to see what actually saved recently
echo "<h3>3. Physical Files in gov_id/</h3>";
if (is_dir($base_gov_id)) {
    // Just a simple recursive scan, limit depth for safety
    function scanDirRecursive($dir, $depth = 0) {
        if ($depth > 4) return;
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $path = $dir . '/' . $file;
                $indent = str_repeat("&nbsp;&nbsp;", $depth * 2);
                if (is_dir($path)) {
                    echo $indent . "[DIR] " . htmlspecialchars($file) . "<br>";
                    scanDirRecursive($path, $depth + 1);
                } else {
                    echo $indent . "- " . htmlspecialchars($file) . "<br>";
                }
            }
        }
    }
    scanDirRecursive($base_gov_id);
}

// 4. Check for PHP Errors
echo "<hr><h3>4. Recent Server Errors (verify_payment.php)</h3>";
if (file_exists('error_log')) {
    $log = escapeshellarg('error_log');
    $errors = shell_exec("tail -n 20 $log");
    echo "<pre>" . htmlspecialchars($errors) . "</pre>";
} else {
    echo "No error_log file found in current directory.<br>";
}
?>
