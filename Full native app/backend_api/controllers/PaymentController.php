<?php
class PaymentController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function initPayment() {
        $data = getJsonBody();
        $order_id = $data['order_id'] ?? null;
        $amount = $data['amount'] ?? 0;
        $currency = $data['currency'] ?? 'INR';

        if (!$order_id || $amount <= 0) {
            jsonResponse(false, 'Invalid order_id or amount', 400);
        }

        try {
            // Include Razorpay SDK if available, or call cURL directly
            // For API purposes, we will construct the cURL request directly to Razorpay
            $api_key = RAZORPAY_KEY_ID;
            $api_secret = RAZORPAY_KEY_SECRET;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/orders");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                "amount" => $amount * 100, // in paise
                "currency" => $currency,
                "receipt" => "rcptid_" . $order_id
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $api_key . ":" . $api_secret);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $result = json_decode($response, true);

            if (isset($result['id'])) {
                $razorpay_order_id = $result['id'];
                // We should record this attempt in the payments table
                $stmt = $this->pdo->prepare("INSERT INTO payments (order_id, provider, provider_payment_id, amount, currency, status) VALUES (?, 'razorpay', ?, ?, ?, 'created')");
                $stmt->execute([$order_id, $razorpay_order_id, $amount, $currency]);

                jsonResponse(true, [
                    'razorpay_order_id' => $razorpay_order_id,
                    'amount' => $amount * 100,
                    'key' => $api_key
                ]);
            } else {
                jsonResponse(false, 'Failed to create Razorpay order', 500);
            }
        } catch (Exception $e) {
            jsonResponse(false, $e->getMessage(), 500);
        }
    }

    public function verifyPayment() {
        $data = getJsonBody();
        $order_id = $data['order_id'] ?? null;
        $razorpay_payment_id = $data['razorpay_payment_id'] ?? null;
        $razorpay_order_id = $data['razorpay_order_id'] ?? null;
        $razorpay_signature = $data['razorpay_signature'] ?? null;
        
        if (!$order_id || !$razorpay_payment_id || !$razorpay_signature) {
            jsonResponse(false, 'Missing payment verification data', 400);
        }

        try {
            // Verify signature
            $generated_signature = hash_hmac('sha256', $razorpay_order_id . "|" . $razorpay_payment_id, RAZORPAY_KEY_SECRET);
            
            if ($generated_signature === $razorpay_signature) {
                // Success
                $this->pdo->beginTransaction();

                // Update Payments table
                $stmt = $this->pdo->prepare("UPDATE payments SET status = 'paid' WHERE provider_payment_id = ?");
                $stmt->execute([$razorpay_order_id]);

                // Update Order table
                $stmt = $this->pdo->prepare("UPDATE visa_orders SET payment_status = 'paid', visa_status = 'processing' WHERE id = ?");
                $stmt->execute([$order_id]);

                $this->pdo->commit();
                jsonResponse(true, ['message' => 'Payment verified successfully']);
            } else {
                // Failed signature
                // Update Order table to failed
                $stmt = $this->pdo->prepare("UPDATE visa_orders SET payment_status = 'failed' WHERE id = ?");
                $stmt->execute([$order_id]);
                jsonResponse(false, 'Payment verification failed: Invalid signature', 400);
            }
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            jsonResponse(false, $e->getMessage(), 500);
        }
    }
}
