<?php

namespace App\Modules\Catalog\Domain;

interface ProductRepository
{
    public function findById(int $id): ?Product;

    public function findBySlug(string $slug): ?Product;

    public function findPublished(array $filters = []): array;

    public function save(Product $product): Product;

    public function delete(int $id): bool;
}
