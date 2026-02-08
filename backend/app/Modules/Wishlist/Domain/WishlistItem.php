<?php

namespace App\Modules\Wishlist\Domain;

class WishlistItem
{
    public function __construct(
        public readonly int $id,
        public readonly int $wishlistId,
        public readonly int $productVariantId,
        public readonly ?string $variantName = null,
        public readonly ?string $variantSku = null,
        public readonly ?int $productId = null,
        public readonly ?string $productName = null,
        public readonly ?string $productSlug = null,
        public readonly ?string $productImageUrl = null,
    ) {
    }
}
