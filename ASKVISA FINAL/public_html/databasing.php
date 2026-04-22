<?php

// ===== DATABASE CONFIG =====
$DB_HOST = "localhost";
$DB_NAME = "u261509590_askvisa_group";
$DB_USER = "u261509590_askvisa";
$DB_PASS = "a+3oYU3>JflH";

// ===== CONNECT =====
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// ===== CHECK CONNECTION =====
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

echo "✅ Connected successfully<br><br>";

echo "<strong>MariaDB Config:</strong><br>";
echo "Host: " . $DB_HOST . "<br>";
echo "User: " . $DB_USER . "<br>";
echo "Database: " . $DB_NAME . "<br>";

// ===== SHOW SERVER INFO =====
echo "<br><strong>Server Info:</strong><br>";
echo "Server Version: " . $conn->server_info . "<br>";
echo "Host Info: " . $conn->host_info . "<br>";

$conn->close();
?>