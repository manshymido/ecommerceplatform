<?php

namespace App\Modules\Catalog\Domain;

class Product
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $slug,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?int $brandId,
        public readonly string $status,
        public readonly ?string $mainImageUrl,
        public readonly ?string $seoTitle,
        public readonly ?string $seoDescription,
    ) {
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }
}
