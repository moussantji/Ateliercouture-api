<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_upload_product_images_and_receives_public_urls(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/products/{$product->id}/images", [
                'images' => [UploadedFile::fake()->image('robe.jpg')],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'product.images')
            ->assertJsonPath('product.image_urls.0', $product->fresh()->images[0]);

        Storage::disk('public')->assertExists($product->fresh()->images[0]);
    }

    public function test_uploading_more_than_four_images_is_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product(['images' => ['products/1.jpg', 'products/2.jpg', 'products/3.jpg', 'products/4.jpg']]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/products/{$product->id}/images", [
                'images' => [UploadedFile::fake()->image('new.jpg')],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('images');
    }

    private function product(array $attributes = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Robe image',
            'slug' => 'robe-image',
            'category' => 'Robes',
            'price' => '25000 FCFA',
            'is_active' => true,
        ], $attributes));
    }
}
