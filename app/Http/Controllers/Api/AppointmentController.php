<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Appointment::with('client', 'service', 'employee');

        if ($request->has('date')) {
            $query->whereDate('appointment_date', $request->input('date'));
        }

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->has('client_id')) {
            $query->where('client_id', $request->input('client_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('appointment_date', [
                $request->input('from_date'),
                $request->input('to_date'),
            ]);
        }

        $appointments = $query->orderBy('appointment_date')->orderBy('start_time')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => AppointmentResource::collection($appointments),
            'meta' => [
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage(),
                'total' => $appointments->total(),
            ],
        ]);
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $appointment = Appointment::create($request->validated());
        $appointment->load('client', 'service', 'employee');

        return response()->json([
            'success' => true,
            'message' => 'Cita creada correctamente.',
            'data' => new AppointmentResource($appointment),
        ], 201);
    }

    public function show(Appointment $appointment): JsonResponse
    {
        $appointment->load('client', 'service', 'employee');

        return response()->json([
            'success' => true,
            'data' => new AppointmentResource($appointment),
        ]);
    }

    public function update(StoreAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $appointment->update($request->validated());
        $appointment->load('client', 'service', 'employee');

        return response()->json([
            'success' => true,
            'message' => 'Cita actualizada correctamente.',
            'data' => new AppointmentResource($appointment),
        ]);
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $appointment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cita eliminada correctamente.',
        ]);
    }
}
