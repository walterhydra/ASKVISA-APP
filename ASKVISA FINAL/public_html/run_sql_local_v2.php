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
    // Try to connect
    $pdo = null;
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        $dsn = "mysql:host=$host;port=3307;dbname=$db;charset=$charset";
        $pdo = new PDO($dsn, $user, $pass, $options);
    }

    echo "Connected successfully to DB: " . $db . "\n";
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

    // 1. ROLLBACK: Delete ALL Thailand questions
    echo "Rolling back Thailand questions...\n";
    $stmt = $pdo->prepare("SELECT id FROM countries WHERE country_name LIKE '%Thailand%' LIMIT 1");
    $stmt->execute();
    $country = $stmt->fetch();
    $thailand_id = $country['id'];

    $pdo->exec("DELETE FROM question_options WHERE question_id IN (SELECT id FROM country_questions WHERE country_id = $thailand_id)");
    $pdo->exec("DELETE FROM country_questions WHERE country_id = $thailand_id");

    // 2. RE-INSERT ORIGINALS
    // From sec/current DB structure.txt
    echo "Re-inserting original Thailand questions...\n";
    $original_sql = "
    INSERT INTO country_questions (country_id, label, field_key, field_type, is_required, sort_order)
    VALUES
    ($thailand_id, 'First Name', 'first_name', 'text', 1, 1),
    ($thailand_id, 'Last Name', 'last_name', 'text', 1, 2),
    ($thailand_id, 'Passport Number', 'passport_number', 'text', 1, 3),
    ($thailand_id, 'Passport Front Image', 'passport_front', 'file', 1, 4),
    ($thailand_id, 'Passport Back Image', 'passport_back', 'file', 1, 5),
    ($thailand_id, 'Passport Issuing Country', 'passport_country', 'text', 1, 6),
    ($thailand_id, 'Passport Issue Date', 'passport_issue_date', 'date', 1, 7),
    ($thailand_id, 'Passport Expiry Date', 'passport_expiry_date', 'date', 1, 8),
    ($thailand_id, 'Place of Birth', 'place_of_birth', 'text', 1, 9),
    ($thailand_id, 'Date of Birth', 'date_of_birth', 'date', 1, 10),
    ($thailand_id, 'Gender', 'gender', 'select', 1, 11),
    ($thailand_id, 'Arrival Flight Number', 'arrival_flight', 'text', 1, 12),
    ($thailand_id, 'Arrival Date in Thailand', 'arrival_date', 'date', 1, 13),
    ($thailand_id, 'Hotel Name', 'hotel_name', 'text', 1, 14),
    ($thailand_id, 'Hotel City', 'hotel_city', 'text', 1, 15);
    ";
    $pdo->exec($original_sql);

    // Re-insert gender options
    $stmt = $pdo->query("SELECT id FROM country_questions WHERE country_id = $thailand_id AND field_key = 'gender' LIMIT 1");
    $gender_q = $stmt->fetch();
    if ($gender_q) {
        $gender_q_id = $gender_q['id'];
        $pdo->exec("
        INSERT INTO question_options (question_id, option_value, option_label, sort_order)
        VALUES
        ($gender_q_id, 'male', 'Male', 1),
        ($gender_q_id, 'female', 'Female', 2),
        ($gender_q_id, 'other', 'Other', 3);
        ");
    }

    echo "Originals re-inserted.\n";

    // 3. APPLY update_thailand_questions_v2.sql
    echo "Applying update_thailand_questions_v2.sql...\n";
    $sql_file = __DIR__ . '/update_thailand_questions_v2.sql';
    if (!file_exists($sql_file)) {
        die("Could not find the SQL file at: $sql_file\n");
    }

    $v2_sql = file_get_contents($sql_file);

    try {
        $pdo->exec($v2_sql);
        echo "Successfully executed update_thailand_questions_v2.sql!\n";
    } catch (\PDOException $e) {
        echo "Error executing v2 SQL script: " . $e->getMessage() . "\n";
    }

} catch (\PDOException $e) {
    echo "Connection Error: " . $e->getMessage() . "\n";
}
?>
