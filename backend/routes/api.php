<?php

use App\Http\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

// Webhooks (no auth; verify signature in controller)
Route::post('/webhooks/stripe', [App\Http\Controllers\Webhook\StripeWebhookController::class, 'handle']);

// Public Catalog routes (storefront)
Route::get('/products', [App\Http\Controllers\Api\CatalogController::class, 'products']);
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
Route::middleware('optional_sanctum')->post('/checkout', [App\Http\Controllers\Api\CheckoutController::class, 'store']);

// Shipping quotes (public: for cart/checkout)
Route::get('/shipping/quotes', [App\Http\Controllers\Api\ShippingController::class, 'quotes']);

// Public routes
Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    $token = $user->createToken('api-token')->plainTextToken;

    return ApiResponse::success([
        'token' => $token,
        'user' => $user->load('roles'),
    ]);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return ApiResponse::success(['user' => $request->user()->load('roles')]);
    });

    // Customer orders
    Route::get('/orders', [App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::get('/orders/{id}', [App\Http\Controllers\Api\OrderController::class, 'show']);
    Route::post('/orders/{id}/pay', [App\Http\Controllers\Api\OrderPaymentController::class, 'store']);

    // Wishlist
    Route::get('/wishlist', [App\Http\Controllers\Api\WishlistController::class, 'show']);
    Route::post('/wishlist/items', [App\Http\Controllers\Api\WishlistController::class, 'addItem']);
    Route::delete('/wishlist/items/{id}', [App\Http\Controllers\Api\WishlistController::class, 'removeItem']);

    // Product reviews (create)
    Route::post('/products/{slug}/reviews', [App\Http\Controllers\Api\ProductReviewController::class, 'store']);

    // Admin only routes
    Route::middleware('role:admin|super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', function (Request $request) {
            return ApiResponse::success([
                'message' => 'Admin Dashboard',
                'user' => $request->user()->load('roles'),
            ]);
        });

        // Admin Catalog Management
        Route::apiResource('products', App\Http\Controllers\Admin\ProductController::class);
        Route::apiResource('categories', App\Http\Controllers\Admin\CategoryController::class);
        Route::apiResource('brands', App\Http\Controllers\Admin\BrandController::class);

        // Admin Inventory
        Route::apiResource('warehouses', App\Http\Controllers\Admin\WarehouseController::class);
        Route::get('stock', [App\Http\Controllers\Admin\StockController::class, 'index']);
        Route::post('stock/adjust', [App\Http\Controllers\Admin\StockController::class, 'adjust']);
        Route::get('stock/movements', [App\Http\Controllers\Admin\StockController::class, 'movements']);

        // Admin Orders
        Route::get('orders', [App\Http\Controllers\Admin\OrderController::class, 'index']);
        Route::get('orders/{id}', [App\Http\Controllers\Admin\OrderController::class, 'show']);
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
