<?php
$host = 'localhost';
$db   = 'ask_visa_local';
$user = 'root';
$pass = ''; // default XAMPP/WAMP
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    // try to connect with 3306 first
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    try {
        // try to connect with 3307 next (common alternate port)
        $dsn = "mysql:host=$host;port=3307;dbname=$db;charset=$charset";
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        // try to connect without specifying db to see what dbs exist
        try {
            $dsn = "mysql:host=$host;charset=$charset";
            $pdo = new PDO($dsn, $user, $pass, $options);
            echo "Connected to MySQL, but 'ask_visa_local' might not exist.\n Databases:\n";
            $stmt = $pdo->query("SHOW DATABASES");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo $row['Database'] . "\n";
            }
        } catch (\PDOException $e) {
            echo "Could not connect to MySQL at all: " . $e->getMessage() . "\n";
            exit;
        }
        exit;
    }
}

echo "Connected successfully to DB: " . $db . "\n";

try {
    // Get country ID for Thailand
    $stmt = $pdo->prepare("SELECT id FROM countries WHERE country_name LIKE '%Thailand%' LIMIT 1");
    $stmt->execute();
    $country = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($country) {
        $country_id = $country['id'];
        echo "Found Thailand with ID: " . $country_id . "\n\n";

        // Get questions for Thailand
        $stmt = $pdo->prepare("SELECT * FROM country_questions WHERE country_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$country_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "--- Current Questions ---\n";
        foreach ($questions as $q) {
            echo "ID: " . $q['id'] . " | Key: " . $q['field_key'] . " | Label: " . $q['label'] . " | Type: " . $q['field_type'] . " | Options: " . ($q['has_options'] ? 'Yes' : 'No') . "\n";
            echo "Validation: " . $q['validation_rules'] . "\n\n";
        }
    } else {
        echo "Thailand not found in countries table.\n";
    }
} catch (\PDOException $e) {
    echo "Query Error: " . $e->getMessage() . "\n";
}
?>
