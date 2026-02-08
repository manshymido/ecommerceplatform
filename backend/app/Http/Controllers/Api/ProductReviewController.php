<?php

namespace App\Http\Controllers\Api;

use App\Http\ApiMessages;
use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\StoreProductReviewRequest;
use App\Http\Resources\ProductReviewResource;
use App\Modules\Catalog\Application\CatalogService;
use App\Modules\Review\Application\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductReviewController extends ApiBaseController
{
    public function __construct(
        private CatalogService $catalogService,
        private ReviewService $reviewService
    ) {
    }

    /**
     * GET /products/{slug}/reviews - List approved reviews for a product (public).
     */
    public function index(Request $request, string $slug): JsonResponse
    {
        $product = $this->catalogService->getProductModelBySlug($slug);
        if (! $product) {
            return $this->notFound(ApiMessages::PRODUCT_NOT_FOUND);
        }

        $reviews = $this->reviewService->getReviewsForProduct($product->id, true, $this->getPerPage($request));

        return $this->collection(ProductReviewResource::collection(collect($reviews)));
    }

    /**
     * POST /products/{slug}/reviews - Create review (auth).
     */
    public function store(StoreProductReviewRequest $request, string $slug): JsonResponse
    {
        $product = $this->catalogService->getProductModelBySlug($slug);
        if (! $product) {
            return $this->notFound(ApiMessages::PRODUCT_NOT_FOUND);
        }

        $review = $this->reviewService->createReview(
            $request->user()->id,
            $product->id,
            (int) $request->validated()['rating'],
            $request->validated()['title'] ?? null,
            $request->validated()['body'] ?? null
        );

        return $this->data(new ProductReviewResource($review), 201);
    }
}
