<?php

namespace App\Modules\Promotion\Infrastructure\Models;

use App\Modules\Promotion\Infrastructure\Concerns\HasValidPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    use HasValidPeriod;
    protected $fillable = [
        'name',
        'type',
        'rule_type',
        'value',
        'starts_at',
        'ends_at',
        'priority',
        'is_active',
        'conditions_json',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'conditions_json' => 'array',
        ];
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function isCurrentlyValid(): bool
    {
        return $this->isWithinPeriod();
    }
}
