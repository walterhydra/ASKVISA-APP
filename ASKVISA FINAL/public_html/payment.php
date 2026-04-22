<?php
session_start();
require 'db.php';

// Check if order exists
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    die('Invalid order ID');
}

$order_id = $_GET['order_id'];

// Fetch order details
try {
    $stmt = $pdo->prepare("SELECT 
        vo.id, 
        vo.email, 
        vo.phone,
        vo.payment_status,
        vo.total_amount,
        vo.currency,
        c.country_name,
        vo.created_at
        FROM visa_orders vo 
        JOIN countries c ON vo.country_id = c.id 
        WHERE vo.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        die('Order not found');
    }
} catch (Exception $e) {
    die('Error fetching order: ' . $e->getMessage());
}

// Handle payment simulation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_status = $_POST['payment_status'] ?? 'success';
    $transaction_id = 'TXN_' . strtoupper(uniqid()) . '_' . date('YmdHis');
    
    try {
        $pdo->beginTransaction();
        
        // Update order payment status
        $stmt = $pdo->prepare("UPDATE visa_orders SET payment_status = ? WHERE id = ?");
        $stmt->execute([$payment_status, $order_id]);
        
        // Insert payment record
        $stmt = $pdo->prepare("INSERT INTO payments (order_id, provider, provider_payment_id, amount, currency, status, payment_method, created_at) VALUES (?, 'demo', ?, ?, ?, ?, 'credit_card', NOW())");
        $stmt->execute([
            $order_id,
            $transaction_id,
            $order['total_amount'],
            $order['currency'],
            $payment_status
        ]);
        
        $pdo->commit();
        
        // Redirect to callback page
        header("Location: payment_callback.php?order_id=" . $order_id . "&status=" . $payment_status . "&transaction_id=" . $transaction_id);
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Payment processing failed: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Processing - Ask Visa</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --dark: #1a1b26;
            --light: #f8f9fa;
            --border-radius: 16px;
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        body.dark {
            --light: #1a1b26;
            --dark: #f8f9fa;
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--light);
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
            background: var(--light);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .payment-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .payment-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--success), var(--primary), var(--success));
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
        }

        .payment-icon {
            font-size: 48px;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }

        .payment-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .payment-header p {
            opacity: 0.9;
        }

        .payment-content {
            padding: 30px;
        }

        .order-summary {
            background: rgba(67, 97, 238, 0.05);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(67, 97, 238, 0.1);
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .summary-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .summary-label {
            color: var(--dark);
            opacity: 0.7;
        }

        .summary-value {
            font-weight: 600;
            color: var(--primary);
        }

        .amount-display {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: linear-gradient(135deg, rgba(76, 201, 240, 0.1) 0%, rgba(67, 97, 238, 0.1) 100%);
            border-radius: var(--border-radius);
            border: 2px solid var(--primary-light);
        }

        .amount-label {
            font-size: 14px;
            color: var(--dark);
            opacity: 0.7;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .amount {
            font-size: 42px;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
        }

        .currency {
            font-size: 24px;
            color: var(--primary-light);
        }

        .payment-methods {
            margin-bottom: 30px;
        }

        .payment-method {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 2px solid rgba(67, 97, 238, 0.2);
            border-radius: var(--border-radius);
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--light);
        }

        .payment-method:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.1);
        }

        .payment-method.selected {
            border-color: var(--primary);
            background: rgba(67, 97, 238, 0.05);
        }

        .method-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: rgba(67, 97, 238, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary);
            font-size: 24px;
        }

        .method-info {
            flex: 1;
        }

        .method-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark);
        }

        .method-desc {
            font-size: 14px;
            color: var(--dark);
            opacity: 0.7;
        }

        .method-check {
            color: var(--primary);
            font-size: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .payment-method.selected .method-check {
            opacity: 1;
        }

        .payment-status-options {
            margin-bottom: 30px;
        }

        .status-options-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark);
            text-align: center;
        }

        .status-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .status-btn {
            padding: 12px;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }

        .status-btn.success {
            background: rgba(76, 201, 240, 0.1);
            color: var(--success);
            border: 2px solid var(--success);
        }

        .status-btn.success:hover {
            background: var(--success);
            color: white;
        }

        .status-btn.processing {
            background: rgba(248, 150, 30, 0.1);
            color: var(--warning);
            border: 2px solid var(--warning);
        }

        .status-btn.processing:hover {
            background: var(--warning);
            color: white;
        }

        .status-btn.failed {
            background: rgba(247, 37, 133, 0.1);
            color: var(--danger);
            border: 2px solid var(--danger);
        }

        .status-btn.failed:hover {
            background: var(--danger);
            color: white;
        }

        .payment-actions {
            display: flex;
            gap: 15px;
        }

        .payment-btn {
            flex: 1;
            padding: 18px;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .payment-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .payment-btn:hover::before {
            left: 100%;
        }

        .payment-btn.primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }

        .payment-btn.primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }

        .payment-btn.secondary {
            background: var(--light);
            border: 2px solid var(--primary-light);
            color: var(--primary);
        }

        .payment-btn.secondary:hover {
            background: rgba(67, 97, 238, 0.05);
        }

        .payment-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(67, 97, 238, 0.1);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        .error-message {
            background: rgba(247, 37, 133, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: none;
        }

        .demo-note {
            background: rgba(248, 150, 30, 0.1);
            border: 1px solid var(--warning);
            color: var(--warning);
            padding: 15px;
            border-radius: var(--border-radius);
            margin-top: 20px;
            font-size: 14px;
            text-align: center;
        }

        @keyframes shimmer {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 600px) {
            .payment-content {
                padding: 20px;
            }
            
            .status-buttons {
                grid-template-columns: 1fr;
            }
            
            .payment-actions {
                flex-direction: column;
            }
            
            .amount {
                font-size: 32px;
            }
        }
    </style>
</head>
<body id="body">
    <div class="payment-container">
        <div class="payment-header">
            <div class="payment-icon">
                <i class="fas fa-lock"></i>
            </div>
            <h1>Secure Payment</h1>
            <p>Complete your visa application payment</p>
        </div>
        
        <div class="payment-content">
            <?php if (isset($error)): ?>
                <div class="error-message" style="display: block;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="order-summary">
                <div class="summary-item">
                    <span class="summary-label">Order ID</span>
                    <span class="summary-value">#<?php echo $order['id']; ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Country</span>
                    <span class="summary-value"><?php echo $order['country_name']; ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Contact Email</span>
                    <span class="summary-value"><?php echo $order['email']; ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Order Date</span>
                    <span class="summary-value"><?php echo date('d-m-Y', strtotime($order['created_at'])); ?></span>
                </div>
            </div>
            
            <div class="amount-display">
                <div class="amount-label">Total Amount Due</div>
                <div class="amount">
                    $<?php echo number_format($order['total_amount'], 2); ?>
                    <span class="currency"><?php echo $order['currency']; ?></span>
                </div>
            </div>
            
            <div class="payment-methods">
                <div class="payment-method selected" onclick="selectPaymentMethod(this)">
                    <div class="method-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="method-info">
                        <div class="method-name">Credit/Debit Card</div>
                        <div class="method-desc">Visa, Mastercard, American Express</div>
                    </div>
                    <div class="method-check">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                
                <div class="payment-method" onclick="selectPaymentMethod(this)">
                    <div class="method-icon">
                        <i class="fas fa-university"></i>
                    </div>
                    <div class="method-info">
                        <div class="method-name">Bank Transfer</div>
                        <div class="method-desc">Direct bank transfer</div>
                    </div>
                    <div class="method-check">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <form id="paymentForm" method="POST">
                <input type="hidden" name="payment_status" id="paymentStatus" value="success">
                
                <div class="payment-status-options">
                    <div class="status-options-title">Demo Payment Status (Select One)</div>
                    <div class="status-buttons">
                        <button type="button" class="status-btn success" onclick="setPaymentStatus('success')">
                            <i class="fas fa-check-circle"></i>
                            <span>Success</span>
                        </button>
                        <button type="button" class="status-btn processing" onclick="setPaymentStatus('processing')">
                            <i class="fas fa-spinner"></i>
                            <span>Processing</span>
                        </button>
                        <button type="button" class="status-btn failed" onclick="setPaymentStatus('failed')">
                            <i class="fas fa-times-circle"></i>
                            <span>Failed</span>
                        </button>
                    </div>
                </div>
                
                <div class="payment-actions">
                    <button type="button" class="payment-btn secondary" onclick="window.location.href='index.php'">
                        <i class="fas fa-arrow-left"></i>
                        Back to Chat
                    </button>
                    <button type="submit" class="payment-btn primary" id="submitBtn">
                        <i class="fas fa-lock"></i>
                        Process Payment
                    </button>
                </div>
            </form>
            
            <div class="demo-note">
                <i class="fas fa-info-circle"></i>
                This is a demo payment page. No real transaction will occur.
            </div>
            
            <div class="loading" id="loading">
                <div class="loading-spinner"></div>
                <p>Processing your payment...</p>
            </div>
            
            <div class="error-message" id="errorMessage"></div>
        </div>
    </div>

    <script>
        let selectedPaymentMethod = 'credit_card';
        let selectedStatus = 'success';
        
        function selectPaymentMethod(element) {
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            element.classList.add('selected');
            
            if (element.querySelector('.fa-credit-card')) {
                selectedPaymentMethod = 'credit_card';
            } else {
                selectedPaymentMethod = 'bank_transfer';
            }
        }
        
        function setPaymentStatus(status) {
            selectedStatus = status;
            document.getElementById('paymentStatus').value = status;
            
            // Update button styles
            document.querySelectorAll('.status-btn').forEach(btn => {
                btn.style.opacity = '0.7';
                btn.style.transform = 'scale(1)';
            });
            event.currentTarget.style.opacity = '1';
            event.currentTarget.style.transform = 'scale(1.05)';
            
            // Update submit button text based on status
            const submitBtn = document.getElementById('submitBtn');
            const icon = submitBtn.querySelector('i');
            const text = submitBtn.querySelector('span') || document.createElement('span');
            
            if (status === 'success') {
                icon.className = 'fas fa-check-circle';
                text.textContent = 'Simulate Successful Payment';
            } else if (status === 'processing') {
                icon.className = 'fas fa-spinner';
                text.textContent = 'Simulate Processing Payment';
            } else {
                icon.className = 'fas fa-times-circle';
                text.textContent = 'Simulate Failed Payment';
            }
            
            if (!submitBtn.querySelector('span')) {
                submitBtn.appendChild(text);
            }
        }
        
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const loading = document.getElementById('loading');
            const errorMessage = document.getElementById('errorMessage');
            
            // Show loading
            submitBtn.disabled = true;
            loading.style.display = 'block';
            errorMessage.style.display = 'none';
            
            // Simulate processing delay
            setTimeout(() => {
                // Submit the form
                this.submit();
            }, 1500);
        });
        
        // Initialize
        window.addEventListener('load', () => {
            setPaymentStatus('success');
        });
    </script>
</body>
</html>