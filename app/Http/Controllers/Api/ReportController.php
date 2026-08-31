<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Package;
use App\Models\Service;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly ReportService $reports) {}

    public function sales(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);

        return $this->ok([
            'by_period' => $this->reports->salesByPeriod($from, $to, $request->string('group_by', 'day')->value()),
            'by_service' => $this->reports->salesByItem($from, $to, Service::class),
            'by_product' => $this->reports->salesByItem($from, $to, InventoryItem::class),
            'by_package' => $this->reports->salesByItem($from, $to, Package::class),
            'by_employee' => $this->reports->salesByEmployee($from, $to),
        ]);
    }

    public function cash(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);

        return $this->ok(['flow' => $this->reports->cashFlow($from, $to)]);
    }

    public function commissions(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);

        return $this->ok(['commissions' => $this->reports->commissions($from, $to)]);
    }

    public function stock(Request $request): JsonResponse
    {
        return $this->ok(['stock' => $this->reports->stock($request->boolean('only_low'))]);
    }

    /**
     * Rango consultado. Por defecto el mes en curso, para que la pantalla
     * cargue con datos útiles sin obligar a elegir fechas.
     *
     * @return array{0:Carbon,1:Carbon}
     */
    private function range(Request $request): array
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();

        return [Carbon::parse($from), Carbon::parse($to)];
    }
}
