<?php

namespace App\Http\Requests\Admin;

class AssignStockRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'product_variant_id' => 'required|exists:product_variants,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:0',
            'reason_code' => 'nullable|string|max:50',
        ];
    }
}
