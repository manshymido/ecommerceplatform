<?php

namespace App\Http\Requests\Admin;

class StoreWarehouseRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouses,code',
            'country_code' => 'nullable|string|size:2',
            'region' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
        ];
    }
}
