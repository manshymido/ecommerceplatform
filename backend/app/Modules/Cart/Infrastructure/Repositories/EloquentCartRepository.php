<?php

namespace App\Modules\Cart\Infrastructure\Repositories;

use App\Modules\Cart\Domain\Cart;
use App\Modules\Cart\Domain\CartItem;
use App\Modules\Cart\Domain\CartRepository;
use App\Modules\Cart\Infrastructure\Models\Cart as CartModel;
use App\Modules\Cart\Infrastructure\Models\CartCoupon;
use App\Modules\Cart\Infrastructure\Models\CartItem as CartItemModel;

class EloquentCartRepository implements CartRepository
{
    public function findById(int $id): ?Cart
    {
        return $this->firstCartAsDomain(
            CartModel::with(CartModel::defaultEagerLoads())->where('id', $id)
        );
    }

    public function findActiveByUser(int $userId): ?Cart
    {
        return $this->firstCartAsDomain(
            CartModel::with(CartModel::defaultEagerLoads())
                ->where('user_id', $userId)
                ->where('status', Cart::STATUS_ACTIVE)
        );
    }

    public function findActiveByGuestToken(string $guestToken): ?Cart
    {
        return $this->firstCartAsDomain(
            CartModel::with(CartModel::defaultEagerLoads())
                ->where('guest_token', $guestToken)
                ->where('status', Cart::STATUS_ACTIVE)
        );
    }

    private function firstCartAsDomain(\Illuminate\Database\Eloquent\Builder $query): ?Cart
    {
        $model = $query->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function getOrCreateForUser(int $userId, string $currency = 'USD'): Cart
    {
        $cart = $this->findActiveByUser($userId);
        if ($cart) {
            return $cart;
        }

        $model = CartModel::create([
            'user_id' => $userId,
            'guest_token' => null,
            'currency' => $currency,
            'status' => Cart::STATUS_ACTIVE,
            'last_activity_at' => now(),
        ]);

        return $this->toDomain($model->load(CartModel::defaultEagerLoads()));
    }

    public function getOrCreateForGuest(string $guestToken, string $currency = 'USD'): Cart
    {
        $cart = $this->findActiveByGuestToken($guestToken);
        if ($cart) {
            return $cart;
        }

        $model = CartModel::create([
            'user_id' => null,
            'guest_token' => $guestToken,
            'currency' => $currency,
            'status' => Cart::STATUS_ACTIVE,
            'last_activity_at' => now(),
        ]);

        return $this->toDomain($model->load(CartModel::defaultEagerLoads()));
    }

    public function addOrUpdateItem(int $cartId, int $productVariantId, int $quantity, float $unitPriceAmount, string $currency, float $discountAmount = 0, ?string $discountCurrency = null): void
    {
        $item = CartItemModel::where('cart_id', $cartId)
            ->where('product_variant_id', $productVariantId)
            ->first();

        if ($item) {
            $item->update([
                'quantity' => $quantity,
                'unit_price_amount' => $unitPriceAmount,
                'unit_price_currency' => $currency,
                'discount_amount' => $discountAmount,
                'discount_currency' => $discountCurrency,
            ]);
        } else {
            CartItemModel::create([
                'cart_id' => $cartId,
                'product_variant_id' => $productVariantId,
                'quantity' => $quantity,
                'unit_price_amount' => $unitPriceAmount,
                'unit_price_currency' => $currency,
                'discount_amount' => $discountAmount,
                'discount_currency' => $discountCurrency,
            ]);
        }

        $this->touchLastActivity($cartId);
    }

    public function updateItemQuantity(int $cartItemId, int $quantity): void
    {
        $item = CartItemModel::findOrFail($cartItemId);
        $item->update(['quantity' => min($quantity, Cart::MAX_QUANTITY_PER_LINE)]);
        $this->touchLastActivity($item->cart_id);
    }

    public function removeItem(int $cartItemId): void
    {
        $item = CartItemModel::findOrFail($cartItemId);
        $cartId = $item->cart_id;
        $item->delete();
        $this->touchLastActivity($cartId);
    }

    public function setCartCoupon(int $cartId, string $couponCode, float $discountAmount, string $currency): void
    {
        CartCoupon::updateOrCreate(
            ['cart_id' => $cartId],
            [
                'coupon_code' => $couponCode,
                'discount_amount' => $discountAmount,
                'discount_currency' => $currency,
                'applied_at' => now(),
            ]
        );
        $this->touchLastActivity($cartId);
    }

    public function removeCartCoupon(int $cartId): void
    {
        CartCoupon::where('cart_id', $cartId)->delete();
        $this->touchLastActivity($cartId);
    }

    public function markAsConverted(int $cartId): void
    {
        CartModel::where('id', $cartId)->update(['status' => Cart::STATUS_CONVERTED]);
    }

    public function touchLastActivity(int $cartId): void
    {
        CartModel::where('id', $cartId)->update(['last_activity_at' => now()]);
    }

    private function toDomain(CartModel $model): Cart
    {
        $items = $model->items->map(fn (CartItemModel $i) => new CartItem(
            id: $i->id,
            cartId: $i->cart_id,
            productVariantId: $i->product_variant_id,
            quantity: $i->quantity,
            unitPriceAmount: (float) $i->unit_price_amount,
            unitPriceCurrency: $i->unit_price_currency,
            discountAmount: (float) $i->discount_amount,
            discountCurrency: $i->discount_currency,
        ))->all();

        $subtotal = array_sum(array_map(fn (CartItem $i) => $i->lineTotal(), $items));
        $coupon = $model->relationLoaded('appliedCoupon') ? $model->appliedCoupon : $model->appliedCoupon()->first();
        $discountAmount = $coupon ? (float) $coupon->discount_amount : 0.0;
        $total = max(0, $subtotal - $discountAmount);
        $appliedCouponCode = $coupon ? $coupon->coupon_code : null;

        return new Cart(
            id: $model->id,
            userId: $model->user_id,
            guestToken: $model->guest_token,
            currency: $model->currency,
            status: $model->status,
            items: $items,
            subtotalAmount: $subtotal,
            discountAmount: $discountAmount,
            totalAmount: $total,
            appliedCouponCode: $appliedCouponCode,
        );
    }
}
