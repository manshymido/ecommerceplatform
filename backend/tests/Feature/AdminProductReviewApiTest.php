<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Infrastructure\Models\Product as ProductModel;
use App\Modules\Review\Infrastructure\Models\ProductReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesAdminUser;
use Tests\SeedsMinimalCatalog;
use Tests\TestCase;

class AdminProductReviewApiTest extends TestCase
{
    use CreatesAdminUser;
    use RefreshDatabase;
    use SeedsMinimalCatalog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createAdminUser();
        $this->ensureRolesExist();
        $this->createMinimalCatalog([
            'product' => ['name' => 'P', 'slug' => 'p'],
            'brand' => ['name' => 'B', 'slug' => 'b'],
            'category' => ['name' => 'C', 'slug' => 'c'],
            'variant' => ['sku' => 'SKU1', 'name' => 'V1'],
        ]);
    }

    public function test_admin_reviews_index_requires_auth(): void
    {
        $response = $this->getJson('/api/admin/reviews');

        $response->assertStatus(401);
    }

    public function test_admin_reviews_index_returns_reviews(): void
    {
        $product = ProductModel::where('slug', 'p')->first();
        $user = User::factory()->create();
        ProductReview::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 4,
            'title' => 'Good',
            'body' => 'Nice.',
            'status' => 'pending',
        ]);

        $this->actingAsAdmin();
        $response = $this->getJson('/api/admin/reviews');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonPath('data.0.title', 'Good')
            ->assertJsonPath('data.0.status', 'pending');
    }

    public function test_admin_can_moderate_review(): void
    {
        $product = ProductModel::where('slug', 'p')->first();
        $user = User::factory()->create();
        $review = ProductReview::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'title' => 'Pending',
            'body' => 'Review body',
            'status' => 'pending',
        ]);

        $this->actingAsAdmin();
        $response = $this->patchJson("/api/admin/reviews/{$review->id}", [
            'status' => 'approved',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $review->refresh();
        $this->assertSame('approved', $review->status);
    }

    public function test_admin_moderate_review_returns_404_when_not_found(): void
    {
        $this->actingAsAdmin();
        $response = $this->patchJson('/api/admin/reviews/99999', [
            'status' => 'approved',
        ]);

        $response->assertStatus(404);
    }

    public function test_admin_moderate_review_validation_requires_valid_status(): void
    {
        $product = ProductModel::where('slug', 'p')->first();
        $user = User::factory()->create();
        $review = ProductReview::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'status' => 'pending',
        ]);

        $this->actingAsAdmin();
        $response = $this->patchJson("/api/admin/reviews/{$review->id}", [
            'status' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
