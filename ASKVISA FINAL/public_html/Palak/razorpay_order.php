<?php
session_start();
require 'db.php';
require 'razorpay_autoloader.php';
require 'razorpay_config.php';

use Razorpay\Api\Api;

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? $_SESSION['current_order_id'] ?? 0;
$amount = $data['amount'] ?? 10000; // Default ₹100
$currency = $data['currency'] ?? 'INR';

try {
    // Initialize Razorpay
    $api = new Api($razorpay_config['key_id'], $razorpay_config['key_secret']);

    // Create order
    $razorpayOrder = $api->order->create([
        'receipt' => 'order_' . $order_id,
        'amount' => $amount * 100, // Convert to paise
        'currency' => $currency,
        'notes' => [
            'order_id' => $order_id,
            'country' => $_SESSION['country_name'] ?? '',
            'applicants' => $_SESSION['total_people'] ?? 1
        ]
    ]);

    // Store in database
    $stmt = $pdo->prepare("UPDATE visa_orders SET razorpay_order_id = ? WHERE id = ?");
    $stmt->execute([$razorpayOrder->id, $order_id]);

    echo json_encode([
        'success' => true,
        'key_id' => $razorpay_config['key_id'],
        'amount' => $razorpayOrder->amount,
        'currency' => $razorpayOrder->currency,
        'razorpay_order_id' => $razorpayOrder->id,
        'order_id' => $order_id,
        'country' => $_SESSION['country_name'] ?? ''
    ]);
} catch (Exception $e) {
    error_log("Razorpay order creation error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create payment order'
    ]);
}
