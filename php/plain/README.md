# NoncePay + plain PHP

PHP 8.1 or later with the `openssl` and `bcmath` extensions. No framework, no Composer.

```bash
PUBLIC_URL=https://your-tunnel.example WALLET=YOUR_TRON_WALLET php -S localhost:8000 index.php
```

Open http://localhost:8000, click **Pay**, and send the amount shown to the address. The order page refreshes every 10
seconds: **received** when the payment is seen, **paid** once it is forwarded to your wallet.

| Variable               | Meaning                                                                           |
| ---------------------- | --------------------------------------------------------------------------------- |
| `PUBLIC_URL`           | Public base URL of this server, reachable by NoncePay (a tunnel on your machine) |
| `WALLET`               | Your wallet on the ticker's network: every payment is forwarded there             |
| `TICKER`               | Optional, `trc20/usdt` by default                                                 |
| `NONCEPAY_PUBLIC_KEY` | Optional, pins the callback key instead of fetching it from `/pubkey/`            |

- [`noncepay.php`](noncepay.php): API calls and signature check, usable on its own.
- [`index.php`](index.php): the shop and the callback handler.

See the [main README](../../README.md) for how the flow works.
