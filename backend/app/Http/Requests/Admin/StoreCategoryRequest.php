<?php

namespace App\Http\Requests\Admin;

class StoreCategoryRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:categories,slug',
            'parent_id' => 'nullable|exists:categories,id',
            'position' => 'nullable|integer',
        ];
    }
}
