<?php

namespace App\Modules\Review\Infrastructure\Repositories;

use App\Modules\Review\Domain\ProductReview;
use App\Modules\Review\Domain\ProductReviewRepository;
use App\Modules\Review\Infrastructure\Models\ProductReview as ProductReviewModel;

class EloquentProductReviewRepository implements ProductReviewRepository
{
    public function findById(int $id): ?ProductReview
    {
        $model = ProductReviewModel::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    /** @return ProductReview[] */
    public function findByProduct(int $productId, ?string $status = 'approved', int $limit = 50): array
    {
        $query = ProductReviewModel::with('user')
            ->where('product_id', $productId)
            ->orderByDesc('created_at')
            ->limit($limit);
        if ($status !== null) {
            $query->where('status', $status);
        }
        return $query->get()->map(fn ($m) => $this->toDomain($m))->all();
    }

    public function create(array $data): ProductReview
    {
        $model = ProductReviewModel::create([
            'user_id' => $data['user_id'],
            'product_id' => $data['product_id'],
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            'status' => $data['status'] ?? ProductReview::STATUS_PENDING,
        ]);
        return $this->toDomain($model);
    }

    public function updateStatus(int $id, string $status): void
    {
        ProductReviewModel::where('id', $id)->update(['status' => $status]);
    }

    private function toDomain(ProductReviewModel $model): ProductReview
    {
        return new ProductReview(
            id: $model->id,
            userId: $model->user_id,
            productId: $model->product_id,
            rating: (int) $model->rating,
            title: $model->title,
            body: $model->body,
            status: $model->status,
            userName: $model->relationLoaded('user') ? $model->user?->name : null,
            createdAt: $model->created_at?->toIso8601String(),
        );
    }
}
