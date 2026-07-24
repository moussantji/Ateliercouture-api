<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['store', 'availableSlots']);
    }

    public function index(Request $request)
    {
        return response()->json(
            Reservation::where('user_id', $request->user()->id)
                ->with('service')
                ->orderBy('reservation_date', 'desc')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'reservation_date' => 'required|date|after_or_equal:today',
            'reservation_time' => 'required|string',
            'client_name' => 'required|string|max:255',
            'client_phone' => 'required|string|max:20',
            'client_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['user_id'] = $request->user()?->id;
        $validated['status'] = 'pending';

        $reservation = Reservation::create($validated);

        return response()->json([
            'reservation' => $reservation->load('service'),
            'message' => 'Réservation confirmée ! Nous vous contacterons sous 24h.'
        ], 201);
    }

    public function destroy(Reservation $reservation)
    {
        $reservation->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Réservation annulée.']);
    }

    public function availableSlots(Request $request)
    {
        $date = $request->date ?? now()->format('Y-m-d');
        $slots = ['08:00', '09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00'];

        $booked = Reservation::where('reservation_date', $date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('reservation_time')
            ->toArray();

        $available = array_values(array_diff($slots, $booked));

        return response()->json([
            'date' => $date,
            'slots' => $available,
        ]);
    }
}
