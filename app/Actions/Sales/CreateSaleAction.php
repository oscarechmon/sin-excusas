<?php

namespace App\Actions\Sales;

use App\Enums\InventoryMovementType;
use App\Enums\PackageStatus;
use App\Enums\SaleStatus;
use App\Models\ClientPackage;
use App\Models\InventoryItem;
use App\Models\Package;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Service;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Registra una venta con su detalle (§21).
 *
 * Una venta puede mezclar servicios, productos y paquetes. Cada tipo tiene un
 * efecto lateral distinto y por eso se resuelve por separado:
 *   - producto  → descuenta inventario
 *   - paquete   → crea el paquete del cliente con su saldo de sesiones
 *   - servicio  → sin efecto adicional
 *
 * Los pagos NO se registran aquí: son una operación aparte
 * (RegisterPaymentAction), porque una venta puede quedar con saldo pendiente.
 */
class CreateSaleAction
{
    public function __construct(private readonly InventoryService $inventory) {}

    /**
     * @param  array<string,mixed>  $data  Payload validado por StoreSaleRequest.
     */
    public function execute(array $data, ?int $userId = null): Sale
    {
        return DB::transaction(function () use ($data, $userId) {
            $sale = Sale::create([
                'code' => Sale::generateCode(),
                'client_id' => $data['client_id'] ?? null,
                'subtotal' => 0,
                'discount' => round((float) ($data['discount'] ?? 0), 2),
                'total' => 0,
                'paid_amount' => 0,
                'status' => SaleStatus::PENDING,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $subtotal = 0.0;

            foreach ($data['items'] as $line) {
                $subtotal += $this->addItem($sale, $line, $userId)->subtotal;
            }

            $total = max(0, round($subtotal - (float) $sale->discount, 2));

            $sale->forceFill([
                'subtotal' => round($subtotal, 2),
                'total' => $total,
            ])->save();

            return $sale->load('items.itemable', 'client');
        });
    }

    /** @param array<string,mixed> $line */
    private function addItem(Sale $sale, array $line, ?int $userId): SaleItem
    {
        $itemable = $this->resolveItemable($line['type'], (int) $line['id']);

        $quantity = (float) ($line['quantity'] ?? 1);
        $unitPrice = round((float) ($line['unit_price'] ?? $this->defaultPrice($itemable)), 2);
        $discount = round((float) ($line['discount'] ?? 0), 2);
        $subtotal = max(0, round($unitPrice * $quantity - $discount, 2));

        $item = $sale->items()->create([
            'itemable_type' => $itemable::class,
            'itemable_id' => $itemable->getKey(),
            'description' => $this->describe($itemable),
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'discount' => $discount,
            'subtotal' => $subtotal,
            'employee_id' => $line['employee_id'] ?? null,
        ]);

        $this->applySideEffects($sale, $itemable, $item, $userId);

        return $item;
    }

    private function resolveItemable(string $type, int $id): Model
    {
        return match ($type) {
            'service' => Service::findOrFail($id),
            'product' => InventoryItem::findOrFail($id),
            'package' => Package::findOrFail($id),
        };
    }

    private function defaultPrice(Model $itemable): float
    {
        return (float) match (true) {
            $itemable instanceof InventoryItem => $itemable->sale_price ?? 0,
            default => $itemable->price,
        };
    }

    private function describe(Model $itemable): string
    {
        return (string) $itemable->name;
    }

    private function applySideEffects(Sale $sale, Model $itemable, SaleItem $item, ?int $userId): void
    {
        if ($itemable instanceof InventoryItem) {
            $this->inventory->registerMovement(
                $itemable,
                InventoryMovementType::SALE,
                (float) $item->quantity,
                $sale,
                $userId,
            );

            return;
        }

        if ($itemable instanceof Package) {
            $this->createClientPackage($sale, $itemable, $item);
        }
    }

    /**
     * Vender un paquete crea el saldo de sesiones del cliente. Nombre y precio
     * se copian para que el histórico sobreviva a cambios en el catálogo.
     */
    private function createClientPackage(Sale $sale, Package $package, SaleItem $item): void
    {
        if ($sale->client_id === null) {
            return;
        }

        ClientPackage::create([
            'client_id' => $sale->client_id,
            'package_id' => $package->id,
            'package_name' => $package->name,
            'price' => $item->subtotal,
            'total_sessions' => $package->total_sessions,
            'used_sessions' => 0,
            'purchased_at' => now()->toDateString(),
            'expires_at' => $package->validity_days
                ? now()->addDays($package->validity_days)->toDateString()
                : null,
            'status' => PackageStatus::ACTIVE,
        ]);
    }
}
