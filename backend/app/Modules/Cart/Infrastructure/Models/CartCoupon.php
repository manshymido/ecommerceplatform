<?php

namespace App\Modules\Cart\Infrastructure\Models;

use App\Modules\Promotion\Infrastructure\Models\Coupon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartCoupon extends Model
{
    protected $fillable = [
        'cart_id',
        'coupon_id',
        'coupon_code',
        'discount_amount',
        'discount_currency',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
            'applied_at' => 'datetime',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
