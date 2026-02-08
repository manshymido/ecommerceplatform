<?php

namespace App\Http\Requests\Admin;

class UpdateVariantPriceRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'currency' => 'sometimes|string|size:3',
            'amount' => 'required|numeric|min:0',
            'compare_at_amount' => 'nullable|numeric|min:0',
        ];
    }
}
