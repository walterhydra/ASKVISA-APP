<?php
session_start();
require 'db.php';

// Razorpay credentials
$razorpay_key_id = 'rzp_test_SA4dsulyUy16xi';
$razorpay_key_secret = '76XrXqfEBKWkin3CNvAeGA6J';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $razorpay_payment_id = $_POST['razorpay_payment_id'] ?? '';
    $razorpay_order_id = $_POST['razorpay_order_id'] ?? '';
    $razorpay_signature = $_POST['razorpay_signature'] ?? '';
    $temp_order_id = $_POST['temp_order_id'] ?? '';
    $is_success = $_POST['is_success'] ?? '0';
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        if ($is_success === '1' && !empty($razorpay_payment_id)) {
            // Verify payment signature
            $generated_signature = hash_hmac('sha256', $razorpay_order_id . "|" . $razorpay_payment_id, $razorpay_key_secret);
            
            if ($generated_signature === $razorpay_signature) {
                // Signature verified - payment successful
                
                // Check if we have session data
                if (!isset($_SESSION['temp_application_data'])) {
                    throw new Exception('Application data not found in session');
                }
                
                $order_data = $_SESSION['temp_application_data'];
                
                // 1. Create actual order in database NOW
                $stmt = $pdo->prepare("
                    INSERT INTO visa_orders 
                    (country_id, email, phone, total_amount, currency, payment_status, visa_status) 
                    VALUES (?, ?, ?, ?, ?, 'paid', 'submitted')
                ");
                $stmt->execute([
                    $order_data['country_id'],
                    $order_data['order_contact_email'],
                    $order_data['order_contact_phone'],
                    $order_data['payment_amount'],
                    $order_data['currency']
                ]);
                
                $real_order_id = $pdo->lastInsertId();
                
                // 2. Create payment record
                $stmt = $pdo->prepare("
                    INSERT INTO payments 
                    (order_id, provider, provider_payment_id, amount, currency, status) 
                    VALUES (?, 'razorpay', ?, ?, ?, 'success')
                ");
                $stmt->execute([$real_order_id, $razorpay_payment_id, $order_data['payment_amount'], $order_data['currency']]);
                
                // 3. Save applicants to database
                if (isset($order_data['collected_info']) && !empty($order_data['collected_info'])) {
                    foreach ($order_data['collected_info'] as $applicant_key => $applicant_data) {
                        if (preg_match('/applicant_(\d+)/', $applicant_key, $matches)) {
                            $applicant_no = $matches[1];
                            $applicant_email = $applicant_data['email'] ?? '';
                            $applicant_phone = $applicant_data['phone'] ?? '';
                            
                            // Insert applicant
                            $stmt = $pdo->prepare("
                                INSERT INTO applicants 
                                (order_id, applicant_no, applicant_email, applicant_phone, visa_status) 
                                VALUES (?, ?, ?, ?, 'submitted')
                            ");
                            $stmt->execute([$real_order_id, $applicant_no, $applicant_email, $applicant_phone]);
                            
                            $applicant_id = $pdo->lastInsertId();
                            
                            // Save answers to database
                            if (isset($applicant_data['answers'])) {
                                foreach ($applicant_data['answers'] as $question_id => $answer) {
                                    // Get question details
                                    $stmt = $pdo->prepare("SELECT field_type FROM country_questions WHERE id = ?");
                                    $stmt->execute([$question_id]);
                                    $question = $stmt->fetch();
                                    $field_type = $question['field_type'] ?? 'text';
                                    
                                    // Check if it's a file path
                                    if (strpos($answer, 'fetch_file.php') !== false || 
                                        strpos($answer, '.jpg') !== false || 
                                        strpos($answer, '.jpeg') !== false || 
                                        strpos($answer, '.png') !== false || 
                                        strpos($answer, '.pdf') !== false) {
                                        // It's a file - store in applicant_files
                                        $stmt = $pdo->prepare("
                                            INSERT INTO applicant_files 
                                            (order_id, applicant_id, question_id, file_path) 
                                            VALUES (?, ?, ?, ?)
                                        ");
                                        $stmt->execute([$real_order_id, $applicant_id, $question_id, $answer]);
                                        
                                        // Also store reference in applicant_answers
                                        $stmt = $pdo->prepare("
                                            INSERT INTO applicant_answers 
                                            (order_id, applicant_id, question_id, answer_type, answer_text) 
                                            VALUES (?, ?, ?, 'file', ?)
                                        ");
                                        $stmt->execute([$real_order_id, $applicant_id, $question_id, $answer]);
                                    } else {
                                        // It's text/date/select answer
                                        $stmt = $pdo->prepare("
                                            INSERT INTO applicant_answers 
                                            (order_id, applicant_id, question_id, answer_type, answer_text) 
                                            VALUES (?, ?, ?, ?, ?)
                                        ");
                                        $stmt->execute([$real_order_id, $applicant_id, $question_id, $field_type, $answer]);
                                    }
                                }
                            }
                        }
                    }
                }
                
                // 4. Clear session data
                unset($_SESSION['temp_application_data']);
                unset($_SESSION['razorpay_order_data']);
                
                // 5. Store successful payment in session for main page
                $_SESSION['payment_success_order_id'] = $real_order_id;
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'order_id' => $real_order_id
                ]);
            } else {
                // Signature verification failed
                throw new Exception('Payment signature verification failed');
            }
        } else {
            // Payment failed
            // NO database entries for failed payments
            
            // Clear session data
            if (isset($_SESSION['temp_application_data'])) {
                unset($_SESSION['temp_application_data']);
            }
            if (isset($_SESSION['razorpay_order_data'])) {
                unset($_SESSION['razorpay_order_data']);
            }
            
            // Store failed payment in session
            $_SESSION['payment_failed_temp_order_id'] = $temp_order_id;
            
            echo json_encode([
                'success' => false,
                'message' => 'Payment failed'
            ]);
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Payment verification error: " . $e->getMessage());
        
        // Clear session on error
        if (isset($_SESSION['temp_application_data'])) {
            unset($_SESSION['temp_application_data']);
        }
        
        echo json_encode([
            'success' => false,
            'message' => 'Payment verification error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>