<?php
session_start();
require 'db.php';

$order_id = $_GET['order_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Failed - Visa Application</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --danger: #dc3545;
            --primary: #4361ee;
            --light: #f8f9fa;
            --dark: #1a1b26;
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
            background: linear-gradient(135deg, #dc3545 0%, #e35d6a 100%);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .failed-container {
            width: 100%;
            max-width: 500px;
        }

        .failed-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--box-shadow);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .failed-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--danger), #e35d6a, var(--danger));
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
        }

        .failed-icon {
            width: 100px;
            height: 100px;
            background: var(--danger);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            color: white;
            font-size: 48px;
            animation: pulse 2s infinite;
        }

        .failed-title {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--danger);
        }

        .failed-message {
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .order-id {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 2px dashed #dc3545;
        }

        .possible-reasons {
            background: #fff5f5;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
            border: 1px solid #fcc2c7;
        }

        .possible-reasons h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: var(--danger);
        }

        .reasons-list {
            list-style: none;
        }

        .reasons-list li {
            margin-bottom: 10px;
            padding-left: 25px;
            position: relative;
        }

        .reasons-list li::before {
            content: '⚠';
            position: absolute;
            left: 0;
            color: var(--danger);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .action-button {
            flex: 1;
            padding: 16px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: none;
            text-decoration: none;
        }

        .retry-btn {
            background: var(--danger);
            color: white;
        }

        .retry-btn:hover {
            background: #e35d6a;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(220, 53, 69, 0.3);
        }

        .home-btn {
            background: #f8f9fa;
            color: var(--dark);
            border: 1px solid #e9ecef;
        }

        .home-btn:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .contact-support {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .contact-support p {
            color: #666;
            margin-bottom: 10px;
        }

        .support-email {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }

        .support-email:hover {
            text-decoration: underline;
        }

        @keyframes shimmer {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @media (max-width: 576px) {
            .failed-card {
                padding: 25px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .failed-title {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="failed-container">
        <div class="failed-card">
            <div class="failed-icon">
                <i class="fas fa-times"></i>
            </div>
            
            <h1 class="failed-title">Payment Failed</h1>
            <p class="failed-message">
                We were unable to process your payment. Please try again or contact support.
            </p>
            
            <?php if ($order_id): ?>
            <div class="order-id">
                Order ID: #<?php echo $order_id; ?>
            </div>
            <?php endif; ?>
            
            <div class="possible-reasons">
                <h3>Possible reasons for failure:</h3>
                <ul class="reasons-list">
                    <li>Insufficient funds in your account</li>
                    <li>Incorrect card details entered</li>
                    <li>Card issuer declined the transaction</li>
                    <li>Network or technical issues</li>
                    <li>Payment gateway timeout</li>
                </ul>
            </div>
            
            <div class="action-buttons">
                <?php 
                // Try to get temp order ID from session if not passed in URL
                $temp_order_id = $order_id ?: ($_SESSION['payment_failed_temp_order_id'] ?? '');
                
                if ($temp_order_id): ?>
                <a href="payment_page.php?temp_order_id=<?php echo $temp_order_id; ?>" class="action-button retry-btn">
                    <i class="fas fa-redo"></i>
                    Try Again
                </a>
                <?php else: ?>
                <button class="action-button retry-btn" onclick="window.history.back()">
                    <i class="fas fa-redo"></i>
                    Go Back
                </button>
                <?php endif; ?>
                
                <a href="index.php" class="action-button home-btn">
                    <i class="fas fa-home"></i>
                    Back to Home
                </a>
            </div>
            
            <div class="contact-support">
                <p>Need help? Contact our support team:</p>
                <a href="mailto:support@visaservice.com" class="support-email">
                    <i class="fas fa-envelope"></i>
                    support@visaservice.com
                </a>
            </div>
        </div>
    </div>
</body>
</html>