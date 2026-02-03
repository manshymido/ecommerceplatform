<?php

namespace App\Modules\Shipping;

use App\Modules\Shipping\Domain\ShippingMethodRepository;
use App\Modules\Shipping\Domain\ShipmentRepository;
use App\Modules\Shipping\Infrastructure\Repositories\EloquentShippingMethodRepository;
use App\Modules\Shipping\Infrastructure\Repositories\EloquentShipmentRepository;
use Illuminate\Support\ServiceProvider;

class ShippingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ShippingMethodRepository::class, EloquentShippingMethodRepository::class);
        $this->app->bind(ShipmentRepository::class, EloquentShipmentRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
