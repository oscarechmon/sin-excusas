<?php

namespace App\Http\Controllers\Api;

use App\Enums\CommissionStatus;
use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommissionResource;
use App\Models\Commission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $query = Commission::query()
            ->with('employee', 'service')
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('generated_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('generated_at', '<=', $request->date('to')));

        // El total se calcula sobre el filtro completo, no sobre la página.
        $totals = (clone $query)
            ->selectRaw('status, SUM(amount) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $commissions = $query->latest('generated_at')->latest('id')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => CommissionResource::collection($commissions->getCollection()),
            'meta' => [
                'current_page' => $commissions->currentPage(),
                'last_page' => $commissions->lastPage(),
                'total' => $commissions->total(),
                'pending_amount' => round((float) ($totals[CommissionStatus::PENDING->value] ?? 0), 2),
                'paid_amount' => round((float) ($totals[CommissionStatus::PAID->value] ?? 0), 2),
            ],
        ]);
    }

    /**
     * Marca comisiones como pagadas. Acepta varias a la vez porque el pago a
     * un especialista normalmente liquida un periodo completo.
     */
    public function pay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commission_ids' => ['required', 'array', 'min:1'],
            'commission_ids.*' => ['integer', 'exists:commissions,id'],
        ]);

        $paid = DB::transaction(fn () => Commission::whereIn('id', $validated['commission_ids'])
            ->where('status', CommissionStatus::PENDING->value)
            ->update([
                'status' => CommissionStatus::PAID->value,
                'paid_at' => now(),
                'paid_by' => $request->user()->id,
            ]));

        return $this->ok(
            ['paid_count' => $paid],
            $paid === 0
                ? 'No había comisiones pendientes entre las seleccionadas.'
                : sprintf('Se marcaron %d comisiones como pagadas.', $paid)
        );
    }
}
