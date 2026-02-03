<?php

namespace App\Modules\Order;

use App\Modules\Order\Domain\OrderRepository;
use App\Modules\Order\Infrastructure\Repositories\EloquentOrderRepository;
use Illuminate\Support\ServiceProvider;

class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderRepository::class, EloquentOrderRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
