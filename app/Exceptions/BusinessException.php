<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Base de los errores de regla de negocio (§40).
 *
 * Laravel invoca `render()` automáticamente, así que cada excepción se
 * convierte en una respuesta con el mismo formato que el resto de la API
 * (§10) sin que los controladores tengan que capturarlas una por una.
 */
abstract class BusinessException extends Exception
{
    protected int $status = 422;

    /** @var array<string,mixed> */
    protected array $context = [];

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => $this->context,
        ], $this->status);
    }
}
