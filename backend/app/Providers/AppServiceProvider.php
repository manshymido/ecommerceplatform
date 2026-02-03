<?php

namespace App\Providers;

use App\Events\OrderCancelled;
use App\Events\OrderFulfilled;
use App\Events\OrderPaymentSucceeded;
use App\Events\OrderPlaced;
use App\Listeners\SendOrderStatusNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(OrderPlaced::class, [SendOrderStatusNotification::class, 'handleOrderPlaced']);
        Event::listen(OrderPaymentSucceeded::class, [SendOrderStatusNotification::class, 'handleOrderPaymentSucceeded']);
        Event::listen(OrderFulfilled::class, [SendOrderStatusNotification::class, 'handleOrderFulfilled']);
        Event::listen(OrderCancelled::class, [SendOrderStatusNotification::class, 'handleOrderCancelled']);
    }
}
