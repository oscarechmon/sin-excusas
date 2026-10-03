<?php

namespace App\Actions\Attendances;

use App\DTOs\AttendanceData;
use App\Enums\AppointmentStatus;
use App\Enums\InventoryMovementType;
use App\Exceptions\ErpException;
use App\Models\Appointment;
use App\Models\Attendance;
use App\Models\ClientPackage;
use App\Models\InventoryItem;
use App\Models\Service;
use App\Services\CommissionService;
use App\Services\Erp\ErpClient;
use App\Services\Erp\LiveCatalog;
use App\Services\InventoryService;
use App\Services\PackageSessionService;
use Illuminate\Support\Facades\DB;

/**
 * Confirma una atención (§19), el proceso central del sistema.
 *
 * Encadena cinco efectos que deben ocurrir juntos o no ocurrir:
 *
 *   historial → sesión de paquete → movimientos de inventario
 *            → stock → comisión
 *
 * Todo va dentro de una única transacción: si falla el descuento de un insumo,
 * no puede quedar una atención registrada ni una sesión consumida (§11).
 */
class ConfirmAttendanceAction
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly PackageSessionService $packageSessions,
        private readonly CommissionService $commissions,
        private readonly ErpClient $erp,
        private readonly LiveCatalog $live,
    ) {}

    public function execute(AttendanceData $data): Attendance
    {
        return DB::transaction(function () use ($data) {
            $service = Service::findOrFail($data->serviceId);

            $attendance = Attendance::create([
                'client_id' => $data->clientId,
                'service_id' => $data->serviceId,
                'employee_id' => $data->employeeId,
                'appointment_id' => $data->appointmentId,
                'client_package_id' => $data->clientPackageId,
                'attended_at' => $data->attendedAt,
                'observations' => $data->observations,
                'measurements' => $data->measurements,
                'created_by' => $data->createdBy,
            ]);

            $this->consumePackageSession($attendance, $data);
            $erpSupplies = $this->discountSupplies($attendance, $data);
            $this->closeAppointment($data);

            // La comisión se calcula sobre el precio del servicio. Si la
            // atención salió de un paquete, la base es el valor prorrateado
            // de la sesión y no el precio de lista.
            $this->commissions->generateForAttendance(
                $attendance,
                $this->commissionBase($attendance, $service)
            );

            // Con el sistema conectado, el stock de los insumos es suyo. Se le
            // avisa al final: si algo de lo anterior falla, el sistema no llega
            // a descontar nada; si el sistema no tiene stock, todo se revierte.
            if ($erpSupplies !== []) {
                $this->erp->registerConsumption(
                    "atencion-{$attendance->id}",
                    $erpSupplies,
                    "Atención #{$attendance->id}: {$service->name}",
                );
                $this->live->forget();
            }

            return $attendance->load('client', 'service', 'employee', 'supplies.item', 'commission');
        });
    }

    private function consumePackageSession(Attendance $attendance, AttendanceData $data): void
    {
        if ($data->clientPackageId === null) {
            return;
        }

        $clientPackage = ClientPackage::findOrFail($data->clientPackageId);

        $session = $this->packageSessions->consumeSession(
            $clientPackage,
            $attendance->id,
            $data->createdBy,
        );

        $attendance->forceFill(['session_number' => $session->session_number])->save();
    }

    /**
     * Registra los insumos usados. Sin sistema conectado, los descuenta aquí;
     * con él, devuelve lo que hay que descontar allá (id del sistema y cantidad).
     *
     * @return list<array{product_id:int, quantity:float}>
     */
    private function discountSupplies(Attendance $attendance, AttendanceData $data): array
    {
        if ($data->supplies === []) {
            return [];
        }

        $erpSupplies = [];

        $items = InventoryItem::whereIn('id', array_column($data->supplies, 'inventory_item_id'))
            ->get()
            ->keyBy('id');

        foreach ($data->supplies as $supply) {
            $item = $items->get($supply['inventory_item_id']);

            if ($item === null || $supply['quantity'] <= 0) {
                continue;
            }

            $attendance->supplies()->create([
                'inventory_item_id' => $item->id,
                'quantity' => $supply['quantity'],
            ]);

            if ($this->erp->enabled()) {
                $erpSupplies[] = [
                    'product_id' => $item->erp_id ?? throw ErpException::notLinked($item->name),
                    'quantity' => (float) $supply['quantity'],
                ];

                continue;
            }

            // Lanza InsufficientStockException si no alcanza, revirtiendo
            // toda la transacción.
            $this->inventory->registerMovement(
                $item,
                InventoryMovementType::SERVICE_USAGE,
                $supply['quantity'],
                $attendance,
                $data->createdBy,
            );
        }

        return $erpSupplies;
    }

    /** Una cita atendida pasa a estado "atendida" al confirmar la atención. */
    private function closeAppointment(AttendanceData $data): void
    {
        if ($data->appointmentId === null) {
            return;
        }

        Appointment::whereKey($data->appointmentId)
            ->update(['status' => AppointmentStatus::ATTENDED->value]);
    }

    private function commissionBase(Attendance $attendance, Service $service): float
    {
        if ($attendance->clientPackage === null) {
            return (float) $service->price;
        }

        $package = $attendance->clientPackage;
        $sessions = max(1, $package->total_sessions);

        return round((float) $package->price / $sessions, 2);
    }
}
