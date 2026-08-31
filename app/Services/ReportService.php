<?php

namespace App\Services;

use App\Enums\CommissionStatus;
use App\Enums\SaleStatus;
use App\Models\Commission;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reportes básicos de la primera versión (§27).
 *
 * Todos los métodos aceptan un rango de fechas y devuelven filas planas, para
 * que el frontend solo tenga que mostrarlas en una tabla.
 */
class ReportService
{
    /** Ventas agrupadas por día, semana o mes. */
    public function salesByPeriod(Carbon $from, Carbon $to, string $groupBy = 'day'): array
    {
        $format = match ($groupBy) {
            'week' => '%x-S%v',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        return Sale::query()
            ->where('status', '!=', SaleStatus::CANCELLED->value)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->selectRaw("DATE_FORMAT(created_at, ?) as period, COUNT(*) as sales_count, SUM(total) as total", [$format])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'period' => $row->period,
                'sales_count' => (int) $row->sales_count,
                'total' => round((float) $row->total, 2),
            ])
            ->all();
    }

    /**
     * Ventas por concepto. `$type` acepta las clases del morph para separar
     * servicios, productos y paquetes.
     */
    public function salesByItem(Carbon $from, Carbon $to, string $itemableType): array
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', '!=', SaleStatus::CANCELLED->value)
            ->whereBetween('sales.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->where('sale_items.itemable_type', $itemableType)
            ->selectRaw('sale_items.description, SUM(sale_items.quantity) as quantity, SUM(sale_items.subtotal) as total')
            ->groupBy('sale_items.description')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'description' => $row->description,
                'quantity' => round((float) $row->quantity, 2),
                'total' => round((float) $row->total, 2),
            ])
            ->all();
    }

    /** Ventas atribuidas a cada especialista. */
    public function salesByEmployee(Carbon $from, Carbon $to): array
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('employees', 'employees.id', '=', 'sale_items.employee_id')
            ->where('sales.status', '!=', SaleStatus::CANCELLED->value)
            ->whereBetween('sales.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->selectRaw('employees.name, COUNT(*) as items_count, SUM(sale_items.subtotal) as total')
            ->groupBy('employees.id', 'employees.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'employee' => $row->name,
                'items_count' => (int) $row->items_count,
                'total' => round((float) $row->total, 2),
            ])
            ->all();
    }

    /** Ingresos y egresos de caja del periodo. */
    public function cashFlow(Carbon $from, Carbon $to): array
    {
        return DB::table('cash_movements')
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->selectRaw('type, COUNT(*) as movements_count, SUM(amount) as total')
            ->groupBy('type')
            ->get()
            ->map(fn ($row) => [
                'type' => $row->type,
                'movements_count' => (int) $row->movements_count,
                'total' => round((float) $row->total, 2),
            ])
            ->all();
    }

    public function commissions(Carbon $from, Carbon $to): array
    {
        return Commission::query()
            ->join('employees', 'employees.id', '=', 'commissions.employee_id')
            ->whereBetween('generated_at', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('employees.name, commissions.status, COUNT(*) as items_count, SUM(commissions.amount) as total')
            ->groupBy('employees.id', 'employees.name', 'commissions.status')
            ->orderBy('employees.name')
            ->get()
            ->map(fn ($row) => [
                'employee' => $row->name,
                'status' => $row->status,
                'status_label' => CommissionStatus::from($row->status)->label(),
                'items_count' => (int) $row->items_count,
                'total' => round((float) $row->total, 2),
            ])
            ->all();
    }

    /** Stock actual; con `$onlyLow` deja solo los que están en el mínimo. */
    public function stock(bool $onlyLow = false): array
    {
        return InventoryItem::query()
            ->with('category')
            ->active()
            ->when($onlyLow, fn ($q) => $q->lowStock())
            ->orderBy('name')
            ->get()
            ->map(fn (InventoryItem $item) => [
                'name' => $item->name,
                'category' => $item->category?->name,
                'unit' => $item->unit,
                'stock' => (float) $item->stock,
                'min_stock' => (float) $item->min_stock,
                'is_low_stock' => $item->isLowStock(),
            ])
            ->all();
    }
}
