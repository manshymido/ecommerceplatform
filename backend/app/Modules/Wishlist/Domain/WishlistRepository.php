<?php

namespace App\Modules\Wishlist\Domain;

interface WishlistRepository
{
    public function findById(int $id): ?Wishlist;

    public function findByUser(int $userId): ?Wishlist;

    public function getOrCreateForUser(int $userId): Wishlist;

    public function addItem(int $wishlistId, int $productVariantId): void;

    public function removeItem(int $wishlistItemId): void;
}
