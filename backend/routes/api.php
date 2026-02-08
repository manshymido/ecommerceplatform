<?php

use Illuminate\Support\Facades\Route;

// Webhooks (no auth; verify signature in controller)
Route::post('/webhooks/stripe', [App\Http\Controllers\Webhook\StripeWebhookController::class, 'handle']);

// Public Catalog routes (storefront)
Route::get('/products', [App\Http\Controllers\Api\CatalogController::class, 'products']);
Route::get('/products/suggestions', [App\Http\Controllers\Api\CatalogController::class, 'searchSuggestions']);
Route::get('/products/{slug}', [App\Http\Controllers\Api\CatalogController::class, 'product']);
Route::get('/products/{slug}/reviews', [App\Http\Controllers\Api\ProductReviewController::class, 'index']);
Route::get('/categories', [App\Http\Controllers\Api\CatalogController::class, 'categories']);
Route::get('/categories/{slug}', [App\Http\Controllers\Api\CatalogController::class, 'category']);
Route::get('/brands', [App\Http\Controllers\Api\CatalogController::class, 'brands']);

// Cart (optional auth: Bearer token or X-Guest-Token header)
Route::middleware('optional_sanctum')->prefix('cart')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\CartController::class, 'show']);
    Route::post('/items', [App\Http\Controllers\Api\CartController::class, 'addItem']);
    Route::patch('/items/{item}', [App\Http\Controllers\Api\CartController::class, 'updateItem']);
    Route::delete('/items/{item}', [App\Http\Controllers\Api\CartController::class, 'removeItem']);
    Route::post('/coupon', [App\Http\Controllers\Api\CartController::class, 'applyCoupon']);
    Route::delete('/coupon', [App\Http\Controllers\Api\CartController::class, 'removeCoupon']);
});

// Checkout (optional auth: place order from cart)
Route::middleware('optional_sanctum')->group(function () {
    Route::post('/checkout', [App\Http\Controllers\Api\CheckoutController::class, 'store']);
    Route::post('/checkout/payment-intent', [App\Http\Controllers\Api\CheckoutPaymentIntentController::class, 'store']);
});

// Shipping quotes (public: for cart/checkout)
Route::get('/shipping/quotes', [App\Http\Controllers\Api\ShippingController::class, 'quotes']);

// Guest order lookup (public, throttled to prevent abuse)
Route::middleware('throttle:10,1')->get('/orders/lookup', [App\Http\Controllers\Api\OrderLookupController::class, 'show']);

// Auth (public, throttled to prevent brute force)
Route::middleware('throttle:auth')->group(function () {
    Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [App\Http\Controllers\Api\AuthController::class, 'user']);
    Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);

    // Cart merge (guest cart into user cart; send X-Guest-Token)
    Route::post('/cart/merge', [App\Http\Controllers\Api\CartController::class, 'merge']);

    // Customer orders
    Route::get('/orders', [App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::get('/orders/{id}', [App\Http\Controllers\Api\OrderController::class, 'show']);
    Route::post('/orders/{id}/pay', [App\Http\Controllers\Api\OrderPaymentController::class, 'store']);
    Route::post('/orders/{id}/pay/confirm', [App\Http\Controllers\Api\OrderPaymentController::class, 'confirm']);

    // Wishlist
    Route::get('/wishlist', [App\Http\Controllers\Api\WishlistController::class, 'show']);
    Route::post('/wishlist/items', [App\Http\Controllers\Api\WishlistController::class, 'addItem']);
    Route::delete('/wishlist/items/{id}', [App\Http\Controllers\Api\WishlistController::class, 'removeItem']);

    // Product reviews (create)
    Route::post('/products/{slug}/reviews', [App\Http\Controllers\Api\ProductReviewController::class, 'store']);

    // Admin only routes
    Route::middleware('role:admin|super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', App\Http\Controllers\Admin\DashboardController::class);

        // Admin Catalog Management
        Route::apiResource('products', App\Http\Controllers\Admin\ProductController::class);
        Route::patch('product-variants/{variant}/price', [App\Http\Controllers\Admin\ProductVariantPriceController::class, 'update']);
        Route::apiResource('categories', App\Http\Controllers\Admin\CategoryController::class);
        Route::apiResource('brands', App\Http\Controllers\Admin\BrandController::class);

        // Admin Inventory
        Route::apiResource('warehouses', App\Http\Controllers\Admin\WarehouseController::class);
        Route::get('stock', [App\Http\Controllers\Admin\StockController::class, 'index']);
        Route::get('stock/by-variant', [App\Http\Controllers\Admin\StockController::class, 'byVariant']);
        Route::post('stock/adjust', [App\Http\Controllers\Admin\StockController::class, 'adjust']);
        Route::post('stock/assign', [App\Http\Controllers\Admin\StockController::class, 'assign']);
        Route::get('stock/movements', [App\Http\Controllers\Admin\StockController::class, 'movements']);

        // Admin Orders
        Route::get('orders', [App\Http\Controllers\Admin\OrderController::class, 'index']);
        Route::get('orders/{id}', [App\Http\Controllers\Admin\OrderController::class, 'show']);
        Route::get('orders/{id}/payments', [App\Http\Controllers\Admin\OrderController::class, 'payments']);
        Route::get('orders/{orderId}/shipments', [App\Http\Controllers\Admin\ShipmentController::class, 'index']);
        Route::post('orders/{orderId}/shipments', [App\Http\Controllers\Admin\ShipmentController::class, 'store']);

        // Admin Shipments (update)
        Route::patch('shipments/{id}', [App\Http\Controllers\Admin\ShipmentController::class, 'update']);

        // Admin Reviews (moderation)
        Route::get('reviews', [App\Http\Controllers\Admin\ProductReviewController::class, 'index']);
        Route::patch('reviews/{id}', [App\Http\Controllers\Admin\ProductReviewController::class, 'update']);

        // Admin Refunds
        Route::post('payments/{id}/refund', [App\Http\Controllers\Admin\RefundController::class, 'store']);
    });
});
