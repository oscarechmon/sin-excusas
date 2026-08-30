<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Service::with('category', 'employees');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        $services = $query->orderBy('name')->paginate(15);

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
        $service->update($request->validated());

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
