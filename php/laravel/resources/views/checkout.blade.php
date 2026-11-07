<!doctype html>
<meta charset="utf-8">
<title>Checkout</title>
<body style="font-family:system-ui;max-width:560px;margin:48px auto;padding:0 16px">
<h1>Checkout</h1>
<form method="post" action="/orders">
    @csrf
    <button>Pay ${{ $price }} in crypto</button>
</form>
</body>
