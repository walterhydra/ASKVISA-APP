<?php
require_once __DIR__ . '/config/EnvironmentLoader.php';

$razorpay_config = [
    'key_id' => EnvironmentLoader::get('RAZORPAY_KEY_ID'),
    'key_secret' => EnvironmentLoader::get('RAZORPAY_KEY_SECRET')
];

// Optional: Encrypt the key in memory for extra security
function getEncryptedRazorpayConfig()
{
    static $encryptedConfig = null;

    if ($encryptedConfig === null) {
        $config = [
            'key_id' => EnvironmentLoader::get('RAZORPAY_KEY_ID'),
            'key_secret' => EnvironmentLoader::get('RAZORPAY_KEY_SECRET')
        ];


        $encryptedConfig = [
            'k' => str_rot13($config['key_id']),
            's' => strrev($config['key_secret'])     // Not real encryption, only hides
        ];
    }

    return [
        'key_id' => str_rot13($encryptedConfig['k']),
        'key_secret' => strrev($encryptedConfig['s'])
    ];
}

$razorpay_config = getEncryptedRazorpayConfig();
