# TrezorPay + Flask

Python 3.10 or later.

```bash
python -m venv .venv && . .venv/bin/activate
pip install -r requirements.txt
PUBLIC_URL=https://your-tunnel.example WALLET=YOUR_TRON_WALLET python app.py
```

Open http://localhost:5000, click **Pay**, and send the amount shown to the address. The order page refreshes every 10
seconds: **received** when the payment is seen, **paid** once it is forwarded to your wallet.

| Variable               | Meaning                                                                           |
| ---------------------- | --------------------------------------------------------------------------------- |
| `PUBLIC_URL`           | Public base URL of this server, reachable by TrezorPay (a tunnel on your machine) |
| `WALLET`               | Your wallet on the ticker's network: every payment is forwarded there             |
| `TICKER`               | Optional, `trc20/usdt` by default                                                 |
| `TREZORPAY_PUBLIC_KEY` | Optional, pins the callback key instead of fetching it from `/pubkey/`            |

Everything is in [`app.py`](app.py).
See the [main README](../../README.md) for how the flow works.
