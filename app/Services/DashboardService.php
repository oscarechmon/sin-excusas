<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\CommissionStatus;
use App\Enums\SaleStatus;
use App\Models\Appointment;
use App\Models\ClientPackage;
use App\Models\Commission;
use App\Models\InventoryItem;
use App\Models\Sale;
use Illuminate\Support\Carbon;

/**
 * Indicadores operativos del dashboard (§13).
 *
 * Vive en un servicio y no en el controlador para que las mismas consultas
 * puedan reutilizarse desde reportes sin duplicarlas.
 */
class DashboardService
{
    public function __construct(private readonly CashService $cash) {}

    /** @return array<string,mixed> */
    public function metrics(?Carbon $date = null): array
    {
        $day = $date ?? now();

        return [
            'sales_today' => $this->salesTotal($day),
            'appointments_today' => $this->appointmentsCount($day),
            'pending_appointments' => $this->pendingAppointments($day),
            'pending_package_sessions' => $this->pendingPackageSessions(),
            'low_stock_items' => $this->lowStockCount(),
            'cash' => $this->cashSummary(),
            'pending_commissions' => $this->pendingCommissions(),
        ];
    }

    private function salesTotal(Carbon $day): float
    {
        return round((float) Sale::whereDate('created_at', $day)
            ->where('status', '!=', SaleStatus::CANCELLED->value)
            ->sum('total'), 2);
    }

    private function appointmentsCount(Carbon $day): int
    {
        return Appointment::whereDate('appointment_date', $day)->count();
    }

    private function pendingAppointments(Carbon $day): int
    {
        return Appointment::whereDate('appointment_date', $day)
            ->whereIn('status', [
                AppointmentStatus::PENDING->value,
                AppointmentStatus::CONFIRMED->value,
            ])
            ->count();
    }

    /** Sesiones compradas y aún no utilizadas en paquetes vigentes. */
    private function pendingPackageSessions(): int
    {
        return (int) ClientPackage::active()
            ->selectRaw('COALESCE(SUM(total_sessions - used_sessions), 0) as pending')
            ->value('pending');
    }

    private function lowStockCount(): int
    {
        return InventoryItem::active()->lowStock()->count();
    }

    /** @return array<string,mixed> */
    private function cashSummary(): array
    {
        $session = $this->cash->currentSession();

        if ($session === null) {
            return ['is_open' => false, 'expected' => 0.0, 'opened_at' => null];
        }

        return [
            'is_open' => true,
            'expected' => $this->cash->expectedAmount($session),
            'opening_amount' => (float) $session->opening_amount,
            'opened_at' => $session->opened_at?->toDateTimeString(),
        ];
    }

    private function pendingCommissions(): float
    {
        return round((float) Commission::where('status', CommissionStatus::PENDING->value)
            ->sum('amount'), 2);
    }
}
