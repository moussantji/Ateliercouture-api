<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Inscription réussie !'
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !password_verify($request->password, $user->password)) {
            return response()->json([
                'message' => 'Les identifiants sont incorrects.',
                'errors' => ['email' => ['Email ou mot de passe invalide.']]
            ], 401);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Connexion réussie !'
        ]);
    }

    /**
     * Connexion avec Google
     */
    public function google(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        // Vérifier le token Google via l'API Google
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $request->id_token,
        ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Token Google invalide.',
            ], 401);
        }

        $googleUser = $response->json();

        // Vérifier l'audience (client ID)
        $clientId = config('services.google.client_id');
        if ($clientId && ($googleUser['aud'] ?? '') !== $clientId) {
            return response()->json([
                'message' => 'Token Google invalide pour cette application.',
            ], 401);
        }

        // Trouver ou créer l'utilisateur
        $user = User::where('email', $googleUser['email'])->first();

        if (!$user) {
            $user = User::create([
                'name' => $googleUser['name'] ?? $googleUser['email'],
                'email' => $googleUser['email'],
                'google_id' => $googleUser['sub'],
                'avatar' => $googleUser['picture'] ?? null,
                'password' => bcrypt(Str::random(32)),
            ]);
        } else {
            // Mettre à jour google_id si nécessaire
            if (!$user->google_id) {
                $user->update([
                    'google_id' => $googleUser['sub'],
                    'avatar' => $googleUser['picture'] ?? $user->avatar,
                ]);
            }
        }

        $token = $user->createToken('mobile-app-google')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Connexion Google réussie !'
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie.']);
    }
}
