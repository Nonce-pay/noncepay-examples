<?php

const TREZORPAY_API = 'https://api.trezorpay.com';

function env(string $name, ?string $default = null): string
{
    $value = getenv($name);
    if ($value === false || $value === '') {
        if ($default === null) {
            throw new RuntimeException("Set {$name} (see README.md)");
        }
        return $default;
    }
    return $value;
}

function trezorpay(string $path, array $params = []): array
{
    $url = TREZORPAY_API . $path . ($params ? '?' . http_build_query($params) : '');
    $context = stream_context_create(['http' => ['timeout' => 15, 'ignore_errors' => true]]);
    $body = json_decode((string) file_get_contents($url, false, $context), true);
    if (($body['status'] ?? '') !== 'success') {
        throw new RuntimeException("{$path}: " . ($body['error'] ?? 'no answer'));
    }
    return $body;
}

// The public key never changes: cache it, or pin it with TREZORPAY_PUBLIC_KEY.
function trezorpay_public_key(): string
{
    $pinned = getenv('TREZORPAY_PUBLIC_KEY');
    if ($pinned) {
        return $pinned;
    }
    $cache = sys_get_temp_dir() . '/trezorpay-pubkey.pem';
    if (!is_file($cache)) {
        file_put_contents($cache, trezorpay('/pubkey/')['pubkey']);
    }
    return (string) file_get_contents($cache);
}

function trezorpay_verify(string $data, ?string $signature): bool
{
    if (!$signature) {
        return false;
    }
    return openssl_verify($data, base64_decode($signature), trezorpay_public_key(), OPENSSL_ALGO_SHA256) === 1;
}

// Use your database in a real shop. This file store is enough for one PHP process.
function with_orders(callable $change)
{
    $file = fopen(sys_get_temp_dir() . '/trezorpay-orders.json', 'c+');
    flock($file, LOCK_EX);
    $orders = json_decode(stream_get_contents($file) ?: '{}', true);
    $result = $change($orders);
    ftruncate($file, 0);
    rewind($file);
    fwrite($file, json_encode($orders));
    flock($file, LOCK_UN);
    fclose($file);
    return $result;
}
