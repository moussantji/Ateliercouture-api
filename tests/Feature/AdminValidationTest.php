<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminValidationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_product_creation_requires_the_business_fields(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/products', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'price', 'category']);
    }

    public function test_service_update_rejects_an_invalid_brand_color(): void
    {
        $service = Service::create([
            'name' => 'Retouches',
            'slug' => 'retouches',
            'duration' => '1 jour',
            'price' => '5000 FCFA',
        ]);

        $this->actingAs($this->admin(), 'sanctum')
            ->putJson("/api/admin/services/{$service->id}", ['color' => 'blue'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('color');
    }

    public function test_a_promotion_persists_its_rule_fields(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/promo-codes', [
                'code' => 'bienvenue10',
                'type' => 'percentage',
                'discount' => '10%',
                'min_amount' => 10000,
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('promo.code', 'BIENVENUE10')
            ->assertJsonPath('promo.type', 'percentage')
            ->assertJsonPath('promo.min_amount', 10000);

        $this->assertDatabaseHas('promo_codes', [
            'code' => 'BIENVENUE10',
            'type' => 'percentage',
            'min_amount' => 10000,
        ]);
    }

    public function test_an_order_status_must_be_from_the_allowed_workflow(): void
    {
        $order = Order::create([
            'client_name' => 'Awa Traoré',
            'client_phone' => '+22370000000',
            'items' => [['name' => 'Robe', 'price' => '25000 FCFA', 'quantity' => 1]],
            'total' => 25000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'unknown'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }
}
