<?php

namespace App\Http\Requests\Admin;

class UpdateShipmentRequest extends AdminFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:shipped,delivered'],
            'tracking_number' => ['nullable', 'string', 'max:128'],
            'carrier_code' => ['nullable', 'string', 'max:32'],
        ];
    }
}
