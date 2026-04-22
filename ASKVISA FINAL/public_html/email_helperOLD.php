<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require_once 'config.php';
require_once 'invoice_helper.php';

function sendOrderConfirmationEmail($order_id, $recipient_email, $order_details = []) {
    global $pdo;
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($recipient_email);

        // Attach Invoice PDF
        $pdf_content = getInvoicePDFString($order_id, $pdo);
        if ($pdf_content) {
            $mail->addStringAttachment($pdf_content, "Invoice_Order_#{$order_id}.pdf");
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Order Confirmation - Ask Visa Portal #" . $order_id;
        
        $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
            <h2 style='color: #007bff; text-align: center;'>Order Confirmed!</h2>
            <p>Dear Customer,</p>
            <p>Thank you for choosing Ask Visa Portal. Your payment has been successfully verified, and your visa application is now being processed.</p>
            
            <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <h3 style='margin-top: 0;'>Order Summary</h3>
                <p><strong>Order ID:</strong> #{$order_id}</p>
                <p><strong>Status:</strong> Paid & Processed</p>
                <p><strong>Date:</strong> " . date('d M Y, H:i A') . "</p>
            </div>
            
            <p>We have attached your invoice to this email. You can also view your application status and download your invoice from the portal anytime.</p>
            
            <p style='margin-top: 30px;'>Best Regards,<br><strong>The Ask Visa Portal Team</strong></p>
            <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
            <p style='font-size: 12px; color: #777; text-align: center;'>This is an automated message, please do not reply directly to this email.</p>
        </div>";

        $mail->Body = $body;
        $mail->AltBody = "Thank you for choosing Ask Visa Portal. Your order #{$order_id} has been confirmed. Your invoice is attached.";

        $mail->send();
        error_log("Email sent successfully to {$recipient_email} for order #{$order_id} with invoice attachment");
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
