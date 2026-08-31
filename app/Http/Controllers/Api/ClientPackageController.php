<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\ClientPackageResource;
use App\Models\ClientPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Paquetes contratados por los clientes, con su saldo de sesiones (§18).
 */
class ClientPackageController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $packages = ClientPackage::query()
            ->with('client')
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->integer('client_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            // Al registrar una atención solo interesan los que tienen saldo.
            ->when($request->boolean('consumable'), fn ($q) => $q->active()->whereColumn('used_sessions', '<', 'total_sessions'))
            ->latest('purchased_at')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($packages, ClientPackageResource::class);
    }

    public function show(ClientPackage $clientPackage): JsonResponse
    {
        return $this->ok(new ClientPackageResource(
            $clientPackage->load('client', 'sessions.attendance.service')
        ));
    }
}
