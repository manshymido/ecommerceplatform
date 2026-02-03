<?php

namespace App\Modules\Wishlist\Application;

use App\Modules\Wishlist\Domain\Wishlist;
use App\Modules\Wishlist\Domain\WishlistRepository;

class WishlistService
{
    public function __construct(
        private WishlistRepository $wishlistRepository
    ) {
    }

    public function getOrCreateWishlist(int $userId): Wishlist
    {
        return $this->wishlistRepository->getOrCreateForUser($userId);
    }

    public function addItem(int $userId, int $productVariantId): Wishlist
    {
        $wishlist = $this->wishlistRepository->getOrCreateForUser($userId);
        $this->wishlistRepository->addItem($wishlist->id, $productVariantId);

        return $this->wishlistRepository->findById($wishlist->id);
    }

    public function removeItem(int $userId, int $wishlistItemId): ?Wishlist
    {
        $wishlist = $this->wishlistRepository->findByUser($userId);
        if (! $wishlist) {
            return null;
        }
        $belongsToWishlist = collect($wishlist->items)->contains(fn ($i) => $i->id === $wishlistItemId);
        if (! $belongsToWishlist) {
            return null;
        }
        $this->wishlistRepository->removeItem($wishlistItemId);

        return $this->wishlistRepository->findById($wishlist->id);
    }
}
