<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\ResourceNotFoundException;
use App\Http\ApiMessages;
use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\ApplyCouponRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Modules\Cart\Application\CartService;
use App\Modules\Cart\Domain\Cart;
use App\Modules\Inventory\Application\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cart controller for managing shopping cart operations.
 *
 * Supports both authenticated users and guest carts via X-Guest-Token header.
 */
class CartController extends ApiBaseController
{
    use ResolvesCartContext;

    public function __construct(
        private readonly CartService $cartService,
        private readonly InventoryService $inventoryService
    ) {
    }

    /**
     * Build availability map for cart items and return cart as JSON response.
     *
     * @return array<int, int> variant_id => available_quantity
     */
    private function cartAvailability(Cart $cart): array
    {
        $items = $cart->items ?? [];
        if ($items === []) {
            return [];
        }
        $variantIds = array_map(fn ($i) => $i->productVariantId, $items);
        $variantQuantities = array_fill_keys(array_unique($variantIds), 1);
        $results = $this->inventoryService->checkAvailability($variantQuantities, null);
        $availability = [];
        foreach ($results as $r) {
            $availability[$r->productVariantId] = $r->availableQty;
        }

        return $availability;
    }

    private function cartResponse(Cart $cart): JsonResponse
    {
        $availability = $this->cartAvailability($cart);

        return $this->data(new CartResource($cart, $availability));
    }

    /**
     * Resolve the current cart for the request context.
     */
    protected function resolveCart(Request $request): ?Cart
    {
        $ctx = $this->cartContext($request, true);

        return $this->cartService->getCart($ctx['user_id'], $ctx['guest_token']);
    }

    /**
     * Get or create a cart for the request context.
     */
    protected function getOrCreateCart(Request $request): Cart
    {
        $ctx = $this->cartContext($request, true);

        return $this->cartService->getOrCreateCart(
            $ctx['user_id'],
            $ctx['guest_token'],
            $ctx['currency']
        );
    }

    /**
     * Execute an action with a resolved cart.
     *
     * Returns 404 if cart doesn't exist, 422 if action returns an error,
     * otherwise returns the updated cart data.
     *
     * @throws ResourceNotFoundException
     */
    protected function withResolvedCart(Request $request, callable $action): JsonResponse
    {
        $cart = $this->resolveCart($request);

        if (!$cart) {
            throw new ResourceNotFoundException(ApiMessages::CART_NOT_FOUND);
        }

        $result = $action($cart);

        if (is_array($result) && isset($result['error'])) {
            return $this->unprocessable($result['error']);
        }

        // Refresh cart data after action
        $cart = $this->cartService->getCart($cart->userId, $cart->guestToken);

        return $this->cartResponse($cart);
    }

    /**
     * Get current cart or create if doesn't exist.
     */
    public function show(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);

        return $this->cartResponse($cart);
    }

    /**
     * Merge guest cart into the authenticated user's cart. Requires X-Guest-Token header.
     */
    public function merge(Request $request): JsonResponse
    {
        $guestToken = $request->header('X-Guest-Token');
        $user = $request->user();
        $currency = $request->input('currency', 'USD');

        if (!$guestToken || trim($guestToken) === '') {
            $cart = $this->cartService->getOrCreateCart($user->id, null, $currency);
            return $this->cartResponse($cart);
        }

        return $this->tryAction(
            fn (): Cart => $this->cartService->mergeGuestCartIntoUser($user->id, trim($guestToken), $currency),
            fn (Cart $cart) => $this->cartResponse($cart)
        );
    }

    /**
     * Add an item to the cart.
     */
    public function addItem(AddToCartRequest $request): JsonResponse
    {
        return $this->tryAction(
            function () use ($request): Cart {
                $cart = $this->getOrCreateCart($request);
                $validated = $request->validated();
                $quantity = $validated['quantity'] ?? 1;

                return $this->cartService->addItem(
                    $cart,
                    (int) $validated['product_variant_id'],
                    (int) $quantity
                );
            },
            fn (Cart $cart) => $this->cartResponse($cart)
        );
    }

    /**
     * Update cart item quantity.
     *
     * Setting quantity to 0 removes the item.
     */
    public function updateItem(UpdateCartItemRequest $request, int $item): JsonResponse
    {
        return $this->withResolvedCart($request, function (Cart $cart) use ($request, $item): void {
            $this->cartService->updateItemQuantity(
                $item,
                (int) $request->validated()['quantity']
            );
        });
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(Request $request, int $item): JsonResponse
    {
        return $this->withResolvedCart(
            $request,
            fn (Cart $cart) => $this->cartService->removeItem($item)
        );
    }

    /**
     * Apply a coupon code to the cart.
     *
     * @throws BusinessRuleException
     */
    public function applyCoupon(ApplyCouponRequest $request): JsonResponse
    {
        return $this->withResolvedCart($request, function (Cart $cart) use ($request): ?array {
            $result = $this->cartService->applyCoupon(
                $cart,
                $request->validated()['code']
            );

            if (!($result['valid'] ?? false)) {
                return ['error' => $result['message'] ?? 'Invalid coupon'];
            }

            return null;
        });
    }

    /**
     * Remove the applied coupon from the cart.
     */
    public function removeCoupon(Request $request): JsonResponse
    {
        return $this->withResolvedCart(
            $request,
            fn (Cart $cart) => $this->cartService->removeCoupon($cart)
        );
    }
}
