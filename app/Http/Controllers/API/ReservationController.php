<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Service;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    private const TIME_SLOTS = [
        '08:00', '09:00', '10:00', '11:00',
        '14:00', '15:00', '16:00', '17:00',
    ];

    public function index(Request $request)
    {
        return response()->json(
            Reservation::where('user_id', $request->user()->id)
                ->with('service')
                ->orderByDesc('reservation_date')
                ->orderByDesc('reservation_time')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => [
                'required',
                Rule::exists('services', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'reservation_date' => 'required|date|after_or_equal:today',
            'reservation_time' => ['required', 'date_format:H:i', Rule::in(self::TIME_SLOTS)],
            'client_name' => 'required|string|max:255',
            'client_phone' => 'required|string|max:30',
            'client_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $bookingKey = $this->bookingKey($validated['reservation_date'], $validated['reservation_time']);

        try {
            $reservation = DB::transaction(function () use ($request, $validated, $bookingKey) {
                // The unique booking_key is the final protection against two
                // simultaneous requests taking the same appointment slot.
                return Reservation::create([
                    ...$validated,
                    'user_id' => $request->user()?->id,
                    'booking_key' => $bookingKey,
                    'status' => 'pending',
                ]);
            });
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                return response()->json([
                    'message' => 'Ce créneau vient d’être réservé. Veuillez en choisir un autre.',
                    'errors' => ['reservation_time' => ['Ce créneau n’est plus disponible.']],
                ], 422);
            }

            throw $exception;
        }

        return response()->json([
            'reservation' => $reservation->load('service'),
            'message' => 'Réservation enregistrée ! Nous vous contacterons sous 24h.',
        ], 201);
    }

    public function show(Request $request, Reservation $reservation)
    {
        $this->ensureOwnerOrAdmin($request, $reservation);

        return response()->json($reservation->load('service'));
    }

    public function destroy(Request $request, Reservation $reservation)
    {
        $this->ensureOwnerOrAdmin($request, $reservation);

        if (!in_array($reservation->status, ['pending', 'confirmed'], true)) {
            return response()->json([
                'message' => 'Cette réservation ne peut plus être annulée.',
            ], 422);
        }

        $reservation->update([
            'status' => 'cancelled',
            'booking_key' => null,
        ]);

        return response()->json(['message' => 'Réservation annulée.']);
    }

    public function confirm(Request $request, Reservation $reservation)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Action réservée aux administrateurs.'], 403);
        }

        if ($reservation->status !== 'pending') {
            return response()->json([
                'message' => 'Seule une réservation en attente peut être confirmée.',
            ], 422);
        }

        $reservation->update(['status' => 'confirmed']);

        return response()->json([
            'reservation' => $reservation->fresh()->load('service'),
            'message' => 'Réservation confirmée !',
        ]);
    }

    public function availableSlots(Request $request)
    {
        $validated = $request->validate([
            'date' => 'nullable|date|after_or_equal:today',
        ]);
        $date = $validated['date'] ?? now()->toDateString();

        $booked = Reservation::where('reservation_date', $date)
            ->whereNotNull('booking_key')
            ->pluck('reservation_time')
            ->all();

        return response()->json([
            'date' => $date,
            'slots' => array_values(array_diff(self::TIME_SLOTS, $booked)),
        ]);
    }

    private function ensureOwnerOrAdmin(Request $request, Reservation $reservation): void
    {
        if ($reservation->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403, 'Action non autorisée.');
        }
    }

    private function bookingKey(string $date, string $time): string
    {
        return "{$date}|{$time}";
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['1062', '1555', '2067', '23000'], true);
    }
}
