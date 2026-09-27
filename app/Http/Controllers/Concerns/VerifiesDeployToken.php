<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Puerta de las rutas de despliegue.
 *
 * Sin token configurado la ruta no existe para nadie; con token, solo pasa
 * quien lo presenta exacto. La comparación es en tiempo constante para no
 * filtrar el token a base de medir respuestas.
 */
trait VerifiesDeployToken
{
    protected function verifyDeployToken(Request $request): void
    {
        $token = (string) config('deploy.token');

        abort_if($token === '', 404);

        if (! hash_equals($token, (string) $request->header('X-Deploy-Token'))) {
            Log::warning('Intento de despliegue con token inválido.', ['ip' => $request->ip()]);
            abort(403);
        }
    }
}
