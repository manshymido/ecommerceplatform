<?php

namespace App\Modules\Promotion\Infrastructure\Models;

use App\Modules\Promotion\Infrastructure\Concerns\HasValidPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasValidPeriod;
    protected $fillable = [
        'code',
        'promotion_id',
        'usage_limit',
        'usage_limit_per_user',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function isCurrentlyValid(): bool
    {
        if (! $this->isWithinPeriod()) {
            return false;
        }
        if ($this->usage_limit !== null && $this->redemptions()->count() >= $this->usage_limit) {
            return false;
        }

        return true;
    }
}
