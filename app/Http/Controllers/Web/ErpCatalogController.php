<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Erp\CatalogSync;
use App\Services\Erp\ErpClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Aviso del sistema (ERP): estos productos cambiaron (datos o stock). Llega
 * con el token compartido, no con sesión ni CSRF.
 *
 * No se copia nada: se enlaza lo nuevo con su ficha web y la web vuelve a
 * leer el catálogo del sistema en la próxima página.
 *
 * Sin el sistema configurado la ruta no existe para nadie.
 */
class ErpCatalogController extends Controller
{
    public function __invoke(Request $request, ErpClient $erp, CatalogSync $catalog): JsonResponse
    {
        abort_unless($erp->enabled(), 404);

        if (! hash_equals((string) config('erp.token'), (string) $request->header('X-Integration-Token'))) {
            Log::warning('Aviso del sistema con token inválido.', ['ip' => $request->ip()]);
            abort(403);
        }

        $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.type' => ['required', 'in:product,service,package'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.price' => ['required', 'numeric'],
            'items.*.active' => ['required', 'boolean'],
        ]);

        // validate() solo devuelve lo que se validó; el resto (código,
        // categoría…) se toma del pedido completo.
        return response()->json(['ok' => true, 'actualizados' => $catalog->apply($request->input('items'))]);
    }
}
