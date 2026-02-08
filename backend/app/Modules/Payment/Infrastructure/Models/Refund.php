<?php

namespace App\Modules\Payment\Infrastructure\Models;

use App\Modules\Order\Infrastructure\Models\Order;
use App\Modules\Order\Infrastructure\Models\OrderReturn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Refund extends Model
{
    protected $fillable = [
        'payment_id',
        'order_id',
        'amount',
        'currency',
        'status',
        'reason',
        'raw_response_json',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'raw_response_json' => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderReturn(): HasOne
    {
        return $this->hasOne(OrderReturn::class, 'refund_id');
    }
}
