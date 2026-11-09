<?php

use App\Http\Controllers\NoncePayController;
use Illuminate\Support\Facades\Route;

Route::get('/', [NoncePayController::class, 'checkout']);
Route::post('/orders', [NoncePayController::class, 'create']);
Route::get('/orders/{order}', [NoncePayController::class, 'show']);
Route::get('/noncepay/callback', [NoncePayController::class, 'callback']);
