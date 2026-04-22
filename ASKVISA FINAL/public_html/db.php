<?php
require_once 'config.php';

$host = DB_HOST;
$db   = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;
$charset = DB_CHARSET;

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Log error to file (Outside web root if possible, or protected name)
    $log_file = __DIR__ . '/php_errors.log'; // Using the existing php_errors.log which might be protected or monitored, or better:
    // Ideally: $log_file = dirname(__DIR__) . '/db_error.log'; 
    // For now, let's keep it local but obscure, or use error_log() which goes to server logs.
    error_log("DB Error: " . $e->getMessage()); 
    
    // Check if AJAX request
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    $is_ajax = $is_ajax || isset($_POST['ajax']) || isset($_GET['ajax']);
    
    if ($is_ajax) {
        http_response_code(500);
        echo json_encode(["error" => "Database connection failed. Please try again later."]); 
    } else {
        // Generic error for production
        die("<h1>Service Unavailable</h1><p>Could not connect to the database. Please try again later.</p>");
    }
    exit;
}
