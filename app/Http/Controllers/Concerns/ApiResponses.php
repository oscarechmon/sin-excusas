<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Formato único de respuesta de la API (§10).
 *
 * Existe para que los controladores no repitan la estructura
 * `['success' => ..., 'data' => ..., 'meta' => ...]` en cada método, y para
 * que cambiar el formato sea tocar un solo archivo.
 */
trait ApiResponses
{
    protected function ok(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json(array_filter([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], fn ($value) => $value !== null), $status);
    }

    protected function created(mixed $data, string $message): JsonResponse
    {
        return $this->ok($data, $message, 201);
    }

    /** Respuesta paginada con la misma forma de `meta` que ya usa el frontend. */
    protected function paginated(LengthAwarePaginator $paginator, string $resourceClass): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $resourceClass::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    protected function failed(string $message, int $status = 422, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
