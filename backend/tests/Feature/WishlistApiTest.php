<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\CreatesAdminUser;
use Tests\SeedsMinimalCatalog;
use Tests\TestCase;

class WishlistApiTest extends TestCase
{
    use CreatesAdminUser;
    use RefreshDatabase;
    use SeedsMinimalCatalog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureRolesExist();
        $this->createMinimalCatalog([
            'product' => ['name' => 'P', 'slug' => 'p'],
            'brand' => ['name' => 'B', 'slug' => 'b'],
            'category' => ['name' => 'C', 'slug' => 'c'],
            'variant' => ['sku' => 'SKU1', 'name' => 'V1'],
        ]);
    }

    protected function actingAsCustomer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    public function test_get_wishlist_requires_auth(): void
    {
        $response = $this->getJson('/api/wishlist');

        $response->assertStatus(401);
    }

    public function test_get_wishlist_returns_empty_for_authenticated_user(): void
    {
        $this->actingAsCustomer();

        $response = $this->getJson('/api/wishlist');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'user_id', 'items']])
            ->assertJsonPath('data.items', []);
    }

    public function test_add_item_to_wishlist(): void
    {
        $this->actingAsCustomer();
        $variantId = ProductVariant::first()->id;

        $response = $this->postJson('/api/wishlist/items', [
            'product_variant_id' => $variantId,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'user_id', 'items']])
            ->assertJsonPath('data.items.0.product_variant_id', $variantId);
    }

    public function test_remove_item_from_wishlist(): void
    {
        $this->actingAsCustomer();
        $variantId = ProductVariant::first()->id;
        $this->postJson('/api/wishlist/items', ['product_variant_id' => $variantId]);
        $wishlist = $this->getJson('/api/wishlist')->json('data');
        $itemId = $wishlist['items'][0]['id'];

        $response = $this->deleteJson("/api/wishlist/items/{$itemId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.items', []);
    }

    public function test_add_wishlist_item_validation_requires_product_variant_id(): void
    {
        $this->actingAsCustomer();

        $response = $this->postJson('/api/wishlist/items', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_variant_id']);
    }

    public function test_add_wishlist_item_validation_rejects_invalid_variant(): void
    {
        $this->actingAsCustomer();

        $response = $this->postJson('/api/wishlist/items', [
            'product_variant_id' => 99999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_variant_id']);
    }

    public function test_remove_wishlist_item_returns_404_when_not_found(): void
    {
        $this->actingAsCustomer();

        $response = $this->deleteJson('/api/wishlist/items/99999');

        $response->assertStatus(404);
    }
}
