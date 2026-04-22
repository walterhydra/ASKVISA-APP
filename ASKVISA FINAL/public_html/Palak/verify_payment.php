<?php
// verify_payment.php
// Clear any previous output
while (ob_get_level()) {
    ob_end_clean();
}

// Start output buffering
ob_start();

// Set JSON header FIRST
header('Content-Type: application/json; charset=UTF-8');

// Disable error display, enable logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Log file
$logFile = __DIR__ . '/payment_verification.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " === Verification Started ===\n", FILE_APPEND);

// Function to log and return JSON error
function returnError($message)
{
    global $logFile;
    file_put_contents($logFile, "ERROR: $message\n", FILE_APPEND);

    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

// Function to log success
function logInfo($message)
{
    global $logFile;
    file_put_contents($logFile, "INFO: $message\n", FILE_APPEND);
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// If JSON decode failed, try regular POST as fallback
if ($data === null) {
    $data = $_POST;
}

try {
    // Get POST data
    $razorpay_payment_id = $data['razorpay_payment_id'] ?? '';
    $razorpay_order_id = $data['razorpay_order_id'] ?? '';
    $razorpay_signature = $data['razorpay_signature'] ?? '';
    $order_id = $data['order_id'] ?? '';
    $amount = $data['amount'] ?? 0;
    $email = $data['email'] ?? '';

    logInfo("Received payment: $razorpay_payment_id for order: $order_id");

    // Validate required data
    if (empty($razorpay_payment_id)) returnError('Missing razorpay_payment_id');
    if (empty($razorpay_order_id)) returnError('Missing razorpay_order_id');
    if (empty($razorpay_signature)) returnError('Missing razorpay_signature');
    if (empty($order_id)) returnError('Missing order_id');

    // Load Razorpay key
    $keySecret = '';
    if (file_exists(__DIR__ . '/razorpay_config.php')) {
        require __DIR__ . '/razorpay_config.php';
        $keySecret = $razorpay_config['key_secret'] ?? '';
    }

    if (empty($keySecret)) {
        // Try environment variable
        if (file_exists(__DIR__ . '/config/EnvironmentLoader.php')) {
            require_once __DIR__ . '/config/EnvironmentLoader.php';
            $keySecret = EnvironmentLoader::get('RAZORPAY_KEY_SECRET');
        }
    }

    if (empty($keySecret)) {
        returnError('Razorpay secret key not configured');
    }

    // Generate signature for verification
    $generated_signature = hash_hmac(
        'sha256',
        $razorpay_order_id . '|' . $razorpay_payment_id,
        $keySecret
    );

    logInfo("Generated signature: " . substr($generated_signature, 0, 20) . "...");
    logInfo("Received signature: " . substr($razorpay_signature, 0, 20) . "...");

    // Verify signature
    if (!hash_equals($generated_signature, $razorpay_signature)) {
        returnError('Payment verification failed: Invalid signature');
    }

    logInfo("Signature verified successfully");

    // Load database
    $pdo = null;
    if (file_exists(__DIR__ . '/config/database.php')) {
        require_once __DIR__ . '/config/database.php';
        $pdo = getDatabaseConnection();
    } elseif (file_exists(__DIR__ . '/db.php')) {
        require_once __DIR__ . '/db.php';
        // If db.php sets $pdo directly
        if (!isset($pdo) && isset($GLOBALS['pdo'])) {
            $pdo = $GLOBALS['pdo'];
        }
    }

    if (!$pdo) {
        returnError('Database connection failed');
    }

    // Start database transaction
    $pdo->beginTransaction();

    try {
        // 1. Check if payment already processed
        $stmt = $pdo->prepare("SELECT id FROM payments WHERE provider_payment_id = ?");
        $stmt->execute([$razorpay_payment_id]);

        if ($stmt->fetch()) {
            throw new Exception('This payment was already processed');
        }

        // 2. Update visa_orders table
        $stmt = $pdo->prepare("UPDATE visa_orders SET 
            payment_status = 'paid',
            updated_at = NOW()
            WHERE id = ?");

        $stmt->execute([$order_id]);
        $rowsUpdated = $stmt->rowCount();

        if ($rowsUpdated === 0) {
            throw new Exception("Order #$order_id not found");
        }

        logInfo("Updated visa_orders for order #$order_id");

        // 3. Insert into payments table
        $stmt = $pdo->prepare("INSERT INTO payments 
            (order_id, provider, provider_payment_id, amount, currency, status, created_at) 
            VALUES (?, 'razorpay', ?, ?, 'INR', 'completed', NOW())");

        $stmt->execute([
            $order_id,
            $razorpay_payment_id,
            $amount
        ]);

        $paymentId = $pdo->lastInsertId();
        logInfo("Inserted payment record #$paymentId");

        // 4. If encryption service exists, encrypt sensitive data
        if (file_exists(__DIR__ . '/services/EncryptionService.php')) {
            require_once __DIR__ . '/services/EncryptionService.php';
            $encryption = new EncryptionService();

            // Encrypt payment ID
            $encryptedPaymentId = $encryption->encrypt($razorpay_payment_id);

            // Update payments table with encrypted version
            $stmt = $pdo->prepare("UPDATE payments SET 
                encrypted_payment_id = ? 
                WHERE id = ?");
            $stmt->execute([$encryptedPaymentId, $paymentId]);

            logInfo("Payment ID encrypted and stored");
        }

        // Commit transaction
        $pdo->commit();

        logInfo("Transaction committed successfully");

        // Return success
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Payment verified successfully!',
            'order_id' => $order_id,
            'payment_id' => $razorpay_payment_id
        ]);
    } catch (Exception $e) {
        // Rollback on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
} catch (Exception $e) {
    returnError('Payment verification failed: ' . $e->getMessage());
}


file_put_contents($logFile, date('Y-m-d H:i:s') . " === Verification Ended ===\n\n", FILE_APPEND);
