<!doctype html>
<meta charset="utf-8">
<meta http-equiv="refresh" content="10">
<title>Order {{ $order->status }}</title>
<body style="font-family:system-ui;max-width:560px;margin:48px auto;padding:0 16px">
<h1>Order {{ $order->status }}</h1>
<p>Send <b>{{ $order->amount }}</b> {{ $order->ticker }} to</p>
<p><code>{{ $order->address }}</code></p>
<img src="data:image/png;base64,{{ $qr }}" width="240" height="240" alt="Payment QR code">
<p>Status: <b>{{ $order->status }}</b></p>
</body>
