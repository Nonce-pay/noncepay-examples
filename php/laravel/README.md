# TrezorPay + Laravel

Files to copy into a Laravel 11 or 12 app (PHP 8.2+, `bcmath` extension). Unlike the other examples, orders and
payments are stored in the database, with each TrezorPay payment `uuid` unique so a retried callback is never counted
twice.

1. Copy `app/`, `database/`, `resources/` and `routes/web.php` into your app.
2. Add the `trezorpay` entry of [`config/services.trezorpay.php`](config/services.trezorpay.php) to `config/services.php`.
3. Set in `.env`:

   ```dotenv
   TREZORPAY_PUBLIC_URL=https://your-tunnel.example
   TREZORPAY_WALLET=YOUR_TRON_WALLET
   ```

4. Run:

   ```bash
   php artisan migrate
   php artisan serve
   ```

Open http://localhost:8000, click **Pay**, and send the amount shown to the address.

- [`TrezorPayController.php`](app/Http/Controllers/TrezorPayController.php): creates the address, shows the QR code,
  verifies and records each callback in a transaction.
- [`create_orders_table`](database/migrations/2026_11_08_000000_create_orders_table.php): `orders` and
  `order_payments`.

See the [main README](../../README.md) for how the flow works.
