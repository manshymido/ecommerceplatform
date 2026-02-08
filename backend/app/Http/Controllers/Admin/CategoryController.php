<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\ResourceNotFoundException;
use App\Http\ApiMessages;
use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Modules\Catalog\Infrastructure\Models\Category;
use App\Support\CatalogCache;
use Illuminate\Http\JsonResponse;

/**
 * Admin controller for managing product categories.
 *
 * Handles CRUD operations with cache invalidation.
 */
class CategoryController extends ApiBaseController
{
    /**
     * List all categories with parent and children relationships.
     */
    public function index(): JsonResponse
    {
        $categories = Category::with(['parent', 'children'])
            ->orderBy('position')
            ->get();

        return $this->collection(CategoryResource::collection($categories));
    }

    /**
     * Create a new category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        return $this->tryAction(
            function () use ($request): Category {
                $category = Category::create($request->validated());
                CatalogCache::forgetCategory(null);

                return $category;
            },
            fn (Category $category) => $this->created(
                new CategoryResource($category->load('parent'))
            )
        );
    }

    /**
     * Display a single category with relationships.
     *
     * @throws ResourceNotFoundException
     */
    public function show(string $id): JsonResponse
    {
        $category = $this->findCategoryOrFail($id, ['parent', 'children', 'products']);

        return $this->data(new CategoryResource($category));
    }

    /**
     * Update an existing category.
     */
    public function update(UpdateCategoryRequest $request, string $id): JsonResponse
    {
        return $this->tryAction(
            function () use ($request, $id): Category {
                $category = Category::findOrFail($id);
                $category->update($request->validated());
                CatalogCache::forgetCategory($category->slug);

                return $category;
            },
            fn (Category $category) => $this->data(
                new CategoryResource($category->load('parent'))
            )
        );
    }

    /**
     * Delete a category.
     */
    public function destroy(string $id): JsonResponse
    {
        return $this->tryAction(
            function () use ($id): bool {
                $category = Category::findOrFail($id);
                $slug = $category->slug;
                $deleted = $category->delete();
                CatalogCache::forgetCategory($slug);

                return $deleted;
            },
            fn () => $this->deleted('Category')
        );
    }

    /**
     * Find category with eager loading or throw not found exception.
     *
     * @param array<string> $with
     * @throws ResourceNotFoundException
     */
    private function findCategoryOrFail(string $id, array $with = []): Category
    {
        $category = Category::with($with)->find($id);

        if (!$category) {
            throw new ResourceNotFoundException(ApiMessages::CATEGORY_NOT_FOUND);
        }

        return $category;
    }
}
