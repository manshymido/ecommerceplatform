<?php

namespace App\Http\Controllers\Api;

use App\Http\ApiMessages;
use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\ApplyCouponRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Modules\Cart\Application\CartService;
use App\Modules\Cart\Domain\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ResolvesCartContext;

    public function __construct(
        private CartService $cartService
    ) {
    }

    protected function resolveCart(Request $request): ?Cart
    {
        $ctx = $this->cartContext($request, true);

        return $this->cartService->getCart($ctx['user_id'], $ctx['guest_token']);
    }

    protected function getOrCreateCart(Request $request): Cart
    {
        $ctx = $this->cartContext($request, true);

        return $this->cartService->getOrCreateCart($ctx['user_id'], $ctx['guest_token'], $ctx['currency']);
    }

    /**
     * Resolve cart; if missing return 404. Run action (may return ['error' => message] for 422). Then return cart data.
     */
    protected function withResolvedCart(Request $request, callable $action): JsonResponse
    {
        $cart = $this->resolveCart($request);
        if (! $cart) {
            return ApiResponse::notFound(ApiMessages::CART_NOT_FOUND);
        }
        $result = $action($cart);
        if (is_array($result) && isset($result['error'])) {
            return ApiResponse::unprocessable($result['error']);
        }
        $cart = $this->cartService->getCart($cart->userId, $cart->guestToken);

        return ApiResponse::data(new CartResource($cart));
    }

    /**
     * GET /cart - Get current cart (by auth or X-Guest-Token). Creates guest cart if neither provided.
     */
    public function show(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);

        return ApiResponse::data(new CartResource($cart));
    }

    /**
     * POST /cart/items - Add or update item in cart.
     */
    public function addItem(AddToCartRequest $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);
        $validated = $request->validated();
        $quantity = $validated['quantity'] ?? 1;

        try {
            $cart = $this->cartService->addItem($cart, (int) $validated['product_variant_id'], (int) $quantity);
        } catch (\DomainException $e) {
            return ApiResponse::fromDomainException($e);
        }

        return ApiResponse::data(new CartResource($cart));
    }

    /**
     * PATCH /cart/items/{item} - Update cart item quantity (0 = remove).
     */
    public function updateItem(UpdateCartItemRequest $request, int $item): JsonResponse
    {
        return $this->withResolvedCart($request, function (Cart $cart) use ($request, $item): void {
            $this->cartService->updateItemQuantity($item, (int) $request->validated()['quantity']);
        });
    }

    /**
     * DELETE /cart/items/{item} - Remove item from cart.
     */
    public function removeItem(Request $request, int $item): JsonResponse
    {
        return $this->withResolvedCart($request, fn (Cart $cart) => $this->cartService->removeItem($item));
    }

    /**
     * POST /cart/coupon - Apply coupon code.
     */
    public function applyCoupon(ApplyCouponRequest $request): JsonResponse
    {
        return $this->withResolvedCart($request, function (Cart $cart) use ($request): ?array {
            $result = $this->cartService->applyCoupon($cart, $request->validated()['code']);
            if (! ($result['valid'] ?? false)) {
                return ['error' => $result['message'] ?? 'Invalid coupon'];
            }

            return null;
        });
    }

    /**
     * DELETE /cart/coupon - Remove applied coupon.
     */
    public function removeCoupon(Request $request): JsonResponse
    {
        return $this->withResolvedCart($request, fn (Cart $cart) => $this->cartService->removeCoupon($cart));
    }
}
