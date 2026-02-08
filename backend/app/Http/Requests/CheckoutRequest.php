<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(
            [
                'email' => [$this->user() ? 'nullable' : 'required', 'email'],
                'shipping_method_code' => ['nullable', 'string', 'max:50'],
                'shipping_method_name' => ['nullable', 'string', 'max:255'],
                'shipping_amount' => ['nullable', 'numeric', 'min:0'],
                'tax_amount' => ['nullable', 'numeric', 'min:0'],
                'payment_intent_id' => ['nullable', 'string', 'max:255'],
            ],
            $this->addressRules('billing_address'),
            $this->addressRules('shipping_address')
        );
    }

    /**
     * Validation rules for an address block (billing or shipping). DRY.
     *
     * @return array<string, mixed>
     */
    private function addressRules(string $prefix): array
    {
        return [
            $prefix => ['nullable', 'array'],
            "{$prefix}.name" => ['nullable', 'string', 'max:255'],
            "{$prefix}.line1" => ['nullable', 'string', 'max:255'],
            "{$prefix}.line2" => ['nullable', 'string', 'max:255'],
            "{$prefix}.city" => ['nullable', 'string', 'max:100'],
            "{$prefix}.state" => ['nullable', 'string', 'max:100'],
            "{$prefix}.postal_code" => ['nullable', 'string', 'max:20'],
            "{$prefix}.country" => ['nullable', 'string', 'size:2'],
        ];
    }
}
