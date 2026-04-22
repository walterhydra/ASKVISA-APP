<?php
function loadRazorpay()
{
    $razorpayPath = __DIR__ . '/razorpay-php/src';

    // required files
    $files = [
        'Api.php',
        'Request.php',
        'Errors/Error.php',
        'Errors/ErrorCode.php',
        'Errors/GatewayError.php',
        'Errors/SignatureVerificationError.php',
        'Utility.php',
        'Collection.php',
        'Payment.php',
        'Order.php',
        'Customer.php',
        'Invoice.php',
        'Subscription.php',
        'Addon.php',
        'Plan.php',
        'QrCode.php',
        'FundAccount.php',
        'Card.php',
        'Item.php',
        'Token.php',
        'Transfer.php',
        'VirtualAccount.php',
        'Settlement.php',
        'Document.php'
    ];

    foreach ($files as $file) {
        $filePath = $razorpayPath . '/' . $file;
        if (file_exists($filePath)) {
            require_once $filePath;
        } else {
            error_log("Razorpay file not found: $filePath");
        }
    }
}

// Autoloader for Razorpay classes
spl_autoload_register(function ($class) {
    $prefix = 'Razorpay\\Api\\';
    $base_dir = __DIR__ . '/razorpay-php/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
