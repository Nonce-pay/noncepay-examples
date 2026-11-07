<?php

use App\Http\Controllers\TrezorPayController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TrezorPayController::class, 'checkout']);
Route::post('/orders', [TrezorPayController::class, 'create']);
Route::get('/orders/{order}', [TrezorPayController::class, 'show']);
Route::get('/trezorpay/callback', [TrezorPayController::class, 'callback']);
