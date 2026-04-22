<?php
/**
 * Test script to verify session consistency and visa type fetching
 * This version uses the REAL database connection.
 */
session_start();
require 'db.php';

echo "<h1>Verification Script (Real DB)</h1>";

try {
    // 1. Simulate Landing Page Redirect
    $_GET['country'] = 'Thailand';
    echo "<h2>Step 1: Simulating redirect from landing.php?country=Thailand</h2>";

    $country_name = trim($_GET['country']);
    $stmt = $pdo->prepare("SELECT * FROM countries WHERE country_name = ? OR country_name LIKE ? LIMIT 1");
    $stmt->execute([$country_name, "%$country_name%"]);
    $country = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($country) {
        echo "Found country: " . $country['country_name'] . " (ID: " . $country['id'] . ")<br>";
        $_SESSION['country_id'] = $country['id'];
        $_SESSION['country_name'] = $country['country_name'];
        
        // Ensure table and data exist (helper from index.php)
        require_once 'index.php'; // To get the function definition
        if (function_exists('ensureVisaTypesTableExists')) {
            ensureVisaTypesTableExists($pdo);
            echo "Checked/Ensured visa_types table exists.<br>";
        }

        // Simulate initial fetch
        $stmt = $pdo->prepare("SELECT * FROM visa_types WHERE country_id = ?");
        $stmt->execute([$country['id']]);
        $visa_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $_SESSION['available_visa_types'] = $visa_types;
        
        echo "Initial visa types count from DB: " . count($visa_types) . "<br>";
    } else {
        echo "<b style='color:orange;'>Warning:</b> Thailand not found in countries table. Attempting to seed...<br>";
        $pdo->exec("INSERT INTO countries (country_code, country_name) VALUES ('TH', 'Thailand')");
        echo "Please refresh the page to test again after seeding.<br>";
        exit;
    }

    // 2. Simulate AJAX Request (Session might be clear or modified)
    echo "<h2>Step 2: Simulating AJAX validation (logic from index.php)</h2>";

    // Manually clear session for "available_visa_types" to test robustness
    unset($_SESSION['available_visa_types']);
    echo "Simulating session loss for 'available_visa_types'...<br>";

    // Logic from updated index.php
    $visa_types = $_SESSION['available_visa_types'] ?? [];
    if (empty($visa_types) && isset($_SESSION['country_id'])) {
        echo "Session empty, re-fetching from DB...<br>";
        $stmt = $pdo->prepare("SELECT id, name, price, currency, description, processing_time FROM visa_types WHERE country_id = ?");
        $stmt->execute([$_SESSION['country_id']]);
        $visa_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $_SESSION['available_visa_types'] = $visa_types;
    }

    echo "Final visa types count: " . count($visa_types) . "<br>";
    if (count($visa_types) > 0) {
        echo "<b style='color:green;'>SUCCESS:</b> Visa types successfully recovered from database using session country_id.<br>";
    } else {
        echo "<b style='color:red;'>FAILURE:</b> Could not recover visa types. Check if visa_types table has data for country_id " . $_SESSION['country_id'] . ".<br>";
    }

    // 3. Test matching logic
    echo "<h2>Step 3: Testing matching logic</h2>";
    $test_selection = count($visa_types) > 0 ? $visa_types[0]['name'] : "Tourist Visa (30 Days)"; 
    $found_visa = null;

    foreach ($visa_types as $vt) {
        if (strcasecmp(trim($vt['name']), $test_selection) === 0 || stripos($vt['name'], $test_selection) !== false) {
            $found_visa = $vt;
            break;
        }
    }

    if ($found_visa) {
        echo "Match found: " . $found_visa['name'] . " (ID: " . $found_visa['id'] . ")<br>";
        echo "<b style='color:green;'>SUCCESS:</b> Matching logic worked for '" . $test_selection . "'.<br>";
    } else {
        echo "<b style='color:red;'>FAILURE:</b> Could not match '" . $test_selection . "'.<br>";
    }

    echo "<br><a href='landing.php'>Go to Landing Page</a>";

} catch (Exception $e) {
    echo "<b style='color:red;'>Database Error:</b> " . $e->getMessage() . "<br>";
    echo "Please ensure you have created the database <b>ask_visa_local</b> and it is accessible.";
}
?>
