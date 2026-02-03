<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

trait ResolvesCartContext
{
    /**
     * Resolve cart context from request (auth or X-Guest-Token). Optionally include currency.
     *
     * @return array{user_id: int|null, guest_token: string|null, currency?: string}
     */
    protected function cartContext(Request $request, bool $includeCurrency = false): array
    {
        $ctx = [
            'user_id' => auth('sanctum')->user()?->id,
            'guest_token' => $request->header('X-Guest-Token') ?? $request->input('guest_token'),
        ];

        if ($includeCurrency) {
            $ctx['currency'] = $request->input('currency', 'USD');
        }

        return $ctx;
    }
}
