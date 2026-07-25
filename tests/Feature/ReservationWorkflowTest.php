<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_time_slot_cannot_be_reserved_twice(): void
    {
        $service = $this->activeService();
        $date = Carbon::tomorrow()->toDateString();
        $payload = $this->reservationPayload($service, $date, '09:00');

        $this->postJson('/api/reservations', $payload)->assertCreated();

        $this->postJson('/api/reservations', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reservation_time');
    }

    public function test_an_invalid_or_inactive_service_cannot_be_booked(): void
    {
        $service = $this->activeService(['is_active' => false]);

        $this->postJson('/api/reservations', $this->reservationPayload($service, Carbon::tomorrow()->toDateString(), '09:00'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('service_id');
    }

    public function test_cancelling_an_appointment_releases_its_slot(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $service = $this->activeService();
        $date = Carbon::tomorrow()->toDateString();
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'reservation_date' => $date,
            'reservation_time' => '10:00',
            'booking_key' => "{$date}|10:00",
            'client_name' => $user->name,
            'client_phone' => '+22370000000',
            'status' => 'pending',
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/reservations/{$reservation->id}")
            ->assertOk();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled',
            'booking_key' => null,
        ]);

        $this->getJson("/api/reservations/available-slots?date={$date}")
            ->assertOk()
            ->assertJsonPath('slots.2', '10:00');
    }

    public function test_only_an_admin_can_confirm_a_pending_appointment(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $admin = User::factory()->create(['role' => 'admin']);
        $service = $this->activeService();
        $date = Carbon::tomorrow()->toDateString();
        $reservation = Reservation::create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'reservation_date' => $date,
            'reservation_time' => '11:00',
            'booking_key' => "{$date}|11:00",
            'client_name' => $client->name,
            'client_phone' => '+22370000000',
            'status' => 'pending',
        ]);

        $this->actingAs($client, 'sanctum')
            ->patchJson("/api/reservations/{$reservation->id}/confirm")
            ->assertForbidden();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/reservations/{$reservation->id}/confirm")
            ->assertOk()
            ->assertJsonPath('reservation.status', 'confirmed');
    }

    private function activeService(array $attributes = []): Service
    {
        return Service::create(array_merge([
            'name' => 'Robe sur mesure',
            'slug' => 'robe-sur-mesure',
            'duration' => '3 jours',
            'price' => '35000 FCFA',
            'is_active' => true,
        ], $attributes));
    }

    private function reservationPayload(Service $service, string $date, string $time): array
    {
        return [
            'service_id' => $service->id,
            'reservation_date' => $date,
            'reservation_time' => $time,
            'client_name' => 'Awa Traoré',
            'client_phone' => '+22370000000',
        ];
    }
}
