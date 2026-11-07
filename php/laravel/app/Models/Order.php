<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasUuids;

    protected $fillable = ['id', 'ticker', 'address', 'amount', 'paid', 'status'];

    protected $attributes = ['paid' => '0'];
}
