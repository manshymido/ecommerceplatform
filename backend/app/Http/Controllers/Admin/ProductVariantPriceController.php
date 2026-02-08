<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\ResourceNotFoundException;
use App\Http\ApiMessages;
use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Admin\UpdateVariantPriceRequest;
use App\Http\Resources\ProductPriceResource;
use App\Modules\Catalog\Infrastructure\Models\ProductPrice;
use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use App\Support\CatalogCache;
use Illuminate\Http\JsonResponse;

class ProductVariantPriceController extends ApiBaseController
{
    /**
     * Update or create price for a product variant (e.g. USD).
     *
     * @throws ResourceNotFoundException
     */
    public function update(UpdateVariantPriceRequest $request, int $variant): JsonResponse
    {
        $variantModel = ProductVariant::find($variant);
        if (! $variantModel) {
            throw new ResourceNotFoundException(ApiMessages::VARIANT_NOT_FOUND);
        }

        $currency = strtoupper($request->input('currency', 'USD'));
        $amount = (float) $request->input('amount');
        $compareAtAmount = $request->has('compare_at_amount')
            ? (float) $request->input('compare_at_amount')
            : null;

        $price = ProductPrice::updateOrCreate(
            [
                'product_variant_id' => $variantModel->id,
                'currency' => $currency,
            ],
            [
                'amount' => $amount,
                'compare_at_amount' => $compareAtAmount,
            ]
        );

        CatalogCache::forgetVariantPrice($variantModel->id, $currency);

        return $this->data(new ProductPriceResource($price));
    }
}
