<?php
session_start();
require_once 'db.php';
require 'razorpay_config.php';

// Initialize variables with defaults
$num_applicants = $_SESSION['total_people'] ?? $_GET['applicants'] ?? 1;
$country = $_SESSION['country_name'] ?? $_GET['country'] ?? '';
$order_id = $_SESSION['current_order_id'] ?? $_GET['order_id'] ?? 'CHAT_' . time();
$email_id = $_SESSION['order_contact_email'] ?? $_GET['email'] ?? '';
$phone = $_SESSION['order_contact_phone'] ?? $_GET['phone'] ?? '';
$total_amount = $_SESSION['order_amount'] ?? $_GET['amount'] ?? ($num_applicants * 100);

// Store order ID in session for verification
$_SESSION['current_order_id'] = $order_id;
$_SESSION['order_amount'] = $total_amount;

// Convert amount to Indian Rupees (1 USD = 83 INR)
$total_amount_inr = $total_amount * 83;

// Get applicant details from session if available
$applicants_info = [];
if (isset($_SESSION['collected_info'])) {
    foreach ($_SESSION['collected_info'] as $key => $applicant) {
        if (strpos($key, 'applicant_') === 0) {
            $applicant_num = str_replace('applicant_', '', $key);
            $applicants_info[] = [
                'number' => $applicant_num,
                'email' => $applicant['email'] ?? '',
                'phone' => $applicant['phone'] ?? ''
            ];
        }
    }
}

// Function to create Razorpay order
function createRazorpayOrder($amount, $currency, $receipt, $notes, $key_id, $key_secret)
{
    $url = 'https://api.razorpay.com/v1/orders';
    $data = [
        'amount' => round($amount * 100), // Convert to paise
        'currency' => $currency,
        'receipt' => $receipt,
        'notes' => $notes
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_USERPWD, $key_id . ':' . $key_secret);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['error' => true, 'message' => 'CURL Error: ' . $error];
    }

    $result = json_decode($response, true);

    if ($httpCode !== 200) {
        return [
            'error' => true,
            'message' => 'Razorpay API Error: ' . ($result['error']['description'] ?? 'Unknown error'),
            'http_code' => $httpCode
        ];
    }

    return $result;
}

// Create Razorpay order
$razorpayOrder = null;
$error = null;

try {
    // First, save order to database
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO visa_orders 
                (id, email, phone, country, applicants, amount_usd, amount_inr, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ON DUPLICATE KEY UPDATE 
                email = VALUES(email),
                phone = VALUES(phone),
                country = VALUES(country),
                applicants = VALUES(applicants),
                amount_usd = VALUES(amount_usd),
                amount_inr = VALUES(amount_inr),
                updated_at = NOW()");

            $stmt->execute([
                $order_id,
                $email_id,
                $phone,
                $country,
                $num_applicants,
                $total_amount,
                $total_amount_inr
            ]);
        } catch (PDOException $e) {
            error_log("Database save failed: " . $e->getMessage());
        }
    }

    // Create Razorpay order
    $razorpayOrder = createRazorpayOrder(
        $total_amount_inr,
        'INR',
        'order_' . $order_id,
        [
            'order_id' => $order_id,
            'country' => $country,
            'applicants' => $num_applicants,
            'email' => $email_id,
            'phone' => $phone
        ],
        $razorpay_config['key_id'],
        $razorpay_config['key_secret']
    );

    if (isset($razorpayOrder['error'])) {
        $error = $razorpayOrder['message'];
    } elseif ($razorpayOrder && isset($razorpayOrder['id'])) {
        $_SESSION['razorpay_order_id'] = $razorpayOrder['id'];

        // Update database with Razorpay order ID
        if (isset($pdo)) {
            try {
                $stmt = $pdo->prepare("UPDATE visa_orders SET 
                    razorpay_order_id = ?,
                    razorpay_amount = ?,
                    razorpay_currency = ?,
                    updated_at = NOW()
                    WHERE id = ?");
                $stmt->execute([
                    $razorpayOrder['id'],
                    $razorpayOrder['amount'],
                    $razorpayOrder['currency'],
                    $order_id
                ]);
            } catch (PDOException $e) {
                error_log("Database update failed: " . $e->getMessage());
            }
        }
    } else {
        $error = "Failed to create payment order. Please try again.";
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Visa Fee - Secure Payment</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        :root {
            --primary: #3a36e0;
            --primary-light: #6d69f2;
            --secondary: #10b981;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --gray-light: #e5e7eb;
            --border-radius: 16px;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            --shadow-hover: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--dark);
        }

        .payment-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-hover);
            width: 100%;
            max-width: 1000px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 30px 40px;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .back-btn {
            background: none;
            border: none;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            margin-top: 30px;
            padding: 12px 0;
            transition: color 0.3s;
        }

        .back-btn:hover {
            color: var(--primary);
        }

        .content {
            display: flex;
            min-height: 550px;
        }

        .application-summary {
            flex: 1;
            padding: 40px 30px;
            background: var(--light);
            display: flex;
            flex-direction: column;
        }

        .payment-form {
            flex: 1.2;
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 25px;
            color: var(--dark);
            position: relative;
            padding-bottom: 10px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--primary);
            border-radius: 3px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-item {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary);
        }

        .summary-label {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .summary-value {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }

        .total-amount {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            margin-top: auto;
            box-shadow: var(--shadow);
        }

        .total-label {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .total-value {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .total-note {
            font-size: 14px;
            opacity: 0.8;
            margin-top: 10px;
        }

        .payment-methods {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .payment-method {
            flex: 1;
            padding: 20px;
            border: 2px solid var(--gray-light);
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            border-color: var(--primary-light);
            transform: translateY(-3px);
        }

        .payment-method.active {
            border-color: var(--primary);
            background-color: rgba(58, 54, 224, 0.05);
        }

        .payment-method i {
            font-size: 32px;
            margin-bottom: 10px;
            color: var(--primary);
        }

        #payButton {
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, var(--secondary) 0%, #0ca678 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 20px;
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
        }

        #payButton:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(16, 185, 129, 0.3);
        }

        #payButton:disabled {
            background: var(--gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .secure-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid var(--gray-light);
            color: var(--gray);
            font-size: 14px;
        }

        .progress-indicator {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }

        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--gray);
            margin-bottom: 8px;
            transition: all 0.3s;
        }

        .progress-step.active .step-number {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 5px 15px rgba(58, 54, 224, 0.3);
        }

        .progress-step.completed .step-number {
            background: var(--secondary);
            border-color: var(--secondary);
            color: white;
        }

        .step-label {
            font-size: 14px;
            color: var(--gray);
            font-weight: 500;
        }

        .progress-step.active .step-label {
            color: var(--primary);
            font-weight: 600;
        }

        .progress-bar {
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--gray-light);
            z-index: 1;
        }

        .progress-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background: var(--primary);
            width: 50%;
            transition: width 0.5s ease;
        }

        .error-message {
            background: #fef2f2;
            border: 2px solid #fecaca;
            color: #dc2626;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .error-message i {
            font-size: 24px;
            flex-shrink: 0;
        }

        .error-message h4 {
            margin: 0 0 10px 0;
            font-size: 18px;
        }

        .error-message p {
            margin: 0;
            line-height: 1.6;
        }

        .test-info {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #bae6fd;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }

        .test-info h4 {
            color: #0369a1;
            margin-bottom: 20px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .test-info .test-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .test-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .test-card.success {
            border-left: 4px solid #10b981;
        }

        .test-card.failure {
            border-left: 4px solid #ef4444;
        }

        .test-card h5 {
            margin: 0 0 10px 0;
            font-size: 16px;
        }

        .test-card.success h5 {
            color: #10b981;
        }

        .test-card.failure h5 {
            color: #ef4444;
        }

        .card-number {
            font-family: monospace;
            font-size: 16px;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .card-details {
            font-size: 14px;
            color: #6b7280;
        }

        @media (max-width: 900px) {
            .content {
                flex-direction: column;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .test-info .test-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="payment-container">
        <div class="header">
            <div class="header-content">
                <h1><i class="fas fa-globe-americas"></i> Visa Fee Payment</h1>
                <p>Complete your visa application with our secure payment gateway</p>
            </div>
        </div>

        <div class="content">
            <!-- Left: Application Summary -->
            <div class="application-summary">
                <h2 class="section-title">Application Details</h2>

                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">Application ID</div>
                        <div class="summary-value" id="order_Id"><?php echo htmlspecialchars($order_id); ?></div>
                    </div>

                    <div class="summary-item">
                        <div class="summary-label">Destination Country</div>
                        <div class="summary-value" id="countryDisplay">
                            <i class="fas fa-flag"></i> <?php echo htmlspecialchars($country); ?>
                        </div>
                    </div>

                    <div class="summary-item">
                        <div class="summary-label">Number of Applicants</div>
                        <div class="summary-value" id="applicantsCount">
                            <i class="fas fa-users"></i> <?php echo $num_applicants; ?> person<?php echo $num_applicants > 1 ? 's' : ''; ?>
                        </div>
                    </div>

                    <div class="summary-item">
                        <div class="summary-label">Visa Type</div>
                        <div class="summary-value" id="visaTypeDisplay">
                            <i class="fas fa-passport"></i> TDAC
                        </div>
                    </div>
                </div>

                <!-- Contact Details -->
                <?php if (!empty($applicants_info)): ?>
                    <div style="margin: 25px 0;">
                        <h4 style="color: var(--primary); margin-bottom: 15px; font-size: 16px;">
                            <i class="fas fa-user-friends"></i> Contact Details
                        </h4>
                        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: var(--shadow);">
                            <div style="text-align: center;">
                                <div style="font-size: 24px; color: var(--primary); margin-bottom: 10px;">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <h4 style="color: var(--dark); margin-bottom: 15px;">Contact Information</h4>

                                <div style="background: var(--light); border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                        <i class="fas fa-envelope" style="color: var(--primary);"></i>
                                        <div>

                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($email_id); ?></div>
                                        </div>
                                    </div>

                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <i class="fas fa-phone" style="color: var(--primary);"></i>
                                        <div>

                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($phone); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: inline-flex; align-items: center; gap: 8px; background: var(--primary-light); color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px;">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo $num_applicants; ?> applicant<?php echo $num_applicants > 1 ? 's' : ''; ?></span>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endif; ?>

                <div class="total-amount">
                    <div class="total-label">Total Amount to Pay</div>
                    <div class="total-value" id="total_amount">
                        ₹<?php echo number_format($total_amount_inr, 2); ?>
                        <div style="font-size: 16px; opacity: 0.8; margin-top: 5px;">
                            (Approx $<?php echo number_format($total_amount, 2); ?> USD)
                        </div>
                    </div>
                    <div class="total-note">Includes all processing fees</div>
                </div>

                <div style="margin-top: 30px;">
                    <h4 style="color: var(--primary); margin-bottom: 15px; font-size: 16px;"><i class="fas fa-check-circle"></i> What's Included</h4>
                    <ul style="color: var(--gray); line-height: 1.8; padding-left: 20px;">
                        <li>Visa application processing fee</li>
                        <li>Embassy submission service</li>
                        <li>Application tracking dashboard</li>
                        <li>Email support for 30 days</li>
                        <li>Document verification</li>
                    </ul>
                </div>
            </div>

            <!-- Right: Payment Form -->
            <div class="payment-form">
                <div class="progress-indicator">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="progress-step completed">
                        <div class="step-number"><i class="fas fa-check"></i></div>
                        <div class="step-label">Application</div>
                    </div>
                    <div class="progress-step active">
                        <div class="step-number">2</div>
                        <div class="step-label">Payment</div>
                    </div>
                    <div class="progress-step">
                        <div class="step-number">3</div>
                        <div class="step-label">Confirmation</div>
                    </div>
                </div>

                <h2 class="section-title">Payment Method</h2>

                <!-- Display errors if any -->
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <h4>Payment Setup Error</h4>
                            <p><?php echo htmlspecialchars($error); ?></p>
                            <p style="margin-top: 10px;">
                                Please <a href="javascript:location.reload()" style="color: var(--primary); font-weight: 600;">refresh the page</a> or try again later.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>



                <!-- Payment Button -->
                <div id="paymentSection">
                    <?php if (!$error && isset($razorpayOrder['id'])): ?>
                        <button id="payButton" onclick="processRazorpayPayment()">
                            <i class="fas fa-lock"></i> Pay Securely ₹<?php echo number_format($total_amount_inr, 2); ?>
                        </button>

                        <div class="secure-info">
                            <i class="fas fa-shield-alt" style="color: var(--secondary);"></i>
                            <span>Powered by Razorpay • 256-bit SSL encryption</span>
                        </div>
                    <?php elseif (!$error): ?>
                        <div style="text-align: center; padding: 30px; background: #f9fafb; border-radius: 12px;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: var(--primary); margin-bottom: 15px;"></i>
                            <p>Setting up payment gateway...</p>
                        </div>
                    <?php endif; ?>

                    <div id="paymentError" style="color: #dc2626; margin-top: 15px; padding: 15px; background: #fef2f2; border-radius: 8px; display: none;"></div>
                </div>

                <!-- Success Section (initially hidden) -->
                <div id="successSection" style="display: none;">
                    <div class="success-screen">
                        <div class="success-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <h2 style="color: var(--secondary); margin-bottom: 15px; font-size: 28px;">Payment Successful!</h2>
                        <p style="color: var(--gray); margin-bottom: 25px; font-size: 18px;">
                            Your visa application has been submitted successfully.
                        </p>

                        <div class="success-details">
                            <div class="detail-row">
                                <span class="detail-label">Application ID</span>
                                <span class="detail-value" id="successOrderId"><?php echo htmlspecialchars($order_id); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Destination</span>
                                <span class="detail-value"><?php echo htmlspecialchars($country); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Applicants</span>
                                <span class="detail-value"><?php echo $num_applicants; ?> person<?php echo $num_applicants > 1 ? 's' : ''; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Contact Email</span>
                                <span class="detail-value"><?php echo htmlspecialchars($email_id); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Amount Paid</span>
                                <span class="detail-value">₹<?php echo number_format($total_amount_inr, 2); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Payment Date</span>
                                <span class="detail-value"><?php echo date('F j, Y, h:i A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Status</span>
                                <span class="status-badge">Submitted for Processing</span>
                            </div>
                        </div>

                        <div class="button-group">
                            <button class="btn-primary" onclick="goToDashboard()">
                                <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                            </button>
                            <button class="btn-secondary" onclick="printReceipt()">
                                <i class="fas fa-print"></i> Print Receipt
                            </button>
                        </div>

                        <p style="color: var(--gray); margin-top: 30px; font-size: 14px;">
                            A confirmation email has been sent to <strong><?php echo htmlspecialchars($email_id); ?></strong>
                        </p>
                    </div>
                </div>

                <button class="back-btn" onclick="goBack()">
                    <i class="fas fa-arrow-left"></i> Back to Application Form
                </button>
            </div>
        </div>
    </div>

    <script>
        // Razorpay configuration
        const razorpayConfig = {
            key: "<?php echo $razorpay_config['key_id']; ?>",
            amount: "<?php echo isset($razorpayOrder['amount']) ? $razorpayOrder['amount'] : ($total_amount_inr * 100); ?>",
            currency: "<?php echo isset($razorpayOrder['currency']) ? $razorpayOrder['currency'] : 'INR'; ?>",
            order_id: "<?php echo isset($razorpayOrder['id']) ? $razorpayOrder['id'] : ''; ?>",
            name: "Ask Visa Portal",
            description: "Visa Application for <?php echo htmlspecialchars($country); ?>",
            prefill: {
                name: "<?php echo htmlspecialchars($email_id ? explode('@', $email_id)[0] : 'Customer'); ?>",
                email: "<?php echo htmlspecialchars($email_id); ?>",
                contact: "<?php echo htmlspecialchars($phone); ?>"
            },
            notes: {
                order_id: "<?php echo $order_id; ?>",
                applicants: "<?php echo $num_applicants; ?>",
                country: "<?php echo htmlspecialchars($country); ?>"
            },
            theme: {
                color: "#3a36e0"
            },
            modal: {
                ondismiss: function() {
                    console.log('Payment modal closed');
                    // Re-enable the payment button if modal is dismissed
                    document.getElementById('payButton').disabled = false;
                    document.getElementById('payButton').innerHTML = '<i class="fas fa-lock"></i> Pay Securely ₹<?php echo number_format($total_amount_inr, 2); ?>';
                }
            },
            handler: function(response) {
                // Show processing message
                document.getElementById('paymentSection').innerHTML = `
                    <div style="text-align: center; padding: 40px 20px;">
                        <div style="color: var(--primary); font-size: 48px; margin-bottom: 20px;">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                        <h3 style="color: var(--dark); margin-bottom: 10px;">Verifying Payment...</h3>
                        <p style="color: var(--gray);">Please wait while we confirm your payment</p>
                        <div style="margin-top: 20px; padding: 15px; background: #f8fafc; border-radius: 8px; font-family: monospace; font-size: 14px;">
                            Payment ID: ${response.razorpay_payment_id}
                        </div>
                    </div>
                `;

                // Verify payment with your server
                verifyPayment(response);
            }
        };

        // Process Razorpay payment
        function processRazorpayPayment() {
            const payButton = document.getElementById('payButton');

            // Check if Razorpay order ID exists
            if (!razorpayConfig.order_id) {
                showError('Payment setup incomplete. Please refresh the page.');
                return;
            }

            // Disable button and show loading
            payButton.disabled = true;
            payButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Opening Payment...';

            // Initialize Razorpay
            const rzp = new Razorpay(razorpayConfig);

            // Open payment modal
            rzp.open();

            // Reset button state if modal doesn't open immediately
            setTimeout(() => {
                if (!payButton.disabled) {
                    payButton.disabled = false;
                    payButton.innerHTML = '<i class="fas fa-lock"></i> Pay Securely ₹<?php echo number_format($total_amount_inr, 2); ?>';
                }
            }, 3000);
        }

        // Verify payment with server
        // Verify payment with server
        async function verifyPayment(response) {
            try {
                // Show processing message
                document.getElementById('paymentSection').innerHTML = `
            <div style="text-align: center; padding: 40px 20px;">
                <div style="color: var(--primary); font-size: 48px; margin-bottom: 20px;">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
                <h3 style="color: var(--dark); margin-bottom: 10px;">Verifying Payment...</h3>
                <p style="color: var(--gray);">Please wait while we confirm your payment</p>
                <div style="margin-top: 20px; padding: 15px; background: #f8fafc; border-radius: 8px; font-family: monospace; font-size: 14px;">
                    Payment ID: ${response.razorpay_payment_id}<br>
                    Order ID: ${response.razorpay_order_id}
                </div>
            </div>
        `;

                // Prepare verification data
                const verifyData = {
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_signature: response.razorpay_signature,
                    order_id: "<?php echo $order_id; ?>",
                    amount: "<?php echo $total_amount_inr; ?>",
                    email: "<?php echo htmlspecialchars($email_id); ?>"
                };

                console.log('Sending verification:', verifyData);

                // Verify payment with server
                const verifyResponse = await fetch('verify_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(verifyData)
                });

                // Check if response is OK
                if (!verifyResponse.ok) {
                    throw new Error(`Server error: ${verifyResponse.status} ${verifyResponse.statusText}`);
                }

                const result = await verifyResponse.json();
                console.log('Verification result:', result);

                if (result.success) {
                    // Payment successful - redirect to success page
                    window.location.href = 'valid_palak.php?payment=success&order_id=<?php echo $order_id; ?>&payment_id=' +
                        response.razorpay_payment_id + '&signature=' + response.razorpay_signature;
                } else {
                    // Payment verification failed
                    showVerificationError(result.message || 'Payment verification failed');
                }
            } catch (error) {
                console.error('Verification error:', error);
                showVerificationError('Network error: ' + error.message);
            }
        }

        // Show verification error
        function showVerificationError(message) {
            document.getElementById('paymentSection').innerHTML = `
        <div style="text-align: center; padding: 40px 20px;">
            <div style="color: #dc2626; font-size: 48px; margin-bottom: 20px;">
                <i class="fas fa-times-circle"></i>
            </div>
            <h3 style="color: #dc2626; margin-bottom: 10px;">Verification Failed</h3>
            <p style="color: var(--gray); margin-bottom: 20px;">${message}</p>
            <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <button onclick="retryVerification()" style="padding: 12px 30px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer;">
                    <i class="fas fa-redo"></i> Try Again
                </button>
                <button onclick="window.location.reload()" style="padding: 12px 30px; background: #6b7280; color: white; border: none; border-radius: 8px; cursor: pointer;">
                    <i class="fas fa-home"></i> Restart Payment
                </button>
                <button onclick="goBack()" style="padding: 12px 30px; background: white; color: var(--primary); border: 2px solid var(--primary); border-radius: 8px; cursor: pointer;">
                    <i class="fas fa-arrow-left"></i> Go Back
                </button>
            </div>
            <div style="margin-top: 30px; padding: 15px; background: #f3f4f6; border-radius: 8px; text-align: left;">
                <h4 style="color: var(--dark); margin-bottom: 10px;"><i class="fas fa-info-circle"></i> Next Steps:</h4>
                <ul style="text-align: left; margin: 0; padding-left: 20px; color: var(--gray);">
                    <li>Check your email for payment confirmation</li>
                    <li>Contact support if payment was deducted</li>
                    <li>Try payment again in a few minutes</li>
                </ul>
            </div>
        </div>
    `;
        }

        // Retry verification
        function retryVerification() {
            // You might want to implement a retry logic or redirect
            window.location.href = 'valid_palak.php?order_id=<?php echo $order_id; ?>';
        }

        // Show error message
        function showError(message) {
            const errorDiv = document.getElementById('paymentError');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';

            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 5000);
        }

        // Navigation functions
        function goToDashboard() {
            window.location.href = 'valid_palak.php?order_complete=1&order_id=<?php echo $order_id; ?>';
        }

        function goBack() {
            if (confirm('Are you sure you want to go back? Your payment information will not be saved.')) {
                window.history.back();
            }
        }

        // Print receipt
        function printReceipt() {
            const content = `
                <html>
                <head>
                    <title>Visa Payment Receipt</title>
                    <style>
                        body { 
                            font-family: 'Inter', Arial, sans-serif; 
                            padding: 40px; 
                            max-width: 600px;
                            margin: 0 auto;
                        }
                        .header { 
                            text-align: center; 
                            margin-bottom: 30px; 
                            border-bottom: 2px solid #3a36e0;
                            padding-bottom: 20px;
                        }
                        .details { 
                            margin: 30px 0; 
                            border: 1px solid #e5e7eb;
                            border-radius: 12px;
                            padding: 25px;
                        }
                        .detail-row { 
                            display: flex; 
                            justify-content: space-between; 
                            margin: 15px 0; 
                            padding-bottom: 10px;
                            border-bottom: 1px dashed #e5e7eb;
                        }
                        .detail-row:last-child { border-bottom: none; }
                        .thank-you { 
                            margin-top: 40px; 
                            text-align: center; 
                            font-style: italic;
                            color: #6b7280;
                        }
                        .logo {
                            text-align: center;
                            color: #3a36e0;
                            font-weight: bold;
                            font-size: 24px;
                            margin-bottom: 20px;
                        }
                        .total {
                            font-size: 24px;
                            font-weight: bold;
                            color: #10b981;
                            text-align: center;
                            margin: 20px 0;
                        }
                    </style>
                </head>
                <body>
                    <div class="logo">Ask Visa Portal</div>
                    <div class="header">
                        <h1>Payment Receipt</h1>
                        <p>Visa Application Fee</p>
                    </div>
                    <div class="details">
                        <div class="detail-row">
                            <strong>Application ID:</strong> <?php echo htmlspecialchars($order_id); ?>
                        </div>
                        <div class="detail-row">
                            <strong>Destination:</strong> <?php echo htmlspecialchars($country); ?>
                        </div>
                        <div class="detail-row">
                            <strong>Applicants:</strong> <?php echo $num_applicants; ?> person<?php echo $num_applicants > 1 ? 's' : ''; ?>
                        </div>
                        <div class="detail-row">
                            <strong>Contact Email:</strong> <?php echo htmlspecialchars($email_id); ?>
                        </div>
                        <div class="detail-row">
                            <strong>Date:</strong> ${new Date().toLocaleDateString()}
                        </div>
                        <div class="detail-row">
                            <strong>Time:</strong> ${new Date().toLocaleTimeString()}
                        </div>
                    </div>
                    <div class="total">Amount Paid: ₹<?php echo number_format($total_amount_inr, 2); ?></div>
                    <div class="thank-you">
                        <p>Thank you for your payment. Your visa application is now being processed.</p>
                        <p>You will receive updates via email at <?php echo htmlspecialchars($email_id); ?>.</p>
                        <p style="margin-top: 20px; font-size: 12px;">This is an electronic receipt. No signature required.</p>
                    </div>
                </body>
                </html>
            `;

            const win = window.open('', '_blank');
            win.document.write(content);
            win.document.close();
            win.print();
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-scroll to payment section if there's an error
            <?php if ($error): ?>
                document.querySelector('.payment-form').scrollIntoView({
                    behavior: 'smooth'
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>