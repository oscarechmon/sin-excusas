<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\Erp\ErpClient;
use App\Services\Erp\LiveCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ServiceController extends Controller
{
    public function index(Request $request, ErpClient $erp, LiveCatalog $live): JsonResponse
    {
        $query = Service::with('category', 'employees')->orderBy('name');

        if ($erp->enabled()) {
            // Categoría y estado son del sistema: se filtran sobre lo que dice allá.
            $all = $live->hydrate($query->get())
                ->when($request->has('category_id'), fn ($services) => $services->where('category_id', $request->input('category_id')))
                ->when($request->has('active'), fn ($services) => $services->where('active', $request->boolean('active')))
                ->values();
            $page = max(1, $request->integer('page', 1));
            $services = new LengthAwarePaginator($all->forPage($page, 15)->values(), $all->count(), 15, $page);
        } else {
            if ($request->has('category_id')) {
                $query->where('category_id', $request->input('category_id'));
            }

            if ($request->has('active')) {
                $query->where('active', $request->boolean('active'));
            }

            $services = $query->paginate(15);
        }

        return response()->json([
            'success' => true,
            'data' => ServiceResource::collection($services),
            'meta' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'total' => $services->total(),
            ],
        ]);
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $service = Service::create($request->validated());

        if ($request->has('employee_ids')) {
            $service->employees()->sync($request->input('employee_ids'));
        }

        $service->load('category', 'employees');

        return response()->json([
            'success' => true,
            'message' => 'Servicio creado correctamente.',
            'data' => new ServiceResource($service),
        ], 201);
    }

    public function update(StoreServiceRequest $request, Service $service): JsonResponse
    {
        // Con el sistema conectado, nombre, categoría, precio y estado llegan
        // de allá; aquí queda lo de la web y la agenda.
        $service->update(app(ErpClient::class)->enabled()
            ? $request->safe()->only(['duration_minutes', 'description'])
            : $request->validated());

        if ($request->has('employee_ids')) {
            $service->employees()->sync($request->input('employee_ids'));
        }

        $service->load('category', 'employees');

        return response()->json([
            'success' => true,
            'message' => 'Servicio actualizado correctamente.',
            'data' => new ServiceResource($service),
        ]);
    }

    public function destroy(Service $service): JsonResponse
    {
        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Servicio eliminado correctamente.',
        ]);
    }
}
