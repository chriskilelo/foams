<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotPublicRole
{
    /**
     * Prevent users assigned the 'public' role from accessing authenticated routes.
     *
     * The 'public' role is reserved for unauthenticated general-public issue
     * submission. If a user somehow authenticates with this role, log them out
     * immediately and return them to the public home page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->hasRole('public')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('home');
        }

        return $next($request);
    }
}
