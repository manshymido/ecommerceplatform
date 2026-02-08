<?php

namespace App\Modules\Order\Infrastructure\Models;

use App\Modules\Payment\Infrastructure\Models\Refund;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderReturn extends Model
{
    protected $table = 'returns';

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'order_id',
        'status',
        'refund_id',
        'notes',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    public function returnLines(): HasMany
    {
        return $this->hasMany(ReturnLine::class, 'return_id');
    }
}
