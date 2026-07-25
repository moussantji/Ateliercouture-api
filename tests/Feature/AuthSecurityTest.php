<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_normalizes_email_and_issues_an_expiring_token(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Awa Traoré',
            'email' => 'AWA@EXAMPLE.COM',
            'password' => 'password-long-et-sur',
            'password_confirmation' => 'password-long-et-sur',
        ])
            ->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'email'], 'token']);

        $user = User::where('email', 'awa@example.com')->firstOrFail();
        $this->assertNotNull($user->tokens()->first()->expires_at);
    }

    public function test_google_login_accepts_a_verified_token_for_an_allowed_client(): void
    {
        config()->set('services.google.client_ids', ['web-client.apps.googleusercontent.com']);
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response([
                'sub' => 'google-subject-123',
                'email' => 'google.user@example.com',
                'email_verified' => 'true',
                'name' => 'Google User',
                'aud' => 'web-client.apps.googleusercontent.com',
                'iss' => 'https://accounts.google.com',
            ]),
        ]);

        $this->postJson('/api/auth/google', ['id_token' => 'test-google-id-token'])
            ->assertOk()
            ->assertJsonPath('user.email', 'google.user@example.com')
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('users', [
            'email' => 'google.user@example.com',
            'google_id' => 'google-subject-123',
        ]);
    }

    public function test_login_is_limited_after_repeated_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'client@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/login', [
                'email' => 'client@example.com',
                'password' => 'bad-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/login', [
            'email' => 'client@example.com',
            'password' => 'bad-password',
        ])->assertTooManyRequests();
    }
}
