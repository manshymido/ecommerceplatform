<?php

namespace App\Modules\Payment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = [
        'payment_id',
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
}
