<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Central place for catalog cache keys and invalidation.
 * Used by CatalogService (read) and Admin controllers / repositories (invalidate).
 */
final class CatalogCache
{
    public static function forgetProduct(string $slug, ?int $id = null): void
    {
        Cache::forget("product:slug:{$slug}");
        if ($id !== null) {
            Cache::forget("product:{$id}");
        }
    }

    public static function forgetCategory(?string $slug = null): void
    {
        Cache::forget('categories:all');
        if ($slug !== null) {
            Cache::forget("category:slug:{$slug}");
        }
    }

    public static function forgetBrands(): void
    {
        Cache::forget('brands:all');
    }
}
