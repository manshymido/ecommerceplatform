<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $categoryId = $this->route('category');

        return [
            'name' => 'sometimes|string|max:255',
            'slug' => ['sometimes', 'string', Rule::unique('categories', 'slug')->ignore($categoryId)],
            'parent_id' => 'nullable|exists:categories,id',
            'position' => 'nullable|integer',
        ];
    }
}
