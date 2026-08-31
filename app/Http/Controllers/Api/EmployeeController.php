<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $employees = Employee::query()
            ->with('services')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('active'), fn ($q) => $q->where('active', $request->boolean('active')))
            ->when($request->filled('service_id'), fn ($q) => $q->whereHas(
                'services',
                fn ($s) => $s->where('services.id', $request->integer('service_id'))
            ))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($employees, EmployeeResource::class);
    }

    public function show(Employee $employee): JsonResponse
    {
        return $this->ok(new EmployeeResource($employee->load('services')));
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = Employee::create($request->safe()->except('service_ids'));
        $employee->services()->sync($request->input('service_ids', []));

        return $this->created(
            new EmployeeResource($employee->load('services')),
            'Trabajador registrado correctamente.'
        );
    }

    public function update(StoreEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $employee->update($request->safe()->except('service_ids'));

        if ($request->has('service_ids')) {
            $employee->services()->sync($request->input('service_ids', []));
        }

        return $this->ok(
            new EmployeeResource($employee->load('services')),
            'Trabajador actualizado correctamente.'
        );
    }

    public function destroy(Employee $employee): JsonResponse
    {
        // No se elimina si tiene historial: perderíamos la trazabilidad de
        // atenciones y comisiones. Se desactiva en su lugar (§34).
        if ($employee->attendances()->exists() || $employee->appointments()->exists()) {
            $employee->update(['active' => false]);

            return $this->ok(null, 'El trabajador tiene historial, por lo que se desactivó en lugar de eliminarse.');
        }

        $employee->delete();

        return $this->ok(null, 'Trabajador eliminado correctamente.');
    }
}
