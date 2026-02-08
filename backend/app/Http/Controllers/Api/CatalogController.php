<?php

namespace App\Http\Controllers\Api;

use App\Http\ApiMessages;
use App\Http\Controllers\ApiBaseController;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Modules\Catalog\Application\CatalogService;
use App\Modules\Inventory\Application\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends ApiBaseController
{
    public function __construct(
        private CatalogService $catalogService,
        private InventoryService $inventoryService
    ) {
    }

    public function products(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'category_id', 'brand_id']);
        $query = $this->catalogService->getPublishedProducts($filters);
        $products = $query->paginate($this->getPerPage($request));

        $availability = [];
        $variantIds = $products->pluck('variants')->flatten()->pluck('id')->unique()->filter()->values()->all();
        if ($variantIds !== []) {
            $results = $this->inventoryService->checkAvailability(array_fill_keys($variantIds, 1));
            foreach ($results as $r) {
                $availability[$r->productVariantId] = $r->availableQty;
            }
        }
        foreach ($products as $product) {
            foreach ($product->variants ?? [] as $variant) {
                $variant->available_quantity = $availability[$variant->id] ?? 0;
            }
        }

        return $this->paginated($products, ProductResource::collection($products));
    }

    public function searchSuggestions(Request $request): JsonResponse
    {
        $q = $request->get('q', '');
        $limit = min((int) $request->get('limit', 10), 20);
        $suggestions = $this->catalogService->getSearchSuggestions($q, $limit);

        return $this->success(['data' => $suggestions]);
    }

    public function product(string $slug): JsonResponse
    {
        $model = $this->catalogService->getProductModelBySlug($slug);

        if (! $model) {
            return $this->notFound(ApiMessages::PRODUCT_NOT_FOUND);
        }

        $availability = [];
        if ($model->relationLoaded('variants') && $model->variants->isNotEmpty()) {
            $variantIds = $model->variants->pluck('id')->all();
            $results = $this->inventoryService->checkAvailability(
                array_fill_keys($variantIds, 1)
            );
            foreach ($results as $r) {
                $availability[$r->productVariantId] = $r->availableQty;
            }
        }

        return $this->data(new ProductResource($model, $availability));
    }

    public function categories(): JsonResponse
    {
        $categories = $this->catalogService->getAllCategories();

        return $this->collection(CategoryResource::collection($categories));
    }

    public function category(string $slug): JsonResponse
    {
        $category = $this->catalogService->getCategoryBySlug($slug);

        if (! $category) {
            return $this->notFound(ApiMessages::CATEGORY_NOT_FOUND);
        }

        return $this->data(new CategoryResource($category));
    }

    public function brands(): JsonResponse
    {
        $brands = $this->catalogService->getAllBrands();

        return $this->collection(BrandResource::collection($brands));
    }
}
