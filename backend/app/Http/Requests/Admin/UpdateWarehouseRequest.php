<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $warehouseId = $this->route('warehouse');

        return [
            'name' => 'sometimes|string|max:255',
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('warehouses', 'code')->ignore($warehouseId)],
            'country_code' => 'nullable|string|size:2',
            'region' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
        ];
    }
}
