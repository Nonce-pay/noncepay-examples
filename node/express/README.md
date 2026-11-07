# TrezorPay + Express

Node.js 22 or later.

```bash
npm install
cp .env.example .env   # then set PUBLIC_URL and WALLET
npm start
```

Open http://localhost:3000, click **Pay**, and send the amount shown to the address. The order page refreshes every 10
seconds: **received** when the payment is seen, **paid** once it is forwarded to your wallet.

| Variable               | Meaning                                                                           |
| ---------------------- | --------------------------------------------------------------------------------- |
| `PUBLIC_URL`           | Public base URL of this server, reachable by TrezorPay (a tunnel on your machine) |
| `WALLET`               | Your wallet on the ticker's network: every payment is forwarded there             |
| `TICKER`               | Optional, `trc20/usdt` by default                                                 |
| `TREZORPAY_PUBLIC_KEY` | Optional, pins the callback key instead of fetching it from `/pubkey/`            |

Everything is in [`server.js`](server.js): `POST /orders` creates the address, `GET /trezorpay/callback` verifies and records the payment.

See the [main README](../../README.md) for how the flow works.
