<?php

namespace App\Modules\Inventory;

use App\Modules\Inventory\Domain\StockItemRepository;
use App\Modules\Inventory\Domain\StockReservationRepository;
use App\Modules\Inventory\Infrastructure\Repositories\EloquentStockItemRepository;
use App\Modules\Inventory\Infrastructure\Repositories\EloquentStockReservationRepository;
use Illuminate\Support\ServiceProvider;

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StockItemRepository::class, EloquentStockItemRepository::class);
        $this->app->bind(StockReservationRepository::class, EloquentStockReservationRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
