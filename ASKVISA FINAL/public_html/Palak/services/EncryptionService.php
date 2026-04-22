<?php
// services/EncryptionService.php
class EncryptionService
{
    private $key;
    private $cipher = 'aes-256-gcm';

    public function __construct()
    {
        $this->loadKey();
    }

    private function loadKey()
    {
        // Load from environment
        require_once __DIR__ . '/../config/EnvironmentLoader.php';
        $keyBase64 = EnvironmentLoader::get('ENCRYPTION_KEY');

        if (!$keyBase64) {
            throw new Exception('Encryption key not configured');
        }

        // Remove "base64:" prefix if present
        if (strpos($keyBase64, 'base64:') === 0) {
            $keyBase64 = substr($keyBase64, 7);
        }

        $this->key = base64_decode($keyBase64);

        if (strlen($this->key) !== 32) {
            throw new Exception('Encryption key must be 32 bytes (256 bits)');
        }
    }

    public function encrypt($plaintext)
    {
        if (!is_string($plaintext)) {
            throw new InvalidArgumentException('Plaintext must be a string');
        }

        // Generate random IV (12 bytes for GCM)
        $iv = random_bytes(12);

        // Encrypt
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($ciphertext === false) {
            throw new Exception('Encryption failed: ' . openssl_error_string());
        }

        // Return: iv + tag + ciphertext (all base64 encoded)
        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt($encryptedData)
    {
        // Decode from base64
        $data = base64_decode($encryptedData);

        if (strlen($data) < 28) { // iv(12) + tag(16) = minimum 28 bytes
            throw new Exception('Encrypted data too short');
        }

        // Extract components
        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $ciphertext = substr($data, 28);

        // Decrypt
        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new Exception('Decryption failed: ' . openssl_error_string());
        }

        return $plaintext;
    }

    // Helper to generate a new key
    public static function generateKey()
    {
        $key = random_bytes(32);
        return 'base64:' . base64_encode($key);
    }

    // Encrypt specific field types
    public function encryptEmail($email)
    {
        return $this->encrypt(strtolower(trim($email)));
    }

    public function encryptPhone($phone)
    {
        // Normalize phone number
        $normalized = preg_replace('/[^0-9]/', '', $phone);
        return $this->encrypt($normalized);
    }

    public function encryptPaymentId($paymentId)
    {
        return $this->encrypt($paymentId);
    }
}
