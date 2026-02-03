<?php

namespace App\Modules\Review;

use App\Modules\Review\Domain\ProductReviewRepository;
use App\Modules\Review\Infrastructure\Repositories\EloquentProductReviewRepository;
use Illuminate\Support\ServiceProvider;

class ReviewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductReviewRepository::class, EloquentProductReviewRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
