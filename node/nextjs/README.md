# NoncePay + Next.js

Node.js 22 or later, Next.js App Router.

```bash
npm install
cp .env.example .env.local   # then set PUBLIC_URL and WALLET
npm run build && npm start
```

Open http://localhost:3000, click **Pay**, and send the amount shown to the address. The order page refreshes every 10
seconds: **received** when the payment is seen, **paid** once it is forwarded to your wallet.

| Variable               | Meaning                                                                           |
| ---------------------- | --------------------------------------------------------------------------------- |
| `PUBLIC_URL`           | Public base URL of this server, reachable by NoncePay (a tunnel on your machine) |
| `WALLET`               | Your wallet on the ticker's network: every payment is forwarded there             |
| `TICKER`               | Optional, `trc20/usdt` by default                                                 |
| `NONCEPAY_PUBLIC_KEY` | Optional, pins the callback key instead of fetching it from `/pubkey/`            |

- [`lib/noncepay.ts`](lib/noncepay.ts): API calls, signature check, exact decimal amounts.
- [`app/orders/route.ts`](app/orders/route.ts): creates the order and its address.
- [`app/noncepay/callback/route.ts`](app/noncepay/callback/route.ts): verifies and records the payment.

See the [main README](../../README.md) for how the flow works.
