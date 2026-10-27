<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('admin.login')->with('warning', 'Veuillez vous connecter pour accéder à l\'espace d\'administration.');
        }

        if (! auth()->user()->isStaff()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->with('error', 'Accès refusé. Vos droits ne permettent pas d\'administrer cette plateforme.');
        }

        return $next($request);
    }
}
