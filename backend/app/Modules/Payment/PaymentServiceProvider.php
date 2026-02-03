<?php

namespace App\Modules\Payment;

use App\Modules\Payment\Application\PaymentGateway;
use App\Modules\Payment\Application\StripePaymentGateway;
use App\Modules\Payment\Domain\PaymentRepository;
use App\Modules\Payment\Domain\RefundRepository;
use App\Modules\Payment\Infrastructure\Repositories\EloquentPaymentRepository;
use App\Modules\Payment\Infrastructure\Repositories\EloquentRefundRepository;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentRepository::class, EloquentPaymentRepository::class);
        $this->app->bind(RefundRepository::class, EloquentRefundRepository::class);
        $this->app->bind(PaymentGateway::class, function ($app) {
            $secret = config('services.stripe.secret') ?? '';
            return new StripePaymentGateway($secret);
        });
    }

    public function boot(): void
    {
        //
    }
}
