<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $order_id = $input['order_id'] ?? 0;
    
    if ($order_id) {
        $_SESSION['payment_order_id'] = $order_id;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>