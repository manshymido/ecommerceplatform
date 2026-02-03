<?php

namespace App\Modules\Catalog\Domain;

class ProductVariant
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $productId,
        public readonly string $sku,
        public readonly string $name,
        public readonly ?array $attributes,
        public readonly bool $isDefault,
    ) {
    }
}
