<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts back-office endpoints to authenticated administrators.
 *
 * Route-level protection is deliberate: UI checks are useful for navigation,
 * but they must never be the only authorization mechanism.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'message' => 'Action réservée aux administrateurs.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
