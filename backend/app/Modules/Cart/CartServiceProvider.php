<?php

namespace App\Modules\Cart;

use App\Modules\Cart\Domain\CartRepository;
use App\Modules\Cart\Infrastructure\Repositories\EloquentCartRepository;
use Illuminate\Support\ServiceProvider;

class CartServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CartRepository::class, EloquentCartRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
