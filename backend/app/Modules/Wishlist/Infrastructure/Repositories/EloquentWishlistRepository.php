<?php

namespace App\Modules\Wishlist\Infrastructure\Repositories;

use App\Modules\Wishlist\Domain\Wishlist;
use App\Modules\Wishlist\Domain\WishlistItem;
use App\Modules\Wishlist\Domain\WishlistRepository;
use App\Modules\Wishlist\Infrastructure\Models\Wishlist as WishlistModel;
use App\Modules\Wishlist\Infrastructure\Models\WishlistItem as WishlistItemModel;

class EloquentWishlistRepository implements WishlistRepository
{
    private const ITEM_RELATIONS = ['items.productVariant.product'];

    public function findById(int $id): ?Wishlist
    {
        $model = WishlistModel::with(self::ITEM_RELATIONS)->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByUser(int $userId): ?Wishlist
    {
        $model = WishlistModel::with(self::ITEM_RELATIONS)->where('user_id', $userId)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function getOrCreateForUser(int $userId): Wishlist
    {
        $model = WishlistModel::with(self::ITEM_RELATIONS)->firstOrCreate(
            ['user_id' => $userId],
            ['user_id' => $userId]
        );

        return $this->toDomain($model->load(self::ITEM_RELATIONS));
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
        $items = $model->items->map(function (WishlistItemModel $i) {
            $variant = $i->relationLoaded('productVariant') ? $i->productVariant : null;
            $product = $variant?->relationLoaded('product') ? $variant->product : null;

            return new WishlistItem(
                id: $i->id,
                wishlistId: $i->wishlist_id,
                productVariantId: $i->product_variant_id,
                variantName: $variant?->name,
                variantSku: $variant?->sku,
                productId: $product?->id,
                productName: $product?->name,
                productSlug: $product?->slug,
                productImageUrl: $product?->main_image_url,
            );
        })->all();

        return new Wishlist(
            id: $model->id,
            userId: $model->user_id,
            items: $items,
        );
    }
}
