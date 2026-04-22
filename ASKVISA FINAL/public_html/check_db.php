<?php
require 'db.php';
$output = "";
try {
    $stmt = $pdo->query("SHOW CREATE TABLE applicant_answers");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $output .= "Applicant Answers Schema:\n";
    $output .= ($row['Create Table'] ?? "Table not found") . "\n\n";
} catch (Exception $e) {
    $output .= "Error A: " . $e->getMessage() . "\n";
}

try {
    $stmt2 = $pdo->query("SHOW CREATE TABLE uploaded_files");
    $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    $output .= "Uploaded Files Schema:\n";
    $output .= ($row2['Create Table'] ?? "Table not found") . "\n";
} catch (Exception $e) {
    $output .= "Error B: " . $e->getMessage() . "\n";
}

file_put_contents('db_schema_output.txt', $output);
echo "Done";
?>
