<?php

namespace App\Modules\Wishlist\Domain;

class Wishlist
{
    /**
     * @param  WishlistItem[]  $items
     */
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        /** @var WishlistItem[] */
        public readonly array $items,
    ) {
    }
}
