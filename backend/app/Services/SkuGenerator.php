<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\Catalog\Infrastructure\Models\ProductVariant;

/**
 * Generates unique SKUs for product variants.
 * Format: RG-{productId}-{6 hex chars} when auto-generated.
 */
class SkuGenerator
{
    private const PREFIX = 'RG';

    private const RANDOM_BYTES = 3;

    private const MAX_ATTEMPTS = 5;

    /**
     * Return a SKU to use for a new variant: use provided if non-empty, else generate unique.
     */
    public static function generate(int $productId, ?string $provided = null): string
    {
        $trimmed = $provided !== null && $provided !== '' ? trim($provided) : null;
        if ($trimmed !== null && $trimmed !== '') {
            return $trimmed;
        }

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $sku = self::buildSku($productId);
            if (! ProductVariant::where('sku', $sku)->exists()) {
                return $sku;
            }
        }

        return self::buildSku($productId) . '-' . strtoupper(bin2hex(random_bytes(2)));
    }

    private static function buildSku(int $productId): string
    {
        return self::PREFIX . '-' . $productId . '-' . strtoupper(bin2hex(random_bytes(self::RANDOM_BYTES)));
    }
}
