# NoncePay + Laravel

Files to copy into a Laravel 11 or 12 app (PHP 8.2+, `bcmath` extension). Unlike the other examples, orders and
payments are stored in the database, with each NoncePay payment `uuid` unique so a retried callback is never counted
twice.

1. Copy `app/`, `database/`, `resources/` and `routes/web.php` into your app.
2. Add the `noncepay` entry of [`config/services.noncepay.php`](config/services.noncepay.php) to `config/services.php`.
3. Set in `.env`:

   ```dotenv
   NONCEPAY_PUBLIC_URL=https://your-tunnel.example
   NONCEPAY_WALLET=YOUR_TRON_WALLET
   ```

4. Run:

   ```bash
   php artisan migrate
   php artisan serve
   ```

Open http://localhost:8000, click **Pay**, and send the amount shown to the address.

- [`NoncePayController.php`](app/Http/Controllers/NoncePayController.php): creates the address, shows the QR code,
  verifies and records each callback in a transaction.
- [`create_orders_table`](database/migrations/2026_11_08_000000_create_orders_table.php): `orders` and
  `order_payments`.

See the [main README](../../README.md) for how the flow works.
