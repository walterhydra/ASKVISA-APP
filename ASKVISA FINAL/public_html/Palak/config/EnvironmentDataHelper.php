<?php
// helpers/EncryptedDataHelper.php
require_once 'services/EncryptionService.php';

class EncryptedDataHelper
{
    private static $encryption = null;

    private static function getEncryption()
    {
        if (self::$encryption === null) {
            self::$encryption = new EncryptionService();
        }
        return self::$encryption;
    }

    // Store encrypted data
    public static function storeEncryptedPayment($orderId, $paymentId, $email, $phone = null)
    {
        $encryption = self::getEncryption();
        $pdo = getDatabaseConnection();

        $encryptedPaymentId = $encryption->encryptPaymentId($paymentId);
        $encryptedEmail = $encryption->encryptEmail($email);

        if ($phone) {
            $encryptedPhone = $encryption->encryptPhone($phone);
        }
    }

    // Retrieve and decrypt data
    public static function getDecryptedPayment($paymentId)
    {
        $pdo = getDatabaseConnection();
        $encryption = self::getEncryption();

        $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();

        if ($payment && !empty($payment['encrypted_payment_id'])) {
            $payment['decrypted_payment_id'] = $encryption->decrypt($payment['encrypted_payment_id']);
            $payment['decrypted_email'] = $encryption->decrypt($payment['encrypted_email']);
        }

        return $payment;
    }

    public static function findPaymentByEmail($email)
    {
        $pdo = getDatabaseConnection();
        $encryption = self::getEncryption();

        // To search encrypted data, you need to encrypt the search term
        $encryptedEmail = $encryption->encryptEmail($email);

        $stmt = $pdo->prepare("SELECT * FROM payments WHERE encrypted_email = ?");
        $stmt->execute([$encryptedEmail]);

        return $stmt->fetchAll();
    }
}
