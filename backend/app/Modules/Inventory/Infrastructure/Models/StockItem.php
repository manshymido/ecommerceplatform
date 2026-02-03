<?php

namespace App\Modules\Inventory\Infrastructure\Models;

use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    protected $table = 'stock_items';

    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'quantity',
        'safety_stock',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'safety_stock' => 'integer',
        ];
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class, 'product_variant_id', 'product_variant_id')
            ->where('warehouse_id', $this->warehouse_id);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_variant_id', 'product_variant_id')
            ->where('warehouse_id', $this->warehouse_id);
    }
}
