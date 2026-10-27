<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve les endpoints d'écriture de l'API au serveur Next (immo-front), qui filtre les robots
 * et valide les formulaires avant de les transmettre. Le navigateur n'appelle jamais Laravel directement.
 */
class EnsureFrontKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = config('services.front.api_key');

        if (! $key || ! hash_equals($key, (string) $request->header('X-Front-Key'))) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        return $next($request);
    }
}
