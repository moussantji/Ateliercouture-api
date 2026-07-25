<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_update_their_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'phone' => null,
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/profile', [
                'name' => '  Awa Traoré  ',
                'email' => 'AWA@EXAMPLE.COM',
                'phone' => ' +223 70 00 00 00 ',
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Awa Traoré')
            ->assertJsonPath('user.email', 'awa@example.com')
            ->assertJsonPath('user.phone', '+223 70 00 00 00');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'awa@example.com',
        ]);
    }

    public function test_a_user_cannot_take_an_email_already_used_by_another_account(): void
    {
        $user = User::factory()->create(['email' => 'first@example.com']);
        User::factory()->create(['email' => 'used@example.com']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/profile', ['email' => 'used@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }
}
