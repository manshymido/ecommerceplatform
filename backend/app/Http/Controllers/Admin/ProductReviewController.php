<?php

namespace App\Http\Controllers\Admin;

use App\Http\ApiMessages;
use App\Http\Controllers\ApiBaseController;
use App\Http\Resources\ProductReviewResource;
use App\Modules\Review\Application\ReviewService;
use App\Modules\Review\Domain\ProductReviewRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductReviewController extends ApiBaseController
{
    public function __construct(
        private ProductReviewRepository $productReviewRepository,
        private ReviewService $reviewService
    ) {
    }

    /**
     * GET /admin/reviews - List all reviews (optionally filter by status).
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->get('status');
        $productId = $request->get('product_id');
        $query = \App\Modules\Review\Infrastructure\Models\ProductReview::query()->with(['user', 'product'])
            ->orderByDesc('created_at');
        if ($status !== null) {
            $query->where('status', $status);
        }
        if ($productId !== null) {
            $query->where('product_id', $productId);
        }
        $reviews = $query->paginate($this->getPerPage($request));

        return $this->paginated($reviews, ProductReviewResource::collection($reviews));
    }

    /**
     * PATCH /admin/reviews/{id} - Moderate review (approve/reject).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $review = $this->productReviewRepository->findById($id);
        if (! $review) {
            return $this->notFound(ApiMessages::REVIEW_NOT_FOUND);
        }

        $request->validate([
            'status' => ['required', 'string', 'in:approved,rejected'],
        ]);

        $this->reviewService->moderateReview($id, $request->input('status'));
        $review = $this->productReviewRepository->findById($id);

        return $this->data(new ProductReviewResource($review));
    }
}
