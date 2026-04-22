<?php
session_start();
require 'db.php';
require_once 'invoice_helper.php';

$order_id = $_GET['order_id'] ?? 0;

if (!$order_id) {
    die('Invalid order ID');
}

$pdf_string = getInvoicePDFString($order_id, $pdo);

if (!$pdf_string) {
    die('Order not found or error generating PDF');
}

$inline = isset($_GET['inline']) && $_GET['inline'] == 1;
$filename = 'invoice-order-#' . $order_id . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $filename . '"');
echo $pdf_string;
