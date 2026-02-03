<?php

namespace App\Http\Controllers\Api;

use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Modules\Cart\Application\CartService;
use App\Modules\Order\Application\PlaceOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    use ResolvesCartContext;

    public function __construct(
        private CartService $cartService,
        private PlaceOrderService $placeOrderService
    ) {
    }

    /**
     * POST /checkout - Place order from current cart (auth or guest via X-Guest-Token).
     */
    public function store(CheckoutRequest $request): JsonResponse
    {
        $ctx = $this->cartContext($request);
        $cart = $this->cartService->getCart($ctx['user_id'], $ctx['guest_token']);

        if (! $cart) {
            return ApiResponse::unprocessable('Cart not found. Add items and try again.');
        }

        try {
            $order = $this->placeOrderService->placeOrder($cart, $request->validated());
        } catch (\DomainException $e) {
            return ApiResponse::fromDomainException($e);
        }

        return ApiResponse::data(new OrderResource($order), 201);
    }
}
