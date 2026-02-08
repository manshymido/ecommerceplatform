<?php

namespace App\Http\Requests\Admin;

class StoreShipmentRequest extends AdminFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tracking_number' => ['nullable', 'string', 'max:128'],
            'carrier_code' => ['nullable', 'string', 'max:32'],
        ];
    }
}
