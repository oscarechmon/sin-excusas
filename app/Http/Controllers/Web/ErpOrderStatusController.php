<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\OnlineOrder;
use App\Services\Erp\ErpClient;
use App\Services\Erp\OrderStatusApplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Aviso del sistema (ERP): el personal movió un pedido en su seguimiento
 * (preparación, envío, entrega o anulación). Llega con el token compartido.
 */
class ErpOrderStatusController extends Controller
{
    public function __invoke(Request $request, string $code, ErpClient $erp, OrderStatusApplier $statuses): JsonResponse
    {
        abort_unless($erp->enabled(), 404);

        if (! hash_equals((string) config('erp.token'), (string) $request->header('X-Integration-Token'))) {
            Log::warning('Aviso de pedido del sistema con token inválido.', ['ip' => $request->ip()]);
            abort(403);
        }

        $data = $request->validate([
            'status' => ['required', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:500'],
            'user_name' => ['nullable', 'string', 'max:255'],
            'happened_at' => ['nullable', 'date'],
        ]);

        $order = OnlineOrder::where('code', $code)->firstOrFail();
        $statuses->apply($order, $data['status'], $data['note'] ?? null, $data['user_name'] ?? null, $data['happened_at'] ?? null);

        return response()->json(['ok' => true, 'status' => $order->fresh()->status->value]);
    }
}
