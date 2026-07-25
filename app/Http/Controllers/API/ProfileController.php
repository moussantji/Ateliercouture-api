<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $user->loadCount('reservations');

        return response()->json([
            'user' => $user,
            'is_admin' => $user->isAdmin(),
            'stats' => ['reservations_count' => $user->reservations_count],
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email:rfc,dns', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'sometimes|nullable|string|max:30',
        ]);

        if (array_key_exists('name', $validated)) {
            $validated['name'] = trim($validated['name']);
        }
        if (array_key_exists('email', $validated)) {
            $validated['email'] = strtolower(trim($validated['email']));
        }
        if (array_key_exists('phone', $validated)) {
            $validated['phone'] = $validated['phone'] ? trim($validated['phone']) : null;
        }

        $user->update($validated);

        return response()->json([
            'user' => $user->fresh(),
            'message' => 'Profil mis à jour !',
        ]);
    }

    public function allReservations(Request $request)
    {
        return response()->json(Reservation::with('service')->latest()->get());
    }

    public function allUsers(Request $request)
    {
        return response()->json(User::query()->select(['id', 'name', 'email', 'phone', 'role', 'created_at'])->latest()->get());
    }
}
