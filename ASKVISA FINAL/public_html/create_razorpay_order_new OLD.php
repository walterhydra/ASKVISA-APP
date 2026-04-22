<?php
session_start();
require 'db.php';

// Payment configuration
$razorpay_key_id = 'rzp_test_SA4dsulyUy16xi'; // Your Razorpay Key ID
$razorpay_key_secret = '76XrXqfEBKWkin3CNvAeGA6J'; // Your Razorpay Key Secret

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $temp_order_id = $input['temp_order_id'] ?? '';
    $amount = $input['amount'] ?? 0;
    $currency = $input['currency'] ?? 'INR';
    $email = $input['email'] ?? '';
    $phone = $input['phone'] ?? '';
    $country_id = $input['country_id'] ?? 0;
    
    if ($temp_order_id && $amount > 0 && isset($_SESSION['temp_application_data'])) {
        try {
            // Get order details from session
            $order_data = $_SESSION['temp_application_data'];
            $country_name = $order_data['country_name'] ?? '';
            $total_applicants = $order_data['total_people'] ?? 1;

            // Validate session data
            if (empty($order_data)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Application data not found in session'
                ]);
                exit;
            }
            
            // Use session data
            if (isset($order_data['payment_amount'])) {
                $amount = $order_data['payment_amount'];
            }
            if (isset($order_data['currency'])) {
                $currency = $order_data['currency'];
            }
            if (isset($order_data['order_contact_email'])) {
                $email = $order_data['order_contact_email'];
            }
            if (isset($order_data['order_contact_phone'])) {
                $phone = $order_data['order_contact_phone'];
            }
            
            // Validate payment amount
            if ($amount <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid payment amount'
                ]);
                exit;
            }
            
            // Generate receipt ID
            $receipt = 'VISA_' . $temp_order_id . '_' . time();
                    
            // Store payment data in session (NO DB entry yet)
            $_SESSION['razorpay_order_data'] = [
                'temp_order_id' => $temp_order_id,
                'amount' => $amount,
                'currency' => $currency,
                'email' => $email,
                'phone' => $phone,
                'country_id' => $country_id,
                'order_data' => $order_data
            ];
            
            // Create Razorpay order
            $url = "https://api.razorpay.com/v1/orders";
            
            $data = [
                'amount' => $amount * 100, // Razorpay expects amount in paise
                'currency' => $currency,
                'receipt' => $receipt,
                'payment_capture' => 1, // Auto-capture payment
                'notes' => [
                    'order_id' => $temp_order_id,
                    'country' => $country_name, // Changed from $order['country_name']
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
                $_SESSION['razorpay_order_data']['razorpay_order_id'] = $razorpayData['id'];
                            
                // NO database entry yet - will be created in verify_payment.php
                                
                echo json_encode([
                    'success' => true,
                    'razorpay_order_id' => $razorpayData['id'],
                    'amount' => $amount,
                    'currency' => $currency,
                    'key' => $razorpay_key_id,
                    'temp_order_id' => $temp_order_id,
                    'customer_email' => $email,
                    'customer_phone' => $phone,
                    'description' => 'Visa Application for ' . $country_name . ' (' . $total_applicants . ' applicant(s))'
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