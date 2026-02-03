<?php

namespace App\Modules\Cart\Infrastructure\Models;

use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_variant_id',
        'quantity',
        'unit_price_amount',
        'unit_price_currency',
        'discount_amount',
        'discount_currency',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function lineTotal(): float
    {
        return (float) ($this->unit_price_amount * $this->quantity - $this->discount_amount);
    }
}
