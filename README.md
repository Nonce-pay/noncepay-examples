# TrezorPay examples

Ready-to-run examples for accepting crypto payments with [TrezorPay](https://trezorpay.com), the non-custodial crypto
payment API. Each one is a tiny shop: it creates a payment address for an order, shows the amount and a QR code, and
marks the order paid from TrezorPay's signed callback.

Accept BTC, USDT, USDC, ETH, SOL, LTC, DOGE, BNB, TRX and 80+ coins and tokens on 13 networks. Every payment is
forwarded to your own wallet minus 1.25%. **No account, no API key.**

| Language | Example                             | Run with                          |
| -------- | ----------------------------------- | --------------------------------- |
| Node.js  | [Express](node/express)             | `npm start`                       |
| Node.js  | [Next.js (App Router)](node/nextjs) | `npm run build && npm start`      |
| PHP      | [Plain PHP 8](php/plain)            | `php -S localhost:8000 index.php` |
| PHP      | [Laravel](php/laravel)              | files to copy into a Laravel app  |
| Python   | [Flask](python/flask)               | `python app.py`                   |
| Python   | [FastAPI](python/fastapi)           | `uvicorn main:app`                |

## How a crypto payment works

1. **Create an address.** Your server calls `GET https://api.trezorpay.com/{ticker}/create/` with a `callback` URL
   and the `address` of your wallet. The callback URL identifies the payment address: use one per order.
2. **Show it to your customer.** Convert your price with `/{ticker}/convert/` and display the address with a QR code
   from `/{ticker}/qrcode/`.
3. **Receive the callback.** With `pending=1`, TrezorPay calls your URL when the payment is seen, then again with
   `pending=0` once it is confirmed and forwarded to your wallet.
4. **Verify and answer.** Check the `x-ca-signature` header (RSA-SHA256 of the full callback URL, key from
   `/pubkey/`), record the payment once per `uuid`, and answer `*ok*`. Anything else is retried for 3 days.

```bash
curl "https://api.trezorpay.com/trc20/usdt/create/?callback=https%3A%2F%2Fshop.example%2Fpaid%3Forder%3D1042&address=YOUR_TRON_WALLET&pending=1"
```

## Running an example

Every example needs:

- `PUBLIC_URL`: the public base URL of your server. TrezorPay must reach it to send callbacks, so on your machine
  open a tunnel, for example `cloudflared tunnel --url http://localhost:3000` or `ngrok http 3000`.
- `WALLET`: your wallet on the ticker's network. Every payment is forwarded there.
- `TICKER` (optional): `trc20/usdt` by default. See the [supported coins](https://trezorpay.com/docs/coins/).

Then open the shop, click **Pay**, and send a payment above the coin's minimum. It lands in your wallet, and the order
page turns to **paid**.

## What every example gets right

- One callback URL per order, so an address is never shared between two orders.
- Signature checked on every callback before anything else.
- The callback must match the order's address; a payment is counted once per `uuid`, even when a callback is retried.
- Amounts compared as exact decimals, never floats. A customer who pays less sees **underpaid**; several partial
  payments add up.
- Orders live in memory or a temporary file in the simple examples. Use your database, like the Laravel example does.

## Links

- [Documentation](https://trezorpay.com/docs/) and [quickstart](https://trezorpay.com/docs/quickstart/)
- [Callbacks and signature](https://trezorpay.com/docs/callbacks/)
- [Fees](https://trezorpay.com/docs/fees/): 1.25%, down to 0.25% with volume
- [OpenAPI spec](https://trezorpay.com/openapi.yaml), [llms.txt](https://trezorpay.com/llms.txt) and an
  [integration prompt for AI coding agents](https://trezorpay.com/prompt.md)
- [Status](https://status.trezorpay.com)

## License

MIT
