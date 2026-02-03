<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateBrandRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $brandId = $this->route('brand');

        return [
            'name' => 'sometimes|string|max:255',
            'slug' => ['sometimes', 'string', Rule::unique('brands', 'slug')->ignore($brandId)],
        ];
    }
}
