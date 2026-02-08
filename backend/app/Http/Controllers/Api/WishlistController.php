<?php

namespace App\Http\Controllers\Api;

use App\Http\ApiMessages;
use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\AddWishlistItemRequest;
use App\Http\Resources\WishlistResource;
use App\Modules\Wishlist\Application\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends ApiBaseController
{
    public function __construct(
        private WishlistService $wishlistService
    ) {
    }

    /**
     * GET /wishlist - Get current user's wishlist.
     */
    public function show(Request $request): JsonResponse
    {
        $wishlist = $this->wishlistService->getOrCreateWishlist($request->user()->id);

        return $this->data(new WishlistResource($wishlist));
    }

    /**
     * POST /wishlist/items - Add product variant to wishlist.
     */
    public function addItem(AddWishlistItemRequest $request): JsonResponse
    {
        $wishlist = $this->wishlistService->addItem(
            $request->user()->id,
            (int) $request->validated()['product_variant_id']
        );

        return $this->data(new WishlistResource($wishlist), 201);
    }

    /**
     * DELETE /wishlist/items/{id} - Remove item from wishlist.
     */
    public function removeItem(Request $request, int $id): JsonResponse
    {
        $wishlist = $this->wishlistService->removeItem($request->user()->id, $id);
        if ($wishlist === null) {
            return $this->notFound(ApiMessages::WISHLIST_ITEM_NOT_FOUND);
        }

        return $this->data(new WishlistResource($wishlist));
    }
}
