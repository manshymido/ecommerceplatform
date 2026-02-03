<?php

namespace App\Modules\Wishlist\Domain;

class WishlistItem
{
    public function __construct(
        public readonly int $id,
        public readonly int $wishlistId,
        public readonly int $productVariantId,
    ) {
    }
}
