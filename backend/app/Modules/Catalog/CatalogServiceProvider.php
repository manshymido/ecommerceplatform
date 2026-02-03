<?php

namespace App\Modules\Catalog;

use App\Modules\Catalog\Domain\ProductRepository;
use App\Modules\Catalog\Infrastructure\Repositories\EloquentProductRepository;
use Illuminate\Support\ServiceProvider;

class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRepository::class, EloquentProductRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
