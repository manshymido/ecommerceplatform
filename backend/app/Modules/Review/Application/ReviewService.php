<?php

namespace App\Modules\Review\Application;

use App\Modules\Review\Domain\ProductReview;
use App\Modules\Review\Domain\ProductReviewRepository;

class ReviewService
{
    public function __construct(
        private ProductReviewRepository $productReviewRepository
    ) {
    }

    /** @return ProductReview[] */
    public function getReviewsForProduct(int $productId, bool $approvedOnly = true, int $limit = 50): array
    {
        return $this->productReviewRepository->findByProduct(
            $productId,
            $approvedOnly ? ProductReview::STATUS_APPROVED : null,
            $limit
        );
    }

    public function createReview(int $userId, int $productId, int $rating, ?string $title = null, ?string $body = null): ProductReview
    {
        return $this->productReviewRepository->create([
            'user_id' => $userId,
            'product_id' => $productId,
            'rating' => $rating,
            'title' => $title,
            'body' => $body,
            'status' => ProductReview::STATUS_PENDING,
        ]);
    }

    public function moderateReview(int $reviewId, string $status): void
    {
        $this->productReviewRepository->updateStatus($reviewId, $status);
    }
}
