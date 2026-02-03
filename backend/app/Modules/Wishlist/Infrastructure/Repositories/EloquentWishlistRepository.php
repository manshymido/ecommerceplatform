<?php

namespace App\Modules\Wishlist\Infrastructure\Repositories;

use App\Modules\Wishlist\Domain\Wishlist;
use App\Modules\Wishlist\Domain\WishlistItem;
use App\Modules\Wishlist\Domain\WishlistRepository;
use App\Modules\Wishlist\Infrastructure\Models\Wishlist as WishlistModel;
use App\Modules\Wishlist\Infrastructure\Models\WishlistItem as WishlistItemModel;

class EloquentWishlistRepository implements WishlistRepository
{
    public function findById(int $id): ?Wishlist
    {
        $model = WishlistModel::with('items')->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByUser(int $userId): ?Wishlist
    {
        $model = WishlistModel::with('items')->where('user_id', $userId)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function getOrCreateForUser(int $userId): Wishlist
    {
        $model = WishlistModel::with('items')->firstOrCreate(
            ['user_id' => $userId],
            ['user_id' => $userId]
        );

        return $this->toDomain($model->load('items'));
    }

    public function addItem(int $wishlistId, int $productVariantId): void
    {
        WishlistItemModel::firstOrCreate(
            ['wishlist_id' => $wishlistId, 'product_variant_id' => $productVariantId],
            ['wishlist_id' => $wishlistId, 'product_variant_id' => $productVariantId]
        );
    }

    public function removeItem(int $wishlistItemId): void
    {
        WishlistItemModel::where('id', $wishlistItemId)->delete();
    }

    private function toDomain(WishlistModel $model): Wishlist
    {
        $items = $model->items->map(fn (WishlistItemModel $i) => new WishlistItem(
            id: $i->id,
            wishlistId: $i->wishlist_id,
            productVariantId: $i->product_variant_id,
        ))->all();

        return new Wishlist(
            id: $model->id,
            userId: $model->user_id,
            items: $items,
        );
    }
}
