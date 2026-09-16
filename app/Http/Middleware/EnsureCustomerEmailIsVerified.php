<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Comprar y ver pedidos exige haber verificado el correo. */
class EnsureCustomerEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('customer');

        if ($user && ! $user->hasVerifiedEmail()) {
            // guest() recuerda la página pedida para volver a ella al verificar.
            return redirect()->guest(route('shop.verification.notice'));
        }

        return $next($request);
    }
}
