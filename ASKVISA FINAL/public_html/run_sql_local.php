<?php
require 'db.php';

try {
    echo "Connected successfully to DB: " . DB_NAME . "\n";

    // Read the SQL file
    $sql_file = __DIR__ . '/update_thailand_questions.sql';
    if (!file_exists($sql_file)) {
        die("Could not find the SQL file at: $sql_file\n");
    }

    $sql = file_get_contents($sql_file);

    // Execute the SQL
    // PDO::exec() executes an SQL statement in a single function call, returning the number of rows affected by the statement.
    // However, it doesn't support executing multiple statements (like SET @var processing) by default unless emulated prepares are on.
    // It's safer to use query() for this or enable multi queries.
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    
    // Split the SQL file into separate statements if necessary, but actually try executing the whole block first.
    try {
        $pdo->exec($sql);
        echo "Successfully executed update_thailand_questions.sql!\n";
    } catch (\PDOException $e) {
        echo "Error executing SQL script: " . $e->getMessage() . "\n";
    }

} catch (\PDOException $e) {
    echo "Connection Error: " . $e->getMessage() . "\n";
}
?>
