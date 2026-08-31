<?php

namespace App\Http\Controllers\Api;

use App\Actions\Cash\CloseCashSessionAction;
use App\Actions\Cash\OpenCashSessionAction;
use App\Actions\Cash\RegisterCashExpenseAction;
use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\CloseCashSessionRequest;
use App\Http\Requests\OpenCashSessionRequest;
use App\Http\Requests\StoreCashExpenseRequest;
use App\Http\Resources\CashMovementResource;
use App\Http\Resources\CashSessionResource;
use App\Models\CashSession;
use App\Services\CashService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashSessionController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly CashService $cash) {}

    public function index(Request $request): JsonResponse
    {
        $sessions = CashSession::query()
            ->with('openedBy', 'closedBy')
            ->when($request->filled('from'), fn ($q) => $q->whereDate('opened_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('opened_at', '<=', $request->date('to')))
            ->latest('opened_at')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($sessions, CashSessionResource::class);
    }

    /** Caja abierta actual, o null si no hay ninguna. */
    public function current(): JsonResponse
    {
        $session = $this->cash->currentSession();

        if ($session === null) {
            return $this->ok(null, 'No hay una caja abierta.');
        }

        $session->load('openedBy', 'movements.paymentMethod', 'movements.createdBy');

        return $this->ok(
            (new CashSessionResource($session))->additional([
                'current_expected' => $this->cash->expectedAmount($session),
                'totals_by_method' => $this->cash->totalsByMethod($session),
            ])
        );
    }

    public function show(CashSession $cashSession): JsonResponse
    {
        $cashSession->load('openedBy', 'closedBy', 'movements.paymentMethod', 'movements.createdBy');

        return $this->ok(
            (new CashSessionResource($cashSession))->additional([
                'totals_by_method' => $this->cash->totalsByMethod($cashSession),
            ])
        );
    }

    public function open(OpenCashSessionRequest $request, OpenCashSessionAction $action): JsonResponse
    {
        $session = $action->execute(
            (float) $request->input('opening_amount'),
            $request->user()->id,
            $request->input('notes'),
        );

        return $this->created(new CashSessionResource($session), 'Caja abierta correctamente.');
    }

    public function close(CloseCashSessionRequest $request, CloseCashSessionAction $action): JsonResponse
    {
        $session = $action->execute(
            (float) $request->input('counted_amount'),
            $request->user()->id,
            $request->input('notes'),
        );

        $message = abs((float) $session->difference) < 0.01
            ? 'Caja cerrada sin diferencias.'
            : sprintf('Caja cerrada con una diferencia de S/ %s.', number_format((float) $session->difference, 2));

        return $this->ok(new CashSessionResource($session), $message);
    }

    public function registerExpense(StoreCashExpenseRequest $request, RegisterCashExpenseAction $action): JsonResponse
    {
        $movement = $action->execute(
            (float) $request->input('amount'),
            $request->string('description')->value(),
            $request->user()->id,
        );

        return $this->created(new CashMovementResource($movement), 'Egreso registrado correctamente.');
    }
}
