<?php
require 'db.php';

echo "=== VISA DATABASE CHECK ===\n";

try {
    // 1. Check Thailand Country
    $stmt = $pdo->prepare("SELECT id, country_name FROM countries WHERE country_name = 'Thailand'");
    $stmt->execute();
    $country = $stmt->fetch();
    
    if ($country) {
        echo "[OK] Country 'Thailand' found (ID: " . $country['id'] . ")\n";
        
        // 2. Check Visa Types for Thailand
        $stmt = $pdo->prepare("SELECT id, name, price, currency FROM visa_types WHERE country_id = ?");
        $stmt->execute([$country['id']]);
        $visa_types = $stmt->fetchAll();
        
        echo "[OK] Found " . count($visa_types) . " visa types for Thailand:\n";
        foreach ($visa_types as $vt) {
            echo "     - " . $vt['name'] . " (" . $vt['currency'] . " " . $vt['price'] . ")\n";
        }
        
        // 3. Check Questions for Thailand
        $stmt = $pdo->prepare("SELECT count(*) FROM country_questions WHERE country_id = ?");
        $stmt->execute([$country['id']]);
        $q_count = $stmt->fetchColumn();
        echo "[OK] Found $q_count questions for Thailand in country_questions table.\n";
        
    } else {
        echo "[ERROR] Country 'Thailand' NOT found in database.\n";
    }

} catch (Exception $e) {
    echo "[CRITICAL] Database Error: " . $e->getMessage() . "\n";
}
echo "===========================\n";
?>
