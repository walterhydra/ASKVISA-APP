<?php
session_start();
require 'db.php';

// Payment configuration
$razorpay_key_id = 'rzp_test_SA4dsulyUy16xi'; // Your Razorpay Key ID
$razorpay_key_secret = '76XrXqfEBKWkin3CNvAeGA6J'; // Your Razorpay Key Secret

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $order_id = $input['order_id'] ?? 0;
    $amount = $input['amount'] ?? 0;
    $currency = $input['currency'] ?? 'INR';
    
    if ($order_id && $amount > 0) {
        try {
            // Get order details from database
            $stmt = $pdo->prepare("
                SELECT vo.id, vo.email, vo.phone, vo.payment_status, vo.total_amount, vo.currency,
                       c.country_name
                FROM visa_orders vo
                JOIN countries c ON vo.country_id = c.id
                WHERE vo.id = ?
            ");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();
            
            if (!$order) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Order not found'
                ]);
                exit;
            }
            
            // Check if order is already paid
            if ($order['payment_status'] === 'paid') {
                echo json_encode([
                    'success' => false,
                    'message' => 'Order already paid'
                ]);
                exit;
            }
            
            // Use the amount from database if available
            if ($order['total_amount']) {
                $amount = $order['total_amount'];
                $currency = $order['currency'];
            }
            
            // Validate payment amount
            if ($amount <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid payment amount'
                ]);
                exit;
            }
            
            // Get total applicants count
            $stmt = $pdo->prepare("SELECT COUNT(*) as total_applicants FROM applicants WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $applicants = $stmt->fetch();
            $total_applicants = $applicants['total_applicants'] ?? 1;
            
            // Generate receipt ID
            $receipt = 'VISA_' . $order_id . '_' . time();
            
            // Store payment data in session
            $_SESSION['payment_data'] = [
                'order_id' => $order_id,
                'amount' => $amount,
                'currency' => $currency,
                'email' => $order['email'],
                'phone' => $order['phone']
            ];
            
            // Create Razorpay order
            $url = "https://api.razorpay.com/v1/orders";
            
            $data = [
                'amount' => $amount * 100, // Razorpay expects amount in paise
                'currency' => $currency,
                'receipt' => $receipt,
                'payment_capture' => 1, // Auto-capture payment
                'notes' => [
                    'order_id' => $order_id,
                    'country' => $order['country_name'],
                    'applicants' => $total_applicants,
                    'type' => 'visa_application'
                ]
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($razorpay_key_id . ':' . $razorpay_key_secret)
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $razorpayData = json_decode($response, true);
                
                // Store Razorpay order ID in session
                $_SESSION['payment_data']['razorpay_order_id'] = $razorpayData['id'];
                
                // Create payment record in database (status will be pending initially)
                $stmt = $pdo->prepare("
                    INSERT INTO payments 
                    (order_id, provider, provider_payment_id, amount, currency, status) 
                    VALUES (?, 'razorpay', ?, ?, ?, 'pending')
                ");
                $stmt->execute([$order_id, $razorpayData['id'], $amount, $currency]);
                
                echo json_encode([
                    'success' => true,
                    'razorpay_order_id' => $razorpayData['id'],
                    'amount' => $amount,
                    'currency' => $currency,
                    'key' => $razorpay_key_id,
                    'order_id' => $order_id,
                    'customer_email' => $order['email'],
                    'customer_phone' => $order['phone'],
                    'description' => 'Visa Application for ' . $order['country_name'] . ' (' . $total_applicants . ' applicant(s))'
                ]);
            } else {
                error_log("Razorpay API Error: HTTP $httpCode - $response - $error");
                
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create payment order. Please try again.'
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Payment Error: " . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Order ID and amount are required'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>