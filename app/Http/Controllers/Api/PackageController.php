<?php

namespace App\Http\Controllers\Api;

use App\Enums\PackageStatus;
use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\SellPackageRequest;
use App\Http\Requests\StorePackageRequest;
use App\Http\Resources\ClientPackageResource;
use App\Http\Resources\PackageResource;
use App\Models\ClientPackage;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $packages = Package::query()
            ->with('services')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('active'), fn ($q) => $q->where('active', $request->boolean('active')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($packages, PackageResource::class);
    }

    public function show(Package $package): JsonResponse
    {
        return $this->ok(new PackageResource($package->load('services')));
    }

    public function store(StorePackageRequest $request): JsonResponse
    {
        $package = Package::create($request->safe()->except('service_ids'));
        $package->services()->sync($request->input('service_ids', []));

        return $this->created(
            new PackageResource($package->load('services')),
            'Paquete creado correctamente.'
        );
    }

    public function update(StorePackageRequest $request, Package $package): JsonResponse
    {
        $package->update($request->safe()->except('service_ids'));

        if ($request->has('service_ids')) {
            $package->services()->sync($request->input('service_ids', []));
        }

        return $this->ok(
            new PackageResource($package->load('services')),
            'Paquete actualizado correctamente.'
        );
    }

    public function destroy(Package $package): JsonResponse
    {
        if ($package->clientPackages()->exists()) {
            $package->update(['active' => false]);

            return $this->ok(null, 'El paquete ya fue vendido, por lo que se desactivó en lugar de eliminarse.');
        }

        $package->delete();

        return $this->ok(null, 'Paquete eliminado correctamente.');
    }

    /**
     * Venta directa de un paquete a un cliente, sin pasar por el módulo de
     * ventas. Pensado para cargar paquetes vendidos antes de usar el sistema.
     */
    public function sell(SellPackageRequest $request): JsonResponse
    {
        $package = Package::findOrFail($request->integer('package_id'));
        $purchasedAt = $request->date('purchased_at') ?? now();

        $clientPackage = ClientPackage::create([
            'client_id' => $request->integer('client_id'),
            'package_id' => $package->id,
            'package_name' => $package->name,
            'price' => $request->filled('price') ? (float) $request->input('price') : (float) $package->price,
            'total_sessions' => $package->total_sessions,
            'used_sessions' => 0,
            'purchased_at' => $purchasedAt->toDateString(),
            'expires_at' => $package->validity_days
                ? $purchasedAt->copy()->addDays($package->validity_days)->toDateString()
                : null,
            'status' => PackageStatus::ACTIVE,
        ]);

        return $this->created(
            new ClientPackageResource($clientPackage->load('client')),
            'Paquete asignado al cliente correctamente.'
        );
    }
}
