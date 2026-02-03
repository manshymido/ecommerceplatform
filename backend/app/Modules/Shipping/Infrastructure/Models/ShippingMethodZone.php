<?php

namespace App\Modules\Shipping\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingMethodZone extends Model
{
    protected $table = 'shipping_method_zones';

    protected $fillable = [
        'shipping_method_id',
        'country_code',
        'region',
        'postal_code_pattern',
        'min_cart_total',
        'max_cart_total',
        'base_amount',
        'per_kg_amount',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'min_cart_total' => 'decimal:2',
            'max_cart_total' => 'decimal:2',
            'base_amount' => 'decimal:2',
            'per_kg_amount' => 'decimal:2',
        ];
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }
}
