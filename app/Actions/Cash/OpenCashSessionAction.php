<?php

namespace App\Actions\Cash;

use App\Enums\CashMovementType;
use App\Enums\CashSessionStatus;
use App\Exceptions\CashSessionAlreadyOpenException;
use App\Models\CashSession;
use App\Services\CashService;
use Illuminate\Support\Facades\DB;

/**
 * Apertura de caja (§23).
 *
 * Solo puede haber una caja abierta a la vez; la apertura queda además como
 * movimiento para que el histórico de la sesión esté completo.
 */
class OpenCashSessionAction
{
    public function __construct(private readonly CashService $cash) {}

    public function execute(float $openingAmount, int $userId, ?string $notes = null): CashSession
    {
        return DB::transaction(function () use ($openingAmount, $userId, $notes) {
            if ($open = $this->cash->currentSession()) {
                throw CashSessionAlreadyOpenException::for($open);
            }

            $session = CashSession::create([
                'opening_amount' => round($openingAmount, 2),
                'status' => CashSessionStatus::OPEN,
                'opened_at' => now(),
                'opened_by' => $userId,
                'notes' => $notes,
            ]);

            $this->cash->registerMovement(
                $session,
                CashMovementType::OPENING,
                round($openingAmount, 2),
                'Apertura de caja',
                userId: $userId,
            );

            return $session->load('openedBy');
        });
    }
}
