<?php

namespace App\Modules\Cart\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'guest_token',
        'currency',
        'status',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function appliedCoupon(): HasOne
    {
        return $this->hasOne(CartCoupon::class)->latestOfMany('applied_at');
    }

    /** Default relations to eager load when loading cart. */
    public static function defaultEagerLoads(): array
    {
        return ['items', 'appliedCoupon'];
    }
}
