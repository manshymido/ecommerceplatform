<?php

namespace App\Modules\Review\Domain;

interface ProductReviewRepository
{
    public function findById(int $id): ?ProductReview;

    public function findByProduct(int $productId, ?string $status = 'approved', int $limit = 50): array;

    public function create(array $data): ProductReview;

    public function updateStatus(int $id, string $status): void;
}
