<?php

namespace App\Http\Requests\Admin;

class AdjustStockRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'product_variant_id' => 'required|exists:product_variants,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity_delta' => 'required|integer',
            'reason_code' => 'required|string|max:50',
        ];
    }
}
