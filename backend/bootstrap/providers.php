<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Modules\Catalog\CatalogServiceProvider::class,
    App\Modules\Inventory\InventoryServiceProvider::class,
    App\Modules\Cart\CartServiceProvider::class,
    App\Modules\Order\OrderServiceProvider::class,
    App\Modules\Payment\PaymentServiceProvider::class,
    App\Modules\Shipping\ShippingServiceProvider::class,
    App\Modules\Wishlist\WishlistServiceProvider::class,
    App\Modules\Review\ReviewServiceProvider::class,
];
