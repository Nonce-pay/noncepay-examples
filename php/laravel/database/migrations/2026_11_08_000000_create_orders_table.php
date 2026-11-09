<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ticker');
            $table->string('address');
            // Amounts are kept as exact decimal strings.
            $table->string('amount');
            $table->string('paid')->default('0');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('order_payments', function (Blueprint $table) {
            // NoncePay's payment id: unique, so a retried callback is never counted twice.
            $table->string('uuid')->primary();
            $table->foreignUuid('order_id')->constrained();
            $table->string('value_coin');
            $table->string('txid_in')->nullable();
            $table->string('txid_out')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
        Schema::dropIfExists('orders');
    }
};
