<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleAuthController extends Controller
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

    public function status(): JsonResponse
    {
        $enabled = $this->isWebConfigured() || $this->isDesktopConfigured();

        return response()->json([
            'enabled' => $enabled,
            'client_id' => $enabled && $this->isDesktopConfigured() ? config('services.google.client_id') : null,
            'web_client_id' => $this->isWebConfigured() ? config('services.google.web_client_id') : null,
        ]);
    }

    public function redirect(Request $request): \Illuminate\Http\RedirectResponse
    {
        $returnTo = trim((string) $request->query('return_to', ''));

        if ($returnTo === '' || ! filter_var($returnTo, FILTER_VALIDATE_URL)) {
            $returnTo = null;
        }

        if (! $this->isConfigured()) {
            if ($returnTo) {
                return redirect()->away($returnTo.'?error=not_configured');
            }

            return redirect()->away(url('/api/auth/google/callback?error=not_configured'));
        }

        $clientId = $this->isWebConfigured()
            ? (string) config('services.google.web_client_id')
            : (string) config('services.google.client_id');

        $state = base64_encode(json_encode(['return_to' => $returnTo]));

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => url('/api/auth/google/callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ];

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params));
    }

    public function callback(Request $request): \Illuminate\Http\RedirectResponse
    {
        $state = (string) $request->query('state', '');
        $decoded = [];
        if ($state !== '') {
            $decoded = json_decode(base64_decode($state, true) ?: '', true) ?: [];
        }
        $returnTo = is_array($decoded) ? ($decoded['return_to'] ?? null) : null;

        if (is_string($returnTo) && $returnTo !== '' && $request->filled('code')) {
            try {
                $code = $this->exchangeGoogleCode((string) $request->query('code'), url('/api/auth/google/callback'), $this->isWebConfigured());
            } catch (\Throwable $exception) {
                \Illuminate\Support\Facades\Log::warning('Google callback : échec', ['message' => $exception->getMessage()]);

                return redirect()->away($returnTo.'?error=google_failed');
            }

            $user = $this->findOrCreateGoogleUser($code['google_id'], $code['email'], $code['name']);

            if (! $user) {
                return redirect()->away($returnTo.'?error=account');
            }

            $shortCode = Str::random(48);
            Cache::put('social-login:'.$shortCode, $user->id, now()->addMinutes(5));

            return redirect()->away($returnTo.'?code='.$shortCode);
        }

        $query = $request->query();
        $target = 'coutureatelier://oauth/callback';

        if ($query !== []) {
            $target .= '?'.http_build_query($query);
        }

        return redirect()->away($target);
    }

    public function exchange(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:4096'],
            'code_verifier' => ['nullable', 'string', 'min:43', 'max:128'],
            'redirect_uri' => ['nullable', 'url', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ], [
            'code.required' => 'Le code Google est absent.',
        ]);

        if (! $request->filled('code_verifier')) {
            $userId = Cache::pull('social-login:'.$data['code']);

            if (! $userId) {
                throw ValidationException::withMessages([
                    'google' => ['Code de connexion invalide ou expiré.'],
                ]);
            }

            $user = User::find($userId);

            if (! $user) {
                throw ValidationException::withMessages([
                    'google' => ['Compte introuvable.'],
                ]);
            }

            $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();
            $token = $user->createToken($data['device_name'] ?? 'google-mobile')->plainTextToken;

            return response()->json(['token' => $token, 'user' => $user]);
        }

        if (! $this->isConfigured()) {
            throw ValidationException::withMessages([
                'google' => ['La connexion Google n’est pas configurée.'],
            ]);
        }

        if (! $request->filled('redirect_uri')) {
            throw ValidationException::withMessages([
                'redirect_uri' => ['L’adresse de retour Google est requise.'],
            ]);
        }

        [$clientId, $clientSecret] = $this->resolveClient($data['device_name'] ?? null);

        try {
            $tokenResponse = Http::asForm()
                ->timeout(15)
                ->post(self::TOKEN_URL, [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'code' => $data['code'],
                    'code_verifier' => $data['code_verifier'],
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $data['redirect_uri'],
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            \Illuminate\Support\Facades\Log::warning('Google OAuth : échange du code impossible.', [
                'message' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'google' => ['Google est injoignable. Vérifiez votre connexion internet.'],
            ]);
        }

        if (! $tokenResponse->successful() || blank($tokenResponse->json('access_token'))) {
            throw ValidationException::withMessages([
                'google' => ['Google a refusé la connexion. Réessayez dans un instant.'],
            ]);
        }

        try {
            $userInfoResponse = Http::withToken($tokenResponse->json('access_token'))
                ->timeout(15)
                ->get(self::USERINFO_URL);
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            throw ValidationException::withMessages([
                'google' => ['Impossible de vérifier votre compte Google. Réessayez.'],
            ]);
        }

        if (! $userInfoResponse->successful()) {
            throw ValidationException::withMessages([
                'google' => ['Impossible de vérifier votre compte Google. Réessayez.'],
            ]);
        }

        $googleId = trim((string) $userInfoResponse->json('sub'));
        $email = Str::lower(trim((string) $userInfoResponse->json('email')));
        $emailVerified = filter_var($userInfoResponse->json('email_verified', false), FILTER_VALIDATE_BOOLEAN);

        if ($googleId === '' || $email === '' || ! $emailVerified) {
            throw ValidationException::withMessages([
                'google' => ['Le compte Google ne fournit pas d’adresse e-mail exploitable.'],
            ]);
        }

        $user = $this->findOrCreateGoogleUser($googleId, $email, $userInfoResponse->json('name'));

        if (! $user) {
            throw ValidationException::withMessages([
                'google' => ['Connexion Google impossible.'],
            ]);
        }

        $token = $user->createToken($data['device_name'] ?? 'google-mobile')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

    private function exchangeGoogleCode(string $code, string $redirectUri, bool $useWebClient): array
    {
        $clientId = $useWebClient && $this->isWebConfigured()
            ? (string) config('services.google.web_client_id')
            : (string) config('services.google.client_id');
        $clientSecret = $useWebClient && $this->isWebConfigured()
            ? (string) config('services.google.web_client_secret')
            : (string) config('services.google.client_secret');

        $tokenResponse = Http::asForm()
            ->timeout(15)
            ->post(self::TOKEN_URL, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
            ]);

        if (! $tokenResponse->successful() || blank($tokenResponse->json('access_token'))) {
            throw new \RuntimeException('Token Google invalide.');
        }

        $userInfo = Http::withToken($tokenResponse->json('access_token'))
            ->timeout(15)
            ->get(self::USERINFO_URL);

        if (! $userInfo->successful()) {
            throw new \RuntimeException('Profil Google injoignable.');
        }

        $googleId = trim((string) $userInfo->json('sub'));
        $email = Str::lower(trim((string) $userInfo->json('email')));
        $verified = filter_var($userInfo->json('email_verified', false), FILTER_VALIDATE_BOOLEAN);

        if ($googleId === '' || $email === '' || ! $verified) {
            throw new \RuntimeException('Compte Google non vérifié.');
        }

        return [
            'google_id' => $googleId,
            'email' => $email,
            'name' => $userInfo->json('name'),
        ];
    }

    private function findOrCreateGoogleUser(string $googleId, string $email, $name): ?User
    {
        $user = User::query()->where('google_id', $googleId)->first();

        if (! $user) {
            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        }

        if ($user) {
            if (filled($user->google_id) && $user->google_id !== $googleId) {
                return null;
            }

            $user->forceFill([
                'google_id' => $googleId,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            return $user;
        }

        $user = User::create([
            'name' => $name ?: Str::before($email, '@'),
            'email' => $email,
            'password' => bcrypt(Str::random(32)),
            'google_id' => $googleId,
            'avatar' => null,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function isConfigured(): bool
    {
        return $this->isDesktopConfigured() || $this->isWebConfigured();
    }

    private function isDesktopConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    private function isWebConfigured(): bool
    {
        return filled(config('services.google.web_client_id'))
            && filled(config('services.google.web_client_secret'));
    }

    private function resolveClient(?string $deviceName): array
    {
        $deviceName = strtolower((string) $deviceName);

        if (str_contains($deviceName, 'mobile') && $this->isWebConfigured()) {
            return [
                (string) config('services.google.web_client_id'),
                (string) config('services.google.web_client_secret'),
            ];
        }

        return [
            (string) config('services.google.client_id'),
            (string) config('services.google.client_secret'),
        ];
    }
}