<?php

// Add this entry to the array returned by config/services.php.
return [
    'trezorpay' => [
        // Public base URL of the app, reachable by TrezorPay
        'public_url' => env('TREZORPAY_PUBLIC_URL', env('APP_URL')),
        // Your wallet on the ticker's network: every payment is forwarded there
        'wallet' => env('TREZORPAY_WALLET'),
        'ticker' => env('TREZORPAY_TICKER', 'trc20/usdt'),
        // Optional: pin the callback public key instead of fetching it from /pubkey/
        'public_key' => env('TREZORPAY_PUBLIC_KEY'),
    ],
];
