<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\ResourceNotFoundException;
use App\Modules\Catalog\Application\CatalogService;
use App\Modules\Catalog\Domain\ProductRepository;
use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Catalog\Infrastructure\Models\ProductPrice;
use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductRepository|MockInterface $productRepository;
    private CatalogService $catalogService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = Mockery::mock(ProductRepository::class);
        $this->catalogService = new CatalogService($this->productRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_returns_variant_price_for_given_currency(): void
    {
        // Create test data
        $product = Product::factory()->create(['status' => 'published']);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        ProductPrice::factory()->create([
            'product_variant_id' => $variant->id,
            'currency' => 'USD',
            'amount' => 99.99,
        ]);

        // Clear cache to ensure fresh data
        $this->catalogService->clearCache();

        $price = $this->catalogService->getVariantPrice($variant->id, 'USD');

        $this->assertEquals(99.99, $price);
    }

    #[Test]
    public function it_falls_back_to_any_available_price(): void
    {
        $product = Product::factory()->create(['status' => 'published']);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        ProductPrice::factory()->create([
            'product_variant_id' => $variant->id,
            'currency' => 'EUR',
            'amount' => 89.99,
        ]);

        $this->catalogService->clearCache();

        // Request USD but only EUR exists - should fall back
        $price = $this->catalogService->getVariantPrice($variant->id, 'USD');

        $this->assertEquals(89.99, $price);
    }

    #[Test]
    public function it_throws_exception_when_no_price_exists(): void
    {
        $product = Product::factory()->create(['status' => 'published']);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        // No price created

        $this->catalogService->clearCache();

        $this->expectException(ResourceNotFoundException::class);

        $this->catalogService->getVariantPrice($variant->id, 'USD');
    }

    #[Test]
    public function it_checks_variant_exists_and_is_published(): void
    {
        $product = Product::factory()->create(['status' => 'published']);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $this->catalogService->clearCache();

        $exists = $this->catalogService->variantExists($variant->id);

        $this->assertTrue($exists);
    }

    #[Test]
    public function it_returns_false_for_variant_of_unpublished_product(): void
    {
        $product = Product::factory()->create(['status' => 'draft']);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $this->catalogService->clearCache();

        $exists = $this->catalogService->variantExists($variant->id);

        $this->assertFalse($exists);
    }

    #[Test]
    public function it_returns_false_for_nonexistent_variant(): void
    {
        $this->catalogService->clearCache();

        $exists = $this->catalogService->variantExists(99999);

        $this->assertFalse($exists);
    }
}
