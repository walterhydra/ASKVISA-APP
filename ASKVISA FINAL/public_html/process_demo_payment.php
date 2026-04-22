<?php
session_start();
require 'localdb.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'No input data']);
    exit;
}

$order_id = $input['order_id'] ?? '';
$result = $input['result'] ?? '';
$amount = $input['amount'] ?? 0;
$email = $input['email'] ?? '';

try {
    if ($result === 'success') {
        // Check if order_id is a temp ID
        if (strpos($order_id, 'TEMP_') === 0) {
            // Create real order from temp
            $stmt = $pdo->prepare("INSERT INTO visa_orders 
                (country_id, email, phone, payment_status, total_amount, currency) 
                VALUES (?, ?, ?, 'paid', ?, ?)");

            $stmt->execute([
                $_SESSION['country_id'] ?? 0,
                $_SESSION['order_contact_email'] ?? '',
                $_SESSION['order_contact_phone'] ?? '',
                $amount / 83, // Convert back to USD
                'USD'
            ]);

            $real_order_id = $pdo->lastInsertId();

            // Save applicants
            for ($i = 1; $i <= ($_SESSION['total_people'] ?? 0); $i++) {
                if (isset($_SESSION['collected_info']["applicant_$i"])) {
                    $app_data = $_SESSION['collected_info']["applicant_$i"];

                    $stmt = $pdo->prepare("INSERT INTO applicants 
                        (order_id, applicant_no, applicant_email, applicant_phone, visa_status) 
                        VALUES (?, ?, ?, ?, 'submitted')");
                    $stmt->execute([
                        $real_order_id,
                        $i,
                        $app_data['email'] ?? '',
                        $app_data['phone'] ?? ''
                    ]);
                }
            }

            $order_id = $real_order_id;
        } else {
            // Update existing order to paid
            $stmt = $pdo->prepare("UPDATE visa_orders SET payment_status = 'paid' WHERE id = ?");
            $stmt->execute([$order_id]);
        }

        // Create payment record
        $transaction_id = 'DEMO_TXN_' . time() . '_' . rand(1000, 9999);
        $stmt = $pdo->prepare("INSERT INTO payments 
            (order_id, provider, provider_payment_id, amount, currency, status, created_at) 
            VALUES (?, 'demo', ?, ?, 'INR', 'success', NOW())");
        $stmt->execute([$order_id, $transaction_id, $amount]);

        // Update session
        $_SESSION['current_order_id'] = $order_id;
        $_SESSION['payment_status'] = 'paid';
        $_SESSION['last_transaction_id'] = $transaction_id;

        echo json_encode([
            'success' => true,
            'order_id' => $order_id,
            'transaction_id' => $transaction_id,
            'message' => 'Demo payment processed successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Demo payment failed'
        ]);
    }
} catch (Exception $e) {
    error_log("Demo payment error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Payment processing error: ' . $e->getMessage()
    ]);
}
