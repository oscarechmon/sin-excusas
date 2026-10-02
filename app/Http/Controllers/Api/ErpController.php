<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Erp\CatalogSync;
use App\Services\Erp\ErpClient;
use App\Services\Erp\OnlineOrderRegistrar;
use Illuminate\Http\JsonResponse;

/** El panel pregunta si el sistema está conectado y puede pedir sincronizar. */
class ErpController extends Controller
{
    use ApiResponses;

    public function status(ErpClient $erp): JsonResponse
    {
        return $this->ok([
            'enabled' => $erp->enabled(),
            'url' => $erp->enabled() ? config('erp.url') : null,
        ]);
    }

    /**
     * "Sincronizar ahora": trae el catálogo completo y reintenta los pedidos
     * pagados que no llegaron al sistema. Recupera cualquier aviso perdido.
     */
    public function sync(ErpClient $erp, CatalogSync $catalog, OnlineOrderRegistrar $orders): JsonResponse
    {
        if (! $erp->enabled()) {
            return $this->failed('El sistema no está conectado.', 409);
        }

        $result = $catalog->pull() + $orders->retryPending();

        return $this->ok($result, "Catálogo sincronizado: {$result['actualizados']} ítems.");
    }
}
