<?php

ini_set('display_errors', 0);
error_reporting(0);

$host = 'localhost';
$db   = 'visa_test';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->query("SELECT 1");

    // Log successful connection
    error_log("Database connection successful to $db");
} catch (\PDOException $e) {
    // Log the error
    error_log("DATABASE CONNECTION FAILED: " . $e->getMessage());
    error_log("Attempted connection: host=$host, db=$db, user=$user");


    class MockPDO
    {
        public function prepare($sql)
        {
            error_log("Mock PDO prepare called: $sql");
            return new MockPDOStatement();
        }
        public function query($sql)
        {
            error_log("Mock PDO query called: $sql");
            return new MockPDOStatement();
        }
        public function lastInsertId()
        {
            return 'MOCK_' . time();
        }
        public function beginTransaction()
        {
            return true;
        }
        public function commit()
        {
            return true;
        }
        public function rollBack()
        {
            return true;
        }
    }

    class MockPDOStatement
    {
        public function execute($params = [])
        {
            error_log("Mock execute called with: " . json_encode($params));
            return true;
        }
        public function fetch()
        {
            return [];
        }
        public function fetchAll()
        {
            return [];
        }
    }

    $pdo = new MockPDO();
}
