<?php
session_start();
require 'db.php';
require_once('tcpdf_config.php');
require_once('tcpdf/tcpdf.php');

// Extend TCPDF to add custom header/footer
class InvoicePDF extends TCPDF {
    // Page header
    public function Header() {
        // Logo
        $image_file = 'logo.png'; // Change to your logo path
        if (file_exists($image_file)) {
            $this->Image($image_file, 15, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Company name and title
        $this->SetFont('helvetica', 'B', 18);
        $this->Cell(0, 15, 'ASK VISA PORTAL', 0, 1, 'C', 0, '', 0, false, 'M', 'M');
        
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 5, 'Visa Application Service', 0, 1, 'C');
        $this->Cell(0, 5, '123 Visa Street, City, Country', 0, 1, 'C');
        $this->Cell(0, 5, 'Email: support@askvisa.com | Phone: +1 (234) 567-8900', 0, 1, 'C');
        
        // Line separator
        $this->Ln(5);
        $this->SetLineWidth(0.5);
        $this->Line(15, 40, 195, 40);
        $this->Ln(10);
    }
    
    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        
        // Footer text
        $this->SetY(-25);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(0, 10, 'This is a computer-generated invoice. No signature required.', 0, 0, 'C');
        $this->Ln(4);
        $this->Cell(0, 10, 'For any queries, contact: accounts@askvisa.com', 0, 0, 'C');
    }
}

$order_id = $_GET['order_id'] ?? 0;

if (!$order_id) {
    die('Invalid order ID');
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
    die('Order not found');
}

// Get payment details
$stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$order_id]);
$payment = $stmt->fetch();

// Get applicant count
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM applicants WHERE order_id = ?");
$stmt->execute([$order_id]);
$applicant_count = $stmt->fetch();
$total_people = $applicant_count['total'] ?? 1;

$invoice_number = 'INV-' . date('Ymd') . '-' . $order_id;

// Create new PDF document
$pdf = new InvoicePDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Ask Visa Portal');
$pdf->SetTitle('Invoice ' . $invoice_number);
$pdf->SetSubject('Visa Application Invoice');
$pdf->SetKeywords('Invoice, Visa, Application, Payment');

// Set default header data
$pdf->SetHeaderData('', 0, 'Invoice ' . $invoice_number, "Invoice Date: " . date('d/m/Y'));

// Set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Title
$pdf->SetFont('helvetica', 'B', 24);
$pdf->Cell(0, 15, 'TAX INVOICE', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 14);
$pdf->Cell(0, 10, $invoice_number, 0, 1, 'C');
$pdf->Ln(10);

// Order Information
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Order Information', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);

// Create table for order details
$html = '<table border="0" cellpadding="5" cellspacing="0" style="width:100%;">
    <tr>
        <td width="40%" style="border-bottom:1px solid #ddd;"><strong>Order ID:</strong></td>
        <td width="60%" style="border-bottom:1px solid #ddd;">#' . $order_id . '</td>
    </tr>
    <tr>
        <td style="border-bottom:1px solid #ddd;"><strong>Invoice Date:</strong></td>
        <td style="border-bottom:1px solid #ddd;">' . date('F j, Y') . '</td>
    </tr>
    <tr>
        <td style="border-bottom:1px solid #ddd;"><strong>Country:</strong></td>
        <td style="border-bottom:1px solid #ddd;">' . htmlspecialchars($order['country_name']) . '</td>
    </tr>
    <tr>
        <td style="border-bottom:1px solid #ddd;"><strong>Visa Type:</strong></td>
        <td style="border-bottom:1px solid #ddd;">' . htmlspecialchars($order['visa_type']) . '</td>
    </tr>
    <tr>
        <td style="border-bottom:1px solid #ddd;"><strong>Number of Applicants:</strong></td>
        <td style="border-bottom:1px solid #ddd;">' . $total_people . ' person(s)</td>
    </tr>
    <tr>
        <td style="border-bottom:1px solid #ddd;"><strong>Processing Time:</strong></td>
        <td style="border-bottom:1px solid #ddd;">' . htmlspecialchars($order['processing_time']) . '</td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(10);

// Payment Information
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Payment Details', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);

// Payment details table
$html = '<table border="0" cellpadding="5" cellspacing="0" style="width:100%;">
    <tr>
        <td width="40%" style="border-bottom:1px solid #ddd;"><strong>Total Amount:</strong></td>
        <td width="60%" style="border-bottom:1px solid #ddd;">' . $order['currency'] . ' ' . number_format($order['total_amount'], 2) . '</td>
    </tr>
    <tr>
        <td style="border-bottom:1px solid #ddd;"><strong>Payment Method:</strong></td>
        <td style="border-bottom:1px solid #ddd;">' . ($payment ? htmlspecialchars($payment['payment_method']) : 'Online Payment') . '</td>
    </tr>
    <tr>
        <td style="border-bottom:1px solid #ddd;"><strong>Payment Status:</strong></td>
        <td style="border-bottom:1px solid #ddd; color: #28a745; font-weight: bold;">Paid</td>
    </tr>
    <tr>
        <td style="border-bottom:1px solid #ddd;"><strong>Payment Date:</strong></td>
        <td style="border-bottom:1px solid #ddd;">' . date('F j, Y, h:i A') . '</td>
    </tr>';

if ($payment && $payment['provider_payment_id']) {
    $html .= '<tr>
        <td style="border-bottom:1px solid #ddd;"><strong>Transaction ID:</strong></td>
        <td style="border-bottom:1px solid #ddd;">' . $payment['provider_payment_id'] . '</td>
    </tr>';
}

$html .= '</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(15);

// Total amount box
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('helvetica', 'B', 18);
$pdf->Cell(0, 15, 'Total Paid: ' . $order['currency'] . ' ' . number_format($order['total_amount'], 2), 1, 1, 'C', true);
$pdf->Ln(15);

// Terms and Conditions
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Terms & Conditions:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 6, '1. This invoice is generated for the visa application service provided by Ask Visa Portal.
2. All payments are non-refundable once the application process has started.
3. Visa approval is subject to the respective embassy/consulate\'s decision.
4. The service fee covers processing and documentation assistance only.
5. For any disputes or queries, contact us within 7 days of invoice date.
6. This is a computer-generated invoice and does not require a signature.', 0, 'L');
$pdf->Ln(10);

// Thank you message
$pdf->SetFont('helvetica', 'I', 12);
$pdf->Cell(0, 10, 'Thank you for choosing Ask Visa Portal for your visa application needs!', 0, 1, 'C');
$pdf->Ln(5);

// Output PDF as download
$pdf->Output('invoice-' . $order_id . '.pdf', 'D');
?>