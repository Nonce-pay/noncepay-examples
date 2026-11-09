<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class NoncePayController extends Controller
{
    private const API = 'https://api.noncepay.app';

    private const PRICE_USD = '49.90';

    public function checkout()
    {
        return view('checkout', ['price' => self::PRICE_USD]);
    }

    public function create()
    {
        $ticker = config('services.noncepay.ticker');
        $id = (string) Str::uuid();
        // The callback URL identifies the payment address: one per order, never reused.
        $callback = rtrim(config('services.noncepay.public_url'), '/') . "/noncepay/callback?order={$id}";
        $created = $this->api("/{$ticker}/create/", [
            'callback' => $callback,
            'address' => config('services.noncepay.wallet'),
            'pending' => 1,
        ]);
        $price = $this->api("/{$ticker}/convert/", ['value' => self::PRICE_USD, 'from' => 'usd']);

        Order::create([
            'id' => $id,
            'ticker' => $ticker,
            'address' => $created['address_in'],
            'amount' => $price['value_coin'],
            'status' => 'waiting',
        ]);

        return redirect("/orders/{$id}", 303);
    }

    public function show(Order $order)
    {
        $qr = $this->api("/{$order->ticker}/qrcode/", [
            'address' => $order->address,
            'value' => $order->amount,
            'size' => 240,
        ]);

        return view('order', ['order' => $order, 'qr' => $qr['qr_code']]);
    }

    public function callback(Request $request)
    {
        // GET callbacks are signed over the full URL, exactly as we receive it.
        $signed = rtrim(config('services.noncepay.public_url'), '/') . $request->getRequestUri();
        if (! $this->verify($signed, $request->header('x-ca-signature'))) {
            return response('Bad signature', 401);
        }

        $order = Order::find($request->query('order'));
        if (! $order || $request->query('address_in') !== $order->address) {
            return response('*ok*');
        }

        if ($request->query('pending') === '1') {
            Order::whereKey($order->id)->where('status', 'waiting')->update(['status' => 'received']);

            return response('*ok*');
        }

        // Each payment sends its own callback, once per uuid; a customer may pay in several parts.
        try {
            DB::transaction(function () use ($request, $order) {
                DB::table('order_payments')->insert([
                    'uuid' => $request->query('uuid'),
                    'order_id' => $order->id,
                    'value_coin' => $request->query('value_coin'),
                    'txid_in' => $request->query('txid_in'),
                    'txid_out' => $request->query('txid_out'),
                ]);
                $order = Order::lockForUpdate()->find($order->id);
                // Amounts are decimal strings: bcmath compares them exactly, floats would not.
                $order->paid = bcadd($order->paid, $request->query('value_coin', '0'), 18);
                $order->status = bccomp($order->paid, $order->amount, 18) >= 0 ? 'paid' : 'underpaid';
                $order->save();
            });
        } catch (UniqueConstraintViolationException) {
            // Already recorded: this is a retry of a callback we answered before.
        }

        return response('*ok*');
    }

    private function api(string $path, array $params = []): array
    {
        $body = Http::timeout(15)->get(self::API . $path, $params)->json();
        if (($body['status'] ?? '') !== 'success') {
            throw new RuntimeException("{$path}: " . ($body['error'] ?? 'no answer'));
        }

        return $body;
    }

    private function verify(string $data, ?string $signature): bool
    {
        if (! $signature) {
            return false;
        }
        // The public key never changes: cache it, or pin it with NONCEPAY_PUBLIC_KEY.
        $key = config('services.noncepay.public_key')
            ?: Cache::rememberForever('noncepay.pubkey', fn () => $this->api('/pubkey/')['pubkey']);

        return openssl_verify($data, base64_decode($signature), $key, OPENSSL_ALGO_SHA256) === 1;
    }
}
