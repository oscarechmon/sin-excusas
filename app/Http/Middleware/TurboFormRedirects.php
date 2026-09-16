<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turbo espera un 303 después de enviar un formulario. Laravel redirige con
 * 302, así que se convierte aquí una sola vez en lugar de en cada controlador.
 */
class TurboFormRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') && $response instanceof RedirectResponse && $response->getStatusCode() === 302) {
            $response->setStatusCode(303);
        }

        return $response;
    }
}
