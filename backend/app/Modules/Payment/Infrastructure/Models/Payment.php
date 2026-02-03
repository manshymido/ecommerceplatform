<?php

namespace App\Modules\Payment\Infrastructure\Models;

use App\Modules\Order\Infrastructure\Models\Order as OrderModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'provider',
        'provider_reference',
        'amount',
        'currency',
        'status',
        'raw_response_json',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'raw_response_json' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderModel::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
