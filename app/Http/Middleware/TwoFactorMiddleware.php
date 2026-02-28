<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorMiddleware
{
    /**
     * Enforce two-factor authentication challenge completion.
     *
     * If the authenticated user has confirmed 2FA on their account but has
     * not yet passed the challenge in the current session, redirect them to
     * the two-factor challenge page so they can verify before proceeding.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user !== null &&
            $user->two_factor_confirmed_at !== null &&
            ! $request->session()->get('auth.two_factor_confirmed', false)
        ) {
            return redirect()->route('two-factor.login');
        }

        return $next($request);
    }
}
