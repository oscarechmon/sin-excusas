<?php

namespace App\Actions\Cash;

use App\Enums\CashSessionStatus;
use App\Exceptions\CashSessionNotOpenException;
use App\Models\CashSession;
use App\Services\CashService;
use Illuminate\Support\Facades\DB;

/**
 * Cierre de caja (§23).
 *
 * Guarda el esperado, el contado y la diferencia. La diferencia se conserva
 * aunque sea distinta de cero: cuadrar a la fuerza escondería un faltante.
 */
class CloseCashSessionAction
{
    public function __construct(private readonly CashService $cash) {}

    public function execute(float $countedAmount, int $userId, ?string $notes = null): CashSession
    {
        return DB::transaction(function () use ($countedAmount, $userId, $notes) {
            $session = $this->cash->currentSession();

            if ($session === null) {
                throw CashSessionNotOpenException::make();
            }

            $locked = CashSession::whereKey($session->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isOpen()) {
                throw CashSessionNotOpenException::make();
            }

            $expected = $this->cash->expectedAmount($locked);
            $counted = round($countedAmount, 2);

            $locked->forceFill([
                'expected_amount' => $expected,
                'counted_amount' => $counted,
                'difference' => round($counted - $expected, 2),
                'status' => CashSessionStatus::CLOSED,
                'closed_at' => now(),
                'closed_by' => $userId,
                'notes' => $notes ?? $locked->notes,
            ])->save();

            return $locked->load('openedBy', 'closedBy');
        });
    }
}
