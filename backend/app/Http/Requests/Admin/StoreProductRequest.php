<?php

namespace App\Http\Requests\Admin;

class StoreProductRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:products,slug|max:255',
            'description' => 'nullable|string',
            'brand_id' => 'nullable|exists:brands,id',
            'status' => 'required|in:draft,published,archived',
            'main_image_url' => 'nullable|url|max:500',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'default_variant' => 'nullable|array',
            'default_variant.sku' => 'nullable|string|max:100',
            'default_variant.name' => 'nullable|string|max:255',
            'default_variant.price' => 'nullable|numeric|min:0',
        ];
    }
}
