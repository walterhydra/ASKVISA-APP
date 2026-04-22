<?php
session_start();
require 'localdb.php';

header('Content-Type: application/json');

$order_id = $_GET['order_id'] ?? ($_SESSION['current_order_id'] ?? 0);

try {
    // Get order details
    $stmt = $pdo->prepare("SELECT 
        vo.id, vo.payment_status, vo.total_amount, vo.currency, vo.created_at,
        c.country_name, COALESCE(vo.visa_status, 'pending') as visa_status,
        COUNT(a.id) as applicants_count
        FROM visa_orders vo
        LEFT JOIN countries c ON vo.country_id = c.id
        LEFT JOIN applicants a ON vo.id = a.order_id
        WHERE vo.id = ?
        GROUP BY vo.id");

    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if ($order) {
        echo json_encode([
            'success' => true,
            'order_id' => $order['id'],
            'payment_status' => ucfirst($order['payment_status']),
            'country' => $order['country_name'],
            'amount' => $order['total_amount'],
            'currency' => $order['currency'],
            'applicants_count' => $order['applicants_count'],
            'submitted_date' => date('F j, Y', strtotime($order['created_at'])),
            'visa_status' => $order['visa_status'],
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Order not found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
