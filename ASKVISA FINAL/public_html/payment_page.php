<?php
session_start();
require 'db.php';
require_once 'csrf_helper.php';

// Generate CSRF token
$csrf_token = generate_csrf_token();

$temp_order_id = $_GET['temp_order_id'] ?? '';

if (!$temp_order_id || !isset($_SESSION['temp_application_data'])) {
    header('Location: index.php');
    exit;
}

// Get order details from session (NO database query)
$order_data = $_SESSION['temp_application_data'];
$country_name = $order_data['country_name'] ?? '';
$payment_amount = $order_data['payment_amount'] ?? 0;
$currency = $order_data['currency'] ?? 'INR';
$order_contact_email = $order_data['order_contact_email'] ?? '';
$order_contact_phone = $order_data['order_contact_phone'] ?? '';
$total_people = $order_data['total_people'] ?? 1;
$country_id = $order_data['country_id'] ?? 0;

// Store temp order ID in session for payment callback
$_SESSION['temp_order_id'] = $temp_order_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment - Visa Application</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --success: #4cc9f0;
            --danger: #f72585;
            --dark: #1a1b26;
            --light: #f8f9fa;
            --border-radius: 16px;
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .payment-container {
            width: 100%;
            max-width: 500px;
        }

        .payment-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--box-shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .payment-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--success), var(--primary));
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
        }

        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .payment-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 36px;
            animation: bounce 2s infinite;
        }

        .payment-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .payment-header p {
            color: #666;
            font-size: 16px;
        }

        .order-details {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-label {
            color: #666;
            font-weight: 500;
        }

        .detail-value {
            font-weight: 600;
            color: var(--dark);
        }

        .amount-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin: 25px 0;
        }

        .amount-label {
            font-size: 18px;
            font-weight: 600;
        }

        .amount-value {
            font-size: 32px;
            font-weight: 700;
        }

        .payment-methods {
            margin-bottom: 30px;
        }

        .payment-methods h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: var(--dark);
        }

        .methods-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .method-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .method-item:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .method-icon {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .method-name {
            font-size: 12px;
            font-weight: 600;
        }

        .pay-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }

        .pay-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .pay-button:hover::before {
            left: 100%;
        }

        .pay-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
        }

        .pay-button:active {
            transform: translateY(0);
        }

        .back-button {
            width: 100%;
            padding: 15px;
            background: #f8f9fa;
            color: #666;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .back-button:hover {
            background: #e9ecef;
        }

        .secure-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            color: #28a745;
            font-size: 14px;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes shimmer {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 576px) {
            .payment-card {
                padding: 25px;
            }
            
            .methods-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .payment-header h1 {
                font-size: 24px;
            }
            
            .amount-value {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-card">
            <div class="payment-header">
                <div class="payment-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h1>Complete Payment</h1>
                <p>Secure payment for your visa application</p>
            </div>

            <div class="order-details">
                <div class="detail-row">
                    <span class="detail-label">Temporary Order ID:</span>
                    <span class="detail-value">#<?php echo $temp_order_id; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Country:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($country_name); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Applicants:</span>
                    <span class="detail-value"><?php echo $total_people; ?> person(s)</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Contact Email:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order_contact_email); ?></span>
                </div>
            </div>

            <div class="amount-row">
                <span class="amount-label">Total Amount:</span>
                <span class="amount-value"><?php echo $currency . ' ' . $payment_amount; ?></span>
            </div>

            <button class="pay-button" onclick="initiateRazorpayPayment()">
                <i class="fas fa-lock"></i>
                Pay Now
            </button>

            <button class="back-button" onclick="window.location.href='index.php?return_from_payment=1'">
                <i class="fas fa-arrow-left"></i>
                Back to Application
            </button>

            <div class="secure-info">
                <i class="fas fa-shield-alt"></i>
                <span>100% Secure Payment • SSL Encrypted</span>
            </div>
        </div>
    </div>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <script>
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }

        async function initiateRazorpayPayment() {
            showLoading();
            
            try {
                // Create Razorpay order
                const response = await fetch('create_razorpay_order_new.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        temp_order_id: <?php echo json_encode($temp_order_id); ?>,
                        amount: <?php echo $payment_amount; ?>,
                        currency: <?php echo json_encode($currency); ?>,
                        email: <?php echo json_encode($order_contact_email); ?>,
                        phone: <?php echo json_encode($order_contact_phone); ?>,
                        country_id: <?php echo $country_id; ?>,
                        csrf_token: '<?php echo $csrf_token; ?>' // Add CSRF token
                    })
                });

                const data = await response.json();
                
                if (!data.success) {
                    hideLoading();
                    alert(data.message || 'Failed to create payment order');
                    return;
                }

                // Configure Razorpay checkout
                const options = {
                    key: data.key,
                    amount: data.amount * 100, // Amount in paise
                    currency: data.currency,
                    name: 'Visa Application Service',
                    description: data.description,
                    order_id: data.razorpay_order_id,
                    handler: function(response) {
                        // Payment successful
                        handlePaymentResponse(response, true);
                    },
                    prefill: {
                        name: 'Applicant',
                        email: data.customer_email,
                        contact: data.customer_phone
                    },
                    notes: {
                        order_id: data.temp_order_id
                    },
                    theme: {
                        color: '#4361ee'
                    }
                };

                const rzp = new Razorpay(options);
                
                rzp.on('payment.failed', function(response) {
                    handlePaymentResponse(response, false);
                });

                hideLoading();
                rzp.open();
                
            } catch (error) {
                hideLoading();
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            }
        }

        function handlePaymentResponse(response, isSuccess) {
            showLoading();
            
            const formData = new FormData();
            formData.append('razorpay_payment_id', response.razorpay_payment_id || '');
            formData.append('razorpay_order_id', response.razorpay_order_id || (response.error && response.error.metadata ? response.error.metadata.order_id : ''));
            formData.append('razorpay_signature', response.razorpay_signature || '');
            formData.append('temp_order_id', <?php echo json_encode($temp_order_id); ?>);
            formData.append('is_success', isSuccess ? '1' : '0');
            formData.append('csrf_token', '<?php echo $csrf_token; ?>'); // Add CSRF token
            
            fetch('verify_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                hideLoading();
                if (isSuccess && data.success) {
                    window.location.href = 'payment_successfull.php?order_id=' + data.order_id;
                } else {
                    window.location.href = 'payment_failed.php';
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                alert('Payment verification failed. Please contact support.');
            });
        }
    </script>
</body>
</html>