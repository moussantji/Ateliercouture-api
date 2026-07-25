<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalogues_only_return_active_products_and_services(): void
    {
        $activeProduct = $this->product(['is_active' => true]);
        $hiddenProduct = $this->product(['name' => 'Produit brouillon', 'slug' => 'produit-brouillon', 'is_active' => false]);
        $activeService = $this->service(['is_active' => true]);
        $hiddenService = $this->service(['name' => 'Service brouillon', 'slug' => 'service-brouillon', 'is_active' => false]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonFragment(['id' => $activeProduct->id])
            ->assertJsonMissing(['id' => $hiddenProduct->id]);

        $this->getJson('/api/services')
            ->assertOk()
            ->assertJsonFragment(['id' => $activeService->id])
            ->assertJsonMissing(['id' => $hiddenService->id]);
    }

    public function test_public_users_cannot_open_an_inactive_catalogue_entry(): void
    {
        $product = $this->product(['is_active' => false]);
        $service = $this->service(['is_active' => false]);

        $this->getJson("/api/products/{$product->id}")->assertNotFound();
        $this->getJson("/api/services/{$service->id}")->assertNotFound();
    }

    public function test_an_admin_can_see_inactive_catalogue_entries_for_management(): void
    {
        $product = $this->product(['is_active' => false]);
        $service = $this->service(['is_active' => false]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')->getJson('/api/products')
            ->assertOk()
            ->assertJsonFragment(['id' => $product->id]);

        $this->actingAs($admin, 'sanctum')->getJson('/api/services')
            ->assertOk()
            ->assertJsonFragment(['id' => $service->id]);
    }

    private function product(array $attributes = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Robe active',
            'slug' => 'robe-active',
            'category' => 'Robes',
            'price' => '25000 FCFA',
            'gradient_colors' => '["#0F2038", "#3A5A80"]',
            'is_active' => true,
        ], $attributes));
    }

    private function service(array $attributes = []): Service
    {
        return Service::create(array_merge([
            'name' => 'Retouche active',
            'slug' => 'retouche-active',
            'duration' => '1 jour',
            'price' => '5000 FCFA',
            'is_active' => true,
        ], $attributes));
    }
}
