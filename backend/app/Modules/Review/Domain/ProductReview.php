<?php

namespace App\Modules\Review\Domain;

class ProductReview
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly int $productId,
        public readonly int $rating,
        public readonly ?string $title,
        public readonly ?string $body,
        public readonly string $status,
        public readonly ?string $userName = null,
        public readonly ?string $createdAt = null,
    ) {
    }
}
