<?php

namespace App\Http\Requests\Admin;

class StoreBrandRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:brands,slug',
        ];
    }
}
