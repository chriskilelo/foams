<?php

namespace App\Listeners;

use Illuminate\Http\Request;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;

class SetTwoFactorSessionOnValidCode
{
    public function __construct(public Request $request) {}

    /**
     * Mark the current session as having passed the two-factor challenge.
     *
     * This fires before Fortify calls session()->regenerate(), but Laravel's
     * regenerate() only changes the session ID — data is preserved — so the
     * key survives and satisfies TwoFactorMiddleware on subsequent requests.
     */
    public function handle(ValidTwoFactorAuthenticationCodeProvided $event): void
    {
        $this->request->session()->put('auth.two_factor_confirmed', true);
    }
}
