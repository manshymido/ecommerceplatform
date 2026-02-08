<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Reset all business data (products, categories, orders, stock, etc.) but keep users and auth.
 */
Artisan::command('db:reset-except-users', function () {
    $driver = DB::getDriverName();

    // Tables to clear (child tables first for FK order; SQLite may still need FK disabled)
    $tables = [
        'product_reviews',
        'wishlist_items',
        'wishlists',
        'shipments',
        'refunds',
        'payments',
        'order_status_history',
        'order_lines',
        'orders',
        'coupon_redemptions',
        'cart_coupons',
        'cart_items',
        'carts',
        'stock_movements',
        'stock_reservations',
        'stock_items',
        'warehouses',
        'coupons',
        'promotions',
        'shipping_method_zones',
        'shipping_methods',
        'product_prices',
        'product_variants',
        'category_product',
        'products',
        'categories',
        'brands',
        'user_addresses',
        'personal_access_tokens',
    ];

    if ($driver === 'sqlite') {
        DB::statement('PRAGMA foreign_keys = OFF');
    } elseif ($driver === 'mysql') {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
    }

    foreach ($tables as $table) {
        if (! Schema::hasTable($table)) {
            continue;
        }
        $count = DB::table($table)->count();
        DB::table($table)->delete();
        $this->info("Cleared: {$table} ({$count} rows)");
    }

    if ($driver === 'sqlite') {
        DB::statement('PRAGMA foreign_keys = ON');
    } elseif ($driver === 'mysql') {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    $this->info('Done. Users, roles, permissions, cache, jobs, and sessions were kept.');
    $this->comment('Run: php artisan db:seed --class=LargeDatasetSeeder (or DatabaseSeeder) to re-seed catalog if needed.');
})->purpose('Reset products, categories, orders, stock, and all business data; keep users');
