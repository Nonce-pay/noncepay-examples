<?php

// Add this entry to the array returned by config/services.php.
return [
    'noncepay' => [
        // Public base URL of the app, reachable by NoncePay
        'public_url' => env('NONCEPAY_PUBLIC_URL', env('APP_URL')),
        // Your wallet on the ticker's network: every payment is forwarded there
        'wallet' => env('NONCEPAY_WALLET'),
        'ticker' => env('NONCEPAY_TICKER', 'trc20/usdt'),
        // Optional: pin the callback public key instead of fetching it from /pubkey/
        'public_key' => env('NONCEPAY_PUBLIC_KEY'),
    ],
];
