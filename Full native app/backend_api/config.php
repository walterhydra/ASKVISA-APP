<?php
// Secure headers to handle CORS for Flutter App
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database Credentials
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'ask_visa_local';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

define('DB_HOST', $db_host);
define('DB_NAME', $db_name);
define('DB_USER', $db_user);
define('DB_PASS', $db_pass);
define('DB_CHARSET', 'utf8mb4');

// Payment Gateway Credentials
$rzp_key = getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_SA4dsulyUy16xi';
$rzp_secret = getenv('RAZORPAY_KEY_SECRET') ?: '76XrXqfEBKWkin3CNvAeGA6J';

define('RAZORPAY_KEY_ID', $rzp_key);
define('RAZORPAY_KEY_SECRET', $rzp_secret);

// SMTP Configuration
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'info@askvisa.in');
define('SMTP_PASS', 'Fly@any1234');
define('SMTP_FROM_EMAIL', 'info@askvisa.in');
define('SMTP_FROM_NAME', 'Ask Visa Portal');

// Initialize Database Connection
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => "Database connection failed"]);
    exit;
}

// Helper to send JSON response
function jsonResponse($success, $data, $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => $success], is_array($data) ? $data : ['message' => $data]));
    exit;
}

// Helper to decode JSON body
function getJsonBody() {
    $json = file_get_contents('php://input');
    return json_decode($json, true) ?: [];
}
