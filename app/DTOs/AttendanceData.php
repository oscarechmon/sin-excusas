<?php

namespace App\DTOs;

/**
 * Datos de una atención a confirmar.
 *
 * Existe como DTO —y no como array suelto— porque `supplies` es una estructura
 * anidada que atraviesa varias capas (request → action → servicio de
 * inventario) y tiparla evita errores silenciosos de clave mal escrita.
 */
final readonly class AttendanceData
{
    /**
     * @param  array<int,array{inventory_item_id:int,quantity:float}>  $supplies
     */
    public function __construct(
        public int $clientId,
        public int $serviceId,
        public int $employeeId,
        public string $attendedAt,
        public ?int $appointmentId = null,
        public ?int $clientPackageId = null,
        public ?string $observations = null,
        public ?string $measurements = null,
        public array $supplies = [],
        public ?int $createdBy = null,
    ) {}

    /** @param array<string,mixed> $data Payload ya validado por el Form Request. */
    public static function fromArray(array $data, ?int $createdBy = null): self
    {
        return new self(
            clientId: (int) $data['client_id'],
            serviceId: (int) $data['service_id'],
            employeeId: (int) $data['employee_id'],
            attendedAt: $data['attended_at'],
            appointmentId: isset($data['appointment_id']) ? (int) $data['appointment_id'] : null,
            clientPackageId: isset($data['client_package_id']) ? (int) $data['client_package_id'] : null,
            observations: $data['observations'] ?? null,
            measurements: $data['measurements'] ?? null,
            supplies: array_map(
                fn (array $s) => [
                    'inventory_item_id' => (int) $s['inventory_item_id'],
                    'quantity' => (float) $s['quantity'],
                ],
                $data['supplies'] ?? []
            ),
            createdBy: $createdBy,
        );
    }
}
