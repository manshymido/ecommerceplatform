<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Infrastructure\Models\Product as ProductModel;
use App\Modules\Review\Infrastructure\Models\ProductReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\CreatesAdminUser;
use Tests\SeedsMinimalCatalog;
use Tests\TestCase;

class ProductReviewApiTest extends TestCase
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

    public function test_get_product_reviews_returns_empty_when_none(): void
    {
        $response = $this->getJson('/api/products/p/reviews');

        $response->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonPath('data', []);
    }

    public function test_get_product_reviews_returns_404_for_unknown_product(): void
    {
        $response = $this->getJson('/api/products/non-existent/reviews');

        $response->assertStatus(404);
    }

    public function test_create_review_requires_auth(): void
    {
        $response = $this->postJson('/api/products/p/reviews', [
            'rating' => 5,
            'title' => 'Great',
            'body' => 'Loved it.',
        ]);

        $response->assertStatus(401);
    }

    public function test_create_review_succeeds(): void
    {
        $user = $this->actingAsCustomer();

        $response = $this->postJson('/api/products/p/reviews', [
            'rating' => 5,
            'title' => 'Great product',
            'body' => 'Loved it.',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'user_id', 'product_id', 'rating', 'title', 'body', 'status']])
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.title', 'Great product')
            ->assertJsonPath('data.body', 'Loved it.')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.user_id', $user->id);

        $product = ProductModel::where('slug', 'p')->first();
        $this->assertDatabaseHas('product_reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'status' => 'pending',
        ]);
    }

    public function test_create_review_accepts_optional_title_and_body(): void
    {
        $this->actingAsCustomer();

        $response = $this->postJson('/api/products/p/reviews', [
            'rating' => 3,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.rating', 3)
            ->assertJsonPath('data.title', null)
            ->assertJsonPath('data.body', null);
    }

    public function test_create_review_validation_rating_required(): void
    {
        $this->actingAsCustomer();

        $response = $this->postJson('/api/products/p/reviews', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_create_review_validation_rating_between_1_and_5(): void
    {
        $this->actingAsCustomer();

        $response = $this->postJson('/api/products/p/reviews', [
            'rating' => 6,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_get_reviews_returns_only_approved(): void
    {
        $product = ProductModel::where('slug', 'p')->first();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        ProductReview::create([
            'user_id' => $user1->id,
            'product_id' => $product->id,
            'rating' => 5,
            'title' => 'Approved',
            'body' => 'Nice',
            'status' => 'approved',
        ]);
        ProductReview::create([
            'user_id' => $user2->id,
            'product_id' => $product->id,
            'rating' => 1,
            'title' => 'Pending',
            'body' => 'Meh',
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/products/p/reviews');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame('Approved', $data[0]['title']);
        $this->assertSame('approved', $data[0]['status']);
    }
}
