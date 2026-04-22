<?php
session_start();
require 'db.php';

$order_id = $_GET['order_id'] ?? 0;

if (!$order_id) {
    // Try to get from session
    $order_id = $_SESSION['payment_success_order_id'] ?? 0;
}

if (!$order_id) {
    header('Location: index.php');
    exit;
}

// Get order details
$stmt = $pdo->prepare("
    SELECT vo.*, c.country_name 
    FROM visa_orders vo 
    JOIN countries c ON vo.country_id = c.id 
    WHERE vo.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php');
    exit;
}

// Get payment details
$stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$order_id]);
$payment = $stmt->fetch();

// Generate invoice number
$invoice_number = 'INV-' . date('Ymd') . '-' . $order_id;

// Get applicant count
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM applicants WHERE order_id = ?");
$stmt->execute([$order_id]);
$applicant_count = $stmt->fetch();
$total_people = $applicant_count['total'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Successful - Visa Application</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --success: #28a745;
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-container {
            width: 100%;
            max-width: 600px;
        }

        .success-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--box-shadow);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .success-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--success), #20c997, var(--success));
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: var(--success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            color: white;
            font-size: 48px;
            animation: bounce 2s infinite;
        }

        .success-title {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--success);
        }

        .success-message {
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .order-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-label {
            color: #666;
            font-weight: 500;
        }

        .info-value {
            font-weight: 600;
            color: var(--dark);
        }

        .invoice-section {
            margin: 30px 0;
            padding: 20px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 12px;
            color: white;
        }

        .invoice-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .invoice-number {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            letter-spacing: 2px;
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
            color: inherit;
        }

        .download-btn {
            background: var(--primary);
            color: white;
        }

        .download-btn:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
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

        .whats-next {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e9ecef;
        }

        .whats-next h3 {
            font-size: 20px;
            margin-bottom: 20px;
            color: var(--dark);
        }

        .steps {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .step {
            flex: 1;
            min-width: 150px;
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .step-icon {
            width: 50px;
            height: 50px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 20px;
        }

        .step h4 {
            font-size: 16px;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .step p {
            font-size: 14px;
            color: #666;
            line-height: 1.4;
        }

        @keyframes shimmer {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-10px) scale(1.05); }
        }

        @media (max-width: 576px) {
            .success-card {
                padding: 25px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .steps {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            
            <h1 class="success-title">Payment Successful!</h1>
            <p class="success-message">
                Thank you for your payment. Your visa application has been submitted successfully.
            </p>
            
            <div class="order-info">
                <div class="info-row">
                    <span class="info-label">Order ID:</span>
                    <span class="info-value">#<?php echo $order_id; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Country:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['country_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Applicants:</span>
                    <span class="info-value"><?php echo $total_people; ?> person(s)</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Date:</span>
                    <span class="info-value"><?php echo date('d M Y, h:i A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Amount Paid:</span>
                    <span class="info-value"><?php echo $order['currency'] . ' ' . $order['total_amount']; ?></span>
                </div>
                <?php if ($payment && $payment['provider_payment_id']): ?>
                <div class="info-row">
                    <span class="info-label">Transaction ID:</span>
                    <span class="info-value"><?php echo substr($payment['provider_payment_id'], 0, 15) . '...'; ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="invoice-section">
                <h3 class="invoice-title">Download Invoice</h3>
                <div class="invoice-number"><?php echo $invoice_number; ?></div>
                <p>Keep this invoice for your records and tax purposes.</p>
            </div>
            
            <div class="action-buttons">
                <a href="generate_invoice.php?order_id=<?php echo $order_id; ?>" class="action-button download-btn" target="_blank">
                    <i class="fas fa-download"></i>
                    Download Invoice
                </a>
                <a href="index.php" class="action-button home-btn">
                    <i class="fas fa-home"></i>
                    Back to Home
                </a>
            </div>
            
            <div class="whats-next">
                <h3>What happens next?</h3>
                <div class="steps">
                    <div class="step">
                        <div class="step-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h4>Confirmation Email</h4>
                        <p>You'll receive a confirmation email within 24 hours</p>
                    </div>
                    <div class="step">
                        <div class="step-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4>Processing</h4>
                        <p>Your application will be processed in 3-5 business days</p>
                    </div>
                    <div class="step">
                        <div class="step-icon">
                            <i class="fas fa-passport"></i>
                        </div>
                        <h4>Visa Decision</h4>
                        <p>You'll be notified once your visa is approved</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function downloadInvoice() {
            // Create invoice content based on your database structure
            const invoiceContent = `
                <html>
                <head>
                    <title>Invoice <?php echo $invoice_number; ?></title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 40px; }
                        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                        .invoice-title { font-size: 24px; font-weight: bold; }
                        .invoice-number { font-size: 18px; color: #666; }
                        .details { margin: 30px 0; }
                        .row { display: flex; justify-content: space-between; margin: 12px 0; padding: 8px 0; border-bottom: 1px solid #eee; }
                        .label { color: #666; }
                        .value { font-weight: bold; }
                        .total-row { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 20px; }
                        .footer { margin-top: 50px; text-align: center; color: #666; font-size: 14px; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1 class="invoice-title">TAX INVOICE</h1>
                        <div class="invoice-number"><?php echo $invoice_number; ?></div>
                        <div>Visa Application Service</div>
                    </div>
                    <div class="details">
                        <div class="row">
                            <span class="label">Order ID:</span>
                            <span class="value">#<?php echo $order_id; ?></span>
                        </div>
                        <div class="row">
                            <span class="label">Invoice Date:</span>
                            <span class="value"><?php echo date('d M Y'); ?></span>
                        </div>
                        <div class="row">
                            <span class="label">Country:</span>
                            <span class="value"><?php echo htmlspecialchars($order['country_name']); ?></span>
                        </div>
                        <div class="row">
                            <span class="label">Applicants:</span>
                            <span class="value"><?php echo $total_people; ?> person(s)</span>
                        </div>
                        <div class="row total-row">
                            <span class="label">Total Amount:</span>
                            <span class="value"><?php echo $order['currency'] . ' ' . $order['total_amount']; ?></span>
                        </div>
                        <div class="row">
                            <span class="label">Payment Status:</span>
                            <span class="value">Paid</span>
                        </div>
                        <?php if ($payment && $payment['provider_payment_id']): ?>
                        <div class="row">
                            <span class="label">Transaction ID:</span>
                            <span class="value"><?php echo $payment['provider_payment_id']; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="footer">
                        <p>Thank you for choosing our Visa Application Service!</p>
                        <p>This is a computer-generated invoice and does not require a signature.</p>
                    </div>
                </body>
                </html>
            `;
            
            // Create a blob and download
            const blob = new Blob([invoiceContent], { type: 'text/html' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'invoice-<?php echo $order_id; ?>.html';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            // Show success message
            alert('Invoice downloaded successfully!');
        }
    </script>
</body>
</html>