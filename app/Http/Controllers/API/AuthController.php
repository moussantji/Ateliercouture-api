<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'name' => $request->string('name')->trim()->value(),
            'email' => strtolower($request->string('email')->trim()->value()),
            'phone' => $request->input('phone'),
            'password' => Hash::make($request->input('password')),
        ]);

        $token = $this->issueMobileToken($user, 'mobile-app');

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

        $user = User::where('email', strtolower(trim((string) $request->input('email'))))->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'message' => 'Les identifiants sont incorrects.',
                'errors' => ['email' => ['Email ou mot de passe invalide.']]
            ], 401);
        }

        $token = $this->issueMobileToken($user, 'mobile-app');

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
        $response = Http::timeout(5)->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $request->id_token,
        ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Token Google invalide.',
            ], 401);
        }

        $googleUser = $response->json();

        $clientIds = config('services.google.client_ids', []);
        $issuer = $googleUser['iss'] ?? '';
        $emailVerified = filter_var($googleUser['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$emailVerified || !in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)) {
            return response()->json([
                'message' => 'Token Google invalide pour cette application.',
            ], 401);
        }

        // Vérifier l'audience seulement si des client IDs sont configurés
        if (!empty($clientIds) && !in_array($googleUser['aud'] ?? '', $clientIds, true)) {
            return response()->json([
                'message' => 'Token Google invalide pour cette application (client_id mismatch).',
            ], 401);
        }

        // Trouver ou créer l'utilisateur
        $user = User::where('email', strtolower($googleUser['email']))->first();

        if (!$user) {
            $user = User::create([
                'name' => $googleUser['name'] ?? $googleUser['email'],
                'email' => strtolower($googleUser['email']),
                'google_id' => $googleUser['sub'],
                'avatar' => $googleUser['picture'] ?? null,
                'password' => Hash::make(Str::random(32)),
            ]);
        } else {
            // A previously linked account must keep the same Google subject.
            if ($user->google_id && $user->google_id !== $googleUser['sub']) {
                return response()->json([
                    'message' => 'Ce compte est déjà associé à un autre profil Google.',
                ], 409);
            }

            if (!$user->google_id) {
                $user->update([
                    'google_id' => $googleUser['sub'],
                    'avatar' => $googleUser['picture'] ?? $user->avatar,
                ]);
            }
        }

        $token = $this->issueMobileToken($user, 'mobile-app-google');

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
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    private function issueMobileToken(User $user, string $name): string
    {
        return $user->createToken(
            $name,
            ['*'],
            now()->addDays(30),
        )->plainTextToken;
    }
}
