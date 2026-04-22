<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_products_with_pagination(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(20)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'price', 'category_id', 'in_stock', 'rating', 'created_at', 'updated_at']
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links',
            ]);
    }

    public function test_can_filter_products_by_search_query(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['name' => 'iPhone 15', 'category_id' => $category->id]);
        Product::factory()->create(['name' => 'Samsung Galaxy', 'category_id' => $category->id]);
        Product::factory()->create(['name' => 'iPhone 14', 'category_id' => $category->id]);

        $response = $this->getJson('/api/products?q=iPhone');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_products_by_price_range(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['price' => 100, 'category_id' => $category->id]);
        Product::factory()->create(['price' => 500, 'category_id' => $category->id]);
        Product::factory()->create(['price' => 1000, 'category_id' => $category->id]);

        $response = $this->getJson('/api/products?price_from=200&price_to=800');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_products_by_category(): void
    {
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();
        Product::factory()->create(['category_id' => $cat1->id]);
        Product::factory()->create(['category_id' => $cat2->id]);
        Product::factory()->create(['category_id' => $cat2->id]);

        $response = $this->getJson('/api/products?category_id=' . $cat2->id);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_products_by_in_stock(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['in_stock' => true, 'category_id' => $category->id]);
        Product::factory()->create(['in_stock' => false, 'category_id' => $category->id]);
        Product::factory()->create(['in_stock' => true, 'category_id' => $category->id]);

        $response = $this->getJson('/api/products?in_stock=true');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_products_by_rating(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['rating' => 3.5, 'category_id' => $category->id]);
        Product::factory()->create(['rating' => 4.5, 'category_id' => $category->id]);
        Product::factory()->create(['rating' => 5.0, 'category_id' => $category->id]);

        $response = $this->getJson('/api/products?rating_from=4.0');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_sort_products_by_price_asc(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['price' => 500, 'category_id' => $category->id]);
        Product::factory()->create(['price' => 100, 'category_id' => $category->id]);
        Product::factory()->create(['price' => 300, 'category_id' => $category->id]);

        $response = $this->getJson('/api/products?sort=price_asc');

        $response->assertStatus(200);
        $prices = collect($response->json('data'))->pluck('price')->map(fn($p) => (float) $p)->toArray();
        $this->assertEquals([100.0, 300.0, 500.0], $prices);
    }

    public function test_can_sort_products_by_price_desc(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['price' => 500, 'category_id' => $category->id]);
        Product::factory()->create(['price' => 100, 'category_id' => $category->id]);
        Product::factory()->create(['price' => 300, 'category_id' => $category->id]);

        $response = $this->getJson('/api/products?sort=price_desc');

        $response->assertStatus(200);
        $prices = collect($response->json('data'))->pluck('price')->map(fn($p) => (float) $p)->toArray();
        $this->assertEquals([500.0, 300.0, 100.0], $prices);
    }

    public function test_can_sort_products_by_rating_desc(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['rating' => 3.5, 'category_id' => $category->id]);
        Product::factory()->create(['rating' => 5.0, 'category_id' => $category->id]);
        Product::factory()->create(['rating' => 4.0, 'category_id' => $category->id]);

        $response = $this->getJson('/api/products?sort=rating_desc');

        $response->assertStatus(200);
        $ratings = collect($response->json('data'))->pluck('rating')->toArray();
        $this->assertEquals([5.0, 4.0, 3.5], $ratings);
    }

    public function test_can_sort_products_by_newest(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['created_at' => now()->subDays(2), 'category_id' => $category->id]);
        Product::factory()->create(['created_at' => now()->subDays(1), 'category_id' => $category->id]);
        Product::factory()->create(['created_at' => now(), 'category_id' => $category->id]);

        $response = $this->getJson('/api/products?sort=newest');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertEquals([3, 2, 1], $ids);
    }

    public function test_can_combine_multiple_filters(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create([
            'name' => 'iPhone Pro',
            'price' => 1000,
            'rating' => 4.5,
            'in_stock' => true,
            'category_id' => $category->id,
        ]);
        Product::factory()->create([
            'name' => 'iPhone Mini',
            'price' => 500,
            'rating' => 3.5,
            'in_stock' => false,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/products?q=iPhone&price_from=800&in_stock=true&sort=price_desc');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
