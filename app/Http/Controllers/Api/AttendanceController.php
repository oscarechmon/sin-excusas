<?php

namespace App\Http\Controllers\Api;

use App\Actions\Attendances\ConfirmAttendanceAction;
use App\DTOs\AttendanceData;
use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $attendances = Attendance::query()
            ->with('client', 'service', 'employee', 'clientPackage')
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->integer('client_id')))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('service_id'), fn ($q) => $q->where('service_id', $request->integer('service_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('attended_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('attended_at', '<=', $request->date('to')))
            ->latest('attended_at')
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($attendances, AttendanceResource::class);
    }

    public function show(Attendance $attendance): JsonResponse
    {
        return $this->ok(new AttendanceResource($attendance->load(
            'client', 'service', 'employee', 'clientPackage', 'supplies.item', 'commission'
        )));
    }

    /**
     * Confirma la atención. Toda la cadena de efectos (sesión de paquete,
     * inventario, comisión) ocurre dentro de la Action, en una transacción.
     */
    public function store(StoreAttendanceRequest $request, ConfirmAttendanceAction $action): JsonResponse
    {
        $attendance = $action->execute(
            AttendanceData::fromArray($request->validated(), $request->user()->id)
        );

        return $this->created(
            new AttendanceResource($attendance),
            'Atención registrada correctamente.'
        );
    }

    /**
     * Insumos configurados de un servicio con su cantidad referencial (§20).
     * El frontend los precarga y permite ajustar las cantidades antes de
     * confirmar.
     */
    public function suppliesForService(Service $service): JsonResponse
    {
        $supplies = $service->supplies()->get()->map(fn ($item) => [
            'inventory_item_id' => $item->id,
            'name' => $item->name,
            'unit' => $item->unit,
            'stock' => (float) $item->stock,
            'default_quantity' => (float) $item->pivot->default_quantity,
        ]);

        return $this->ok($supplies);
    }
}
