<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_server_calculates_the_order_total_from_its_own_product_prices(): void
    {
        $product = $this->product(['price' => '25 000 FCFA']);

        $this->postJson('/api/orders', [
            'client_name' => 'Awa Traoré',
            'client_phone' => '+22370000000',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            // This legacy client-controlled field is intentionally ignored.
            'total' => 1,
        ])
            ->assertCreated()
            ->assertJsonPath('summary.subtotal', 50000)
            ->assertJsonPath('summary.total', 50000)
            ->assertJsonPath('order.total', 50000);

        $this->assertDatabaseHas('orders', ['total' => 50000]);
    }

    public function test_an_applicable_promotion_is_recalculated_on_the_server(): void
    {
        $product = $this->product(['price' => '20 000 FCFA']);
        PromoCode::create([
            'code' => 'BIENVENUE10',
            'type' => 'percentage',
            'discount' => '10%',
            'min_amount' => 10000,
            'is_active' => true,
        ]);

        $this->postJson('/api/orders', [
            'client_name' => 'Awa Traoré',
            'client_phone' => '+22370000000',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'promo_code' => 'bienvenue10',
        ])
            ->assertCreated()
            ->assertJsonPath('summary.subtotal', 40000)
            ->assertJsonPath('summary.discount_value', 4000)
            ->assertJsonPath('summary.total', 36000)
            ->assertJsonPath('order.promo_code', 'BIENVENUE10');
    }

    public function test_an_inactive_product_or_invalid_promotion_cannot_be_ordered(): void
    {
        $product = $this->product(['is_active' => false]);

        $this->postJson('/api/orders', [
            'client_name' => 'Awa Traoré',
            'client_phone' => '+22370000000',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors('items');

        $activeProduct = $this->product([
            'name' => 'Boubou',
            'slug' => 'boubou',
            'is_active' => true,
        ]);

        $this->postJson('/api/orders', [
            'client_name' => 'Awa Traoré',
            'client_phone' => '+22370000000',
            'items' => [['product_id' => $activeProduct->id, 'quantity' => 1]],
            'promo_code' => 'INEXISTANT',
        ])->assertUnprocessable()->assertJsonValidationErrors('promo_code');
    }

    public function test_an_authenticated_customer_only_sees_their_own_orders(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $product = $this->product();

        $this->actingAs($customer, 'sanctum')->postJson('/api/orders', [
            'client_name' => $customer->name,
            'client_phone' => '+22370000000',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $otherOrder = \App\Models\Order::create([
            'user_id' => $otherCustomer->id,
            'client_name' => $otherCustomer->name,
            'client_phone' => '+22371111111',
            'items' => [],
            'total' => 10000,
            'status' => 'pending',
        ]);

        $this->actingAs($customer, 'sanctum')->getJson('/api/orders')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonMissing(['id' => $otherOrder->id]);
    }

    private function product(array $attributes = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Robe',
            'slug' => 'robe',
            'category' => 'Robes',
            'price' => '10000 FCFA',
            'is_active' => true,
        ], $attributes));
    }
}
