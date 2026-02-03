<?php

namespace App\Modules\Wishlist;

use App\Modules\Wishlist\Domain\WishlistRepository;
use App\Modules\Wishlist\Infrastructure\Repositories\EloquentWishlistRepository;
use Illuminate\Support\ServiceProvider;

class WishlistServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WishlistRepository::class, EloquentWishlistRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
