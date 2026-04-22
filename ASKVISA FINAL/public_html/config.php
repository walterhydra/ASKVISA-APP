<?php
// Database Credentials
// Check if running in a secure environment with env vars
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'ask_visa_local';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

define('DB_HOST', $db_host);
define('DB_NAME', $db_name);
define('DB_USER', $db_user);
define('DB_PASS', $db_pass);
define('DB_CHARSET', 'utf8mb4');

// Payment Gateway Credentials (Razorpay)
// SECURITY WARNING: Hardcoded credentials should only be used in local development.
// In production, use environment variables.
$rzp_key = getenv('RAZORPAY_KEY_ID');
$rzp_secret = getenv('RAZORPAY_KEY_SECRET');

// Fallback for local development
if (!$rzp_key) {
    $rzp_key = 'rzp_test_SA4dsulyUy16xi';
    $rzp_secret = '76XrXqfEBKWkin3CNvAeGA6J';
}

define('RAZORPAY_KEY_ID', $rzp_key);
define('RAZORPAY_KEY_SECRET', $rzp_secret);

// SMTP Configuration
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'info@askvisa.in');
define('SMTP_PASS', 'Fly@any1234');
define('SMTP_FROM_EMAIL', 'info@askvisa.in');
define('SMTP_FROM_NAME', 'Ask Visa Portal');
