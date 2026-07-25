<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function show(Request $request)
    {
        $user = $request->user();
        $user->loadCount(['reservations']);

        return response()->json([
            'user' => $user,
            'is_admin' => $user->isAdmin(),
            'stats' => [
                'reservations_count' => $user->reservations_count,
            ]
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $request->user()->id,
            'phone' => 'sometimes|string|max:20',
        ]);

        $request->user()->update($validated);

        return response()->json([
            'user' => $request->user(),
            'message' => 'Profil mis à jour !'
        ]);
    }

    // Admin: liste des réservations
    public function allReservations(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Accès réservé aux administrateurs.'], 403);
        }

        return response()->json(
            \App\Models\Reservation::with('service')->orderBy('created_at', 'desc')->get()
        );
    }

    // Admin: liste des utilisateurs
    public function allUsers(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Accès réservé aux administrateurs.'], 403);
        }

        return response()->json(\App\Models\User::all());
    }
}
