<?php

namespace App\Services;

use App\Enums\CommissionStatus;
use App\Models\Attendance;
use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\Employee;
use App\Models\Service;

/**
 * Resolución y cálculo de comisiones (§26).
 *
 * La comisión guarda el monto ya calculado además del porcentaje: si mañana
 * cambia la regla, el histórico no se altera.
 */
class CommissionService
{
    /**
     * Regla aplicable, de la más específica a la más general:
     *   1. empleado + servicio
     *   2. empleado (cualquier servicio)
     *   3. servicio (cualquier empleado)
     *   4. regla general del centro
     */
    public function resolveRule(Employee $employee, ?Service $service): ?CommissionRule
    {
        $candidates = CommissionRule::active()
            ->where(function ($query) use ($employee) {
                $query->where('employee_id', $employee->id)->orWhereNull('employee_id');
            })
            ->where(function ($query) use ($service) {
                $query->whereNull('service_id');
                if ($service !== null) {
                    $query->orWhere('service_id', $service->id);
                }
            })
            ->get();

        return $candidates
            ->sortByDesc(fn (CommissionRule $rule) => $this->specificity($rule))
            ->first();
    }

    /**
     * Genera la comisión de una atención.
     *
     * Devuelve null si no hay regla aplicable: no todos los servicios
     * comisionan y eso no debe interrumpir el registro de la atención.
     */
    public function generateForAttendance(Attendance $attendance, float $baseAmount): ?Commission
    {
        $attendance->loadMissing('employee', 'service');

        if ($attendance->employee === null) {
            return null;
        }

        $rule = $this->resolveRule($attendance->employee, $attendance->service);

        if ($rule === null) {
            return null;
        }

        $amount = $rule->type->calculate($baseAmount, (float) $rule->value);

        if ($amount <= 0) {
            return null;
        }

        // firstOrCreate y no create: el índice único sobre attendance_id evita
        // la doble comisión, pero así el reintento no lanza una excepción.
        return Commission::firstOrCreate(
            ['attendance_id' => $attendance->id],
            [
                'employee_id' => $attendance->employee_id,
                'service_id' => $attendance->service_id,
                'base_amount' => round($baseAmount, 2),
                'type' => $rule->type,
                'value' => $rule->value,
                'amount' => $amount,
                'status' => CommissionStatus::PENDING,
                'generated_at' => $attendance->attended_at ?? now()->toDateString(),
            ]
        );
    }

    private function specificity(CommissionRule $rule): int
    {
        return ($rule->employee_id !== null ? 2 : 0) + ($rule->service_id !== null ? 1 : 0);
    }
}
