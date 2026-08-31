<?php

namespace App\Actions\Cash;

use App\Enums\CashMovementType;
use App\Models\CashMovement;
use App\Services\CashService;

/**
 * Egreso manual de caja (§23). El monto se guarda en negativo para que el
 * saldo siga siendo la simple suma de los movimientos.
 */
class RegisterCashExpenseAction
{
    public function __construct(private readonly CashService $cash) {}

    public function execute(float $amount, string $description, int $userId): CashMovement
    {
        $session = $this->cash->requireOpenSession();

        return $this->cash->registerMovement(
            $session,
            CashMovementType::EXPENSE,
            -abs(round($amount, 2)),
            $description,
            userId: $userId,
        );
    }
}
