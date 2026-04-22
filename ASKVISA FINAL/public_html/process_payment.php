<?php
session_start();
require 'db.php';

// Get payment data
$order_id = $_POST['order_id'] ?? $_SESSION['current_order_id'] ?? 0;
$payment_status = $_POST['payment_status'] ?? 'failed';
$transaction_id = $_POST['transaction_id'] ?? uniqid('TXN_', true);
$payment_method = $_POST['payment_method'] ?? 'credit_card';

// For testing - you can simulate success/failure by adding ?test=success or ?test=fail to URL
if (isset($_GET['test'])) {
    $payment_status = $_GET['test'];
}

if (!$order_id) {
    header('Location: exco.php?error=invalid_order');
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    if ($payment_status === 'success' || $payment_status === 'completed') {
        // Update order payment status
        $stmt = $pdo->prepare("UPDATE visa_orders SET payment_status = 'paid', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$order_id]);
        
        // Get order amount
        $stmt = $pdo->prepare("SELECT total_amount, currency FROM visa_orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order_data = $stmt->fetch();
        
        // Save payment details
        $stmt = $pdo->prepare("INSERT INTO payments (order_id, provider, provider_payment_id, amount, currency, status) 
                               VALUES (?, ?, ?, ?, ?, 'completed')");
        $stmt->execute([
            $order_id, 
            $payment_method, 
            $transaction_id, 
            $order_data['total_amount'], 
            $order_data['currency']
        ]);
        
        // Store success message in session for chat
        $_SESSION['payment_success_message'] = "✅ Payment successful! Order #$order_id has been confirmed. Your visa application is now being processed.";
        
        // Clear current order from session
        unset($_SESSION['current_order_id']);
        
        $pdo->commit();
        
        // Send invoice email
        $stmt = $pdo->prepare("SELECT email FROM visa_orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order_email = $stmt->fetchColumn();
        
        // Redirect to success page
        header("Location: payment_success.php?order_id=" . $order_id . "&email=" . urlencode($order_email));
        exit;
        
    } else {
        // Payment failed - update order status
        $stmt = $pdo->prepare("UPDATE visa_orders SET payment_status = 'failed', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$order_id]);
        
        // Save failed payment attempt
        $stmt = $pdo->prepare("INSERT INTO payments (order_id, provider, provider_payment_id, status) 
                               SELECT ?, ?, ?, 'failed' 
                               FROM visa_orders WHERE id = ?");
        $stmt->execute([$order_id, $payment_method, $transaction_id, $order_id]);
        
        // Store failure message in session
        $_SESSION['payment_error_message'] = "❌ Payment failed for Order #$order_id. Please try again or contact support.";
        
        $pdo->commit();
        
        // Redirect to failure page
        header("Location: payment_failed.php?order_id=" . $order_id);
        exit;
    }
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['payment_error_message'] = "❌ System error: " . $e->getMessage();
    header("Location: payment_failed.php?order_id=" . $order_id . "&error=" . urlencode($e->getMessage()));
    exit;
}
?>