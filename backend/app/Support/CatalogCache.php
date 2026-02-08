<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Central place for catalog cache keys and invalidation.
 * Used by CatalogService (read) and Admin controllers / repositories (invalidate).
 *
 * Keys MUST match the prefix used by CatalogService (BaseService::buildCacheKey)
 * which prefixes all keys with "catalog:".
 */
final class CatalogCache
{
    private const PREFIX = 'catalog';

    public static function forgetProduct(string $slug, ?int $id = null): void
    {
        Cache::forget(self::key("product:slug:{$slug}"));
        if ($id !== null) {
            Cache::forget(self::key("product:{$id}"));
        }
    }

    public static function forgetCategory(?string $slug = null): void
    {
        Cache::forget(self::key('categories:all'));
        if ($slug !== null) {
            Cache::forget(self::key("category:slug:{$slug}"));
        }
    }

    public static function forgetBrands(): void
    {
        Cache::forget(self::key('brands:all'));
    }

    public static function forgetVariantPrice(int $productVariantId, string $currency): void
    {
        Cache::forget(self::key("variant_price:{$productVariantId}:{$currency}"));
    }

    private static function key(string $key): string
    {
        return self::PREFIX . ':' . $key;
    }
}
