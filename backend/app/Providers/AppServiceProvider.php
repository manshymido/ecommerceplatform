<?php

namespace App\Providers;

use App\Events\CouponApplied;
use App\Events\CouponRedeemed;
use App\Events\OrderCancelled;
use App\Events\OrderFulfilled;
use App\Events\OrderPaymentSucceeded;
use App\Events\OrderPlaced;
use App\Listeners\HandleOrderInventory;
use App\Listeners\SendOrderStatusNotification;
use App\Listeners\TrackCouponUsage;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Database\Eloquent\Factories\Factory;
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
        Factory::guessFactoryNamesUsing(function (string $modelName) {
            return 'Database\\Factories\\' . class_basename($modelName) . 'Factory';
        });
        $this->configureRateLimiting();

        Event::listen(OrderPlaced::class, [SendOrderStatusNotification::class, 'handleOrderPlaced']);
        Event::listen(OrderPaymentSucceeded::class, [SendOrderStatusNotification::class, 'handleOrderPaymentSucceeded']);
        Event::listen(OrderPaymentSucceeded::class, [HandleOrderInventory::class, 'handlePaymentSucceeded']);
        Event::listen(OrderFulfilled::class, [SendOrderStatusNotification::class, 'handleOrderFulfilled']);
        Event::listen(OrderCancelled::class, [SendOrderStatusNotification::class, 'handleOrderCancelled']);
        Event::listen(OrderCancelled::class, [HandleOrderInventory::class, 'handleOrderCancelled']);

        // Coupon tracking events
        Event::listen(CouponApplied::class, [TrackCouponUsage::class, 'handleCouponApplied']);
        Event::listen(CouponRedeemed::class, [TrackCouponUsage::class, 'handleCouponRedeemed']);
    }

    /**
     * Configure rate limiting for API (e.g. auth endpoints).
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
