<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards internal API groups with a shared bearer token. Usage:
 *   Route::middleware('service.token:bot')
 *   Route::middleware('service.token:ingest')
 *   Route::middleware('service.token:userbot')
 *
 * The expected token is read from config('boshpana.tokens.{audience}').
 */
class AuthenticateServiceToken
{
    public function handle(Request $request, Closure $next, string $audience = 'bot'): Response
    {
        $expected = config("boshpana.tokens.$audience");
        $provided = $request->bearerToken() ?: $request->header('X-Api-Token');

        if (! $expected || ! $provided || ! hash_equals((string) $expected, (string) $provided)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
