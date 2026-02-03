<?php

namespace App\Modules\Shipping\Application;

use App\Modules\Shipping\Domain\ShippingMethodRepository;
use App\Modules\Shipping\Domain\ShippingQuote;
use Illuminate\Support\Facades\Cache;

class ShippingService
{
    private const QUOTES_TTL_SECONDS = 300;

    public function __construct(
        private ShippingMethodRepository $shippingMethodRepository
    ) {
    }

    /**
     * Get available shipping quotes for a destination and cart total. Optionally pass weight in kg for per_kg pricing.
     * Results are cached for 5 minutes to reduce load on read-heavy checkout flows.
     *
     * @return ShippingQuote[]
     */
    public function getQuotes(string $countryCode, float $cartTotal, string $currency = 'USD', float $weightKg = 0): array
    {
        $cacheKey = sprintf(
            'shipping:quotes:%s:%s:%s:%s',
            $countryCode,
            number_format($cartTotal, 2, '.', ''),
            $currency,
            number_format($weightKg, 2, '.', '')
        );

        return Cache::remember($cacheKey, self::QUOTES_TTL_SECONDS, function () use ($countryCode, $cartTotal, $currency, $weightKg) {
            return $this->computeQuotes($countryCode, $cartTotal, $currency, $weightKg);
        });
    }

    /**
     * @return ShippingQuote[]
     */
    private function computeQuotes(string $countryCode, float $cartTotal, string $currency, float $weightKg): array
    {
        $methods = $this->shippingMethodRepository->findAllActive();
        $quotes = [];

        foreach ($methods as $method) {
            foreach ($method->zones as $zone) {
                if (! $zone->matches($countryCode, $cartTotal, $weightKg)) {
                    continue;
                }
                if ($zone->currency !== $currency) {
                    continue;
                }
                $amount = $zone->calculateAmount($weightKg);
                $quotes[] = new ShippingQuote(
                    methodCode: $method->code,
                    methodName: $method->name,
                    amount: $amount,
                    currency: $zone->currency,
                );
                break;
            }
        }

        return $quotes;
    }
}
