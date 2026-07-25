<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unauthenticated_request_cannot_access_an_admin_endpoint(): void
    {
        $this->getJson('/api/admin/orders')
            ->assertUnauthorized();
    }

    public function test_a_client_cannot_access_any_admin_read_endpoint(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        foreach ([
            '/api/admin/orders',
            '/api/admin/promo-codes',
            '/api/admin/categories',
            '/api/admin/users',
            '/api/admin/reservations',
        ] as $endpoint) {
            $this->actingAs($client, 'sanctum')
                ->getJson($endpoint)
                ->assertForbidden()
                ->assertJsonPath('message', 'Action réservée aux administrateurs.');
        }
    }

    public function test_an_admin_can_access_an_admin_read_endpoint(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/categories')
            ->assertOk()
            ->assertJsonStructure(['products', 'services']);
    }
}
