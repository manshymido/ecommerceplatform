<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Http\Resources\ShippingQuoteResource;
use App\Modules\Shipping\Application\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingController extends ApiBaseController
{
    public function __construct(
        private ShippingService $shippingService
    ) {
    }

    /**
     * GET /shipping/quotes - Get available shipping quotes for country and cart total (cart/checkout).
     * Query: country_code (required), cart_total (required), currency (optional, default USD), weight_kg (optional).
     */
    public function quotes(Request $request): JsonResponse
    {
        $request->validate([
            'country_code' => ['required', 'string', 'size:2'],
            'cart_total' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
        ]);

        $countryCode = strtoupper($request->input('country_code'));
        $cartTotal = (float) $request->input('cart_total');
        $currency = $request->input('currency', 'USD');
        $weightKg = (float) $request->input('weight_kg', 0);

        $quotes = $this->shippingService->getQuotes($countryCode, $cartTotal, $currency, $weightKg);

        return $this->collection(ShippingQuoteResource::collection(collect($quotes)));
    }
}
