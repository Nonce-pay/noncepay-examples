<?php

require __DIR__ . '/noncepay.php';

$PUBLIC_URL = rtrim(env('PUBLIC_URL'), '/'); // where NoncePay can reach this server
$WALLET = env('WALLET'); // your wallet: payments are forwarded there
$TICKER = env('TICKER', 'trc20/usdt');
$PRICE_USD = '49.90';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

function page(string $title, string $body): void
{
    echo "<!doctype html><meta charset=\"utf-8\"><title>{$title}</title>"
        . '<body style="font-family:system-ui;max-width:560px;margin:48px auto;padding:0 16px">'
        . "<h1>{$title}</h1>{$body}</body>";
}

if ($method === 'GET' && $path === '/') {
    page('Checkout', "<form method=\"post\" action=\"/orders\"><button>Pay \${$PRICE_USD} in crypto</button></form>");
    return;
}

if ($method === 'POST' && $path === '/orders') {
    // The callback URL identifies the payment address: one per order, never reused.
    $id = bin2hex(random_bytes(16));
    $callback = "{$PUBLIC_URL}/noncepay/callback?order={$id}";
    $created = noncepay("/{$TICKER}/create/", ['callback' => $callback, 'address' => $WALLET, 'pending' => 1]);
    $price = noncepay("/{$TICKER}/convert/", ['value' => $PRICE_USD, 'from' => 'usd']);
    $qr = noncepay("/{$TICKER}/qrcode/", ['address' => $created['address_in'], 'value' => $price['value_coin'], 'size' => 240]);
    with_orders(function (array &$orders) use ($id, $created, $price, $qr) {
        $orders[$id] = [
            'address' => $created['address_in'],
            'amount' => $price['value_coin'],
            'qr' => $qr['qr_code'],
            'status' => 'waiting',
            'paid' => '0',
            'payments' => [],
        ];
    });
    header("Location: /orders/{$id}", true, 303);
    return;
}

if ($method === 'GET' && preg_match('#^/orders/([0-9a-f]{32})$#', $path, $match)) {
    $order = with_orders(fn (array &$orders) => $orders[$match[1]] ?? null);
    if (!$order) {
        http_response_code(404);
        echo 'Unknown order';
        return;
    }
    $e = fn (string $text) => htmlspecialchars($text, ENT_QUOTES);
    page(
        "Order {$order['status']}",
        '<meta http-equiv="refresh" content="10">'
        . "<p>Send <b>{$e($order['amount'])}</b> {$e($TICKER)} to</p>"
        . "<p><code>{$e($order['address'])}</code></p>"
        . "<img src=\"data:image/png;base64,{$order['qr']}\" width=\"240\" height=\"240\" alt=\"Payment QR code\">"
        . "<p>Status: <b>{$order['status']}</b></p>"
    );
    return;
}

if ($method === 'GET' && $path === '/noncepay/callback') {
    // GET callbacks are signed over the full URL, exactly as we receive it.
    if (!noncepay_verify($PUBLIC_URL . $_SERVER['REQUEST_URI'], $_SERVER['HTTP_X_CA_SIGNATURE'] ?? null)) {
        http_response_code(401);
        echo 'Bad signature';
        return;
    }
    with_orders(function (array &$orders) {
        $id = $_GET['order'] ?? '';
        if (!isset($orders[$id]) || ($_GET['address_in'] ?? '') !== $orders[$id]['address']) {
            return;
        }
        $order = &$orders[$id];
        $uuid = $_GET['uuid'] ?? '';
        if (($_GET['pending'] ?? '') === '1') {
            if ($order['status'] === 'waiting') {
                $order['status'] = 'received';
            }
        } elseif (!in_array($uuid, $order['payments'], true)) {
            // Each payment sends its own callback, once per uuid; a customer may pay in several parts.
            $order['payments'][] = $uuid;
            // Amounts are decimal strings: bcmath compares them exactly, floats would not.
            $order['paid'] = bcadd($order['paid'], $_GET['value_coin'] ?? '0', 18);
            $order['status'] = bccomp($order['paid'], $order['amount'], 18) >= 0 ? 'paid' : 'underpaid';
        }
    });
    echo '*ok*';
    return;
}

http_response_code(404);
echo 'Not found';
