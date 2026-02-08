<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\ResourceNotFoundException;
use App\Http\ApiMessages;
use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Catalog\Infrastructure\Models\ProductPrice;
use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use App\Modules\Inventory\Infrastructure\Models\StockItem as StockItemModel;
use App\Modules\Inventory\Infrastructure\Models\Warehouse;
use App\Services\SkuGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin controller for managing products.
 *
 * Handles CRUD operations with proper validation, error handling,
 * and consistent API responses.
 */
class ProductController extends ApiBaseController
{
    /**
     * List products with optional filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->buildProductQuery($request);

        $perPage = $this->getPerPage($request);
        $products = $query->paginate($perPage);

        return $this->paginated($products, ProductResource::collection($products));
    }

    /**
     * Build product query with filters applied.
     */
    private function buildProductQuery(Request $request): Builder
    {
        $query = Product::with(array_merge(Product::defaultEagerLoadsForList(), ['variants.prices']));

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function (Builder $q) use ($searchTerm): void {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('slug', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('variants', function (Builder $v) use ($searchTerm): void {
                        $v->where('sku', 'like', '%' . $searchTerm . '%');
                    });
            });
        }

        // Apply sorting
        $sortParams = $this->getSortParams($request, 'created_at', 'desc');
        $query->orderBy($sortParams['sort'], $sortParams['direction']);

        return $query;
    }

    /**
     * Create a new product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        return $this->tryAction(
            fn () => $this->createProduct($request),
            fn (Product $product) => $this->created(
                new ProductResource($product->load(['brand', 'categories']))
            )
        );
    }

    /**
     * Create product with category sync and optional default variant + price.
     */
    private function createProduct(StoreProductRequest $request): Product
    {
        return DB::transaction(function () use ($request): Product {
            $data = $request->validated();
            $defaultVariant = $data['default_variant'] ?? null;
            unset($data['default_variant']);

            $product = Product::create($data);

            if ($request->has('category_ids')) {
                $product->categories()->sync($request->input('category_ids'));
            }

            if (is_array($defaultVariant) && (isset($defaultVariant['price']) && $defaultVariant['price'] !== '' && $defaultVariant['price'] !== null)) {
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => SkuGenerator::generate($product->id, $defaultVariant['sku'] ?? null),
                    'name' => $defaultVariant['name'] ?? 'Default',
                    'attributes' => [],
                    'is_default' => true,
                ]);
                ProductPrice::create([
                    'product_variant_id' => $variant->id,
                    'currency' => 'USD',
                    'amount' => (float) $defaultVariant['price'],
                ]);
                // Create stock rows (quantity 0) in every warehouse so the variant appears in Stock and can be adjusted.
                $warehouseIds = Warehouse::pluck('id');
                foreach ($warehouseIds as $warehouseId) {
                    StockItemModel::create([
                        'product_variant_id' => $variant->id,
                        'warehouse_id' => $warehouseId,
                        'quantity' => 0,
                        'safety_stock' => 0,
                    ]);
                }
            }

            return $product;
        });
    }

    /**
     * Display a single product.
     */
    public function show(string $id): JsonResponse
    {
        $product = $this->findProductOrFail($id);

        return $this->data(new ProductResource($product));
    }

    /**
     * Update an existing product.
     */
    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        return $this->tryAction(
            fn () => $this->updateProduct($request, $id),
            fn (Product $product) => $this->data(
                new ProductResource($product->load(['brand', 'categories']))
            )
        );
    }

    /**
     * Update product with category sync in transaction.
     */
    private function updateProduct(UpdateProductRequest $request, string $id): Product
    {
        return DB::transaction(function () use ($request, $id): Product {
            $product = Product::findOrFail($id);
            $product->update($request->validated());

            if ($request->has('category_ids')) {
                $product->categories()->sync($request->input('category_ids'));
            }

            return $product;
        });
    }

    /**
     * Delete a product.
     */
    public function destroy(string $id): JsonResponse
    {
        return $this->tryAction(
            function () use ($id): bool {
                $product = Product::findOrFail($id);
                return $product->delete();
            },
            fn () => $this->deleted('Product')
        );
    }

    /**
     * Find product with eager loading or throw not found exception.
     *
     * @throws ResourceNotFoundException
     */
    private function findProductOrFail(string $id): Product
    {
        $product = Product::with(Product::defaultEagerLoads())->find($id);

        if (!$product) {
            throw new ResourceNotFoundException(ApiMessages::PRODUCT_NOT_FOUND);
        }

        return $product;
    }
}
