<?php

namespace App\Http\Controllers\Api;

use App\Http\ApiMessages;
use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Modules\Catalog\Application\CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(
        private CatalogService $catalogService
    ) {
    }

    public function products(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'category_id', 'brand_id']);
        $query = $this->catalogService->getPublishedProducts($filters);
        $products = $query->paginate($request->get('per_page', 15));

        return ApiResponse::paginated($products, ProductResource::collection($products));
    }

    public function product(string $slug): JsonResponse
    {
        $model = $this->catalogService->getProductModelBySlug($slug);

        if (! $model) {
            return ApiResponse::notFound('Product not found');
        }

        return ApiResponse::data(new ProductResource($model));
    }

    public function categories(): JsonResponse
    {
        $categories = $this->catalogService->getAllCategories();

        return ApiResponse::collection(CategoryResource::collection($categories));
    }

    public function category(string $slug): JsonResponse
    {
        $category = $this->catalogService->getCategoryBySlug($slug);

        if (! $category) {
            return ApiResponse::notFound(ApiMessages::CATEGORY_NOT_FOUND);
        }

        return ApiResponse::data(new CategoryResource($category));
    }

    public function brands(): JsonResponse
    {
        $brands = $this->catalogService->getAllBrands();

        return ApiResponse::collection(BrandResource::collection($brands));
    }
}
