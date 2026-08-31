<?php

namespace App\Services;

use App\Enums\CashMovementType;
use App\Exceptions\CashSessionNotOpenException;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;

/**
 * Movimientos y saldos de caja (§23).
 *
 * El saldo esperado se calcula sumando los movimientos, nunca guardando un
 * campo que se sobrescribe.
 */
class CashService
{
    public function currentSession(): ?CashSession
    {
        return CashSession::open()->latest('opened_at')->first();
    }

    public function requireOpenSession(): CashSession
    {
        return $this->currentSession() ?? throw CashSessionNotOpenException::make();
    }

    public function registerMovement(
        CashSession $session,
        CashMovementType $type,
        float $amount,
        string $description,
        ?int $paymentMethodId = null,
        ?Model $source = null,
        ?int $userId = null,
    ): CashMovement {
        $movement = new CashMovement([
            'cash_session_id' => $session->id,
            'type' => $type,
            'payment_method_id' => $paymentMethodId,
            'amount' => round($amount, 2),
            'description' => $description,
            'created_by' => $userId,
        ]);

        if ($source !== null) {
            $movement->source()->associate($source);
        }

        $movement->save();

        return $movement;
    }

    /**
     * Registra el ingreso de un pago en la caja abierta.
     *
     * Si no hay caja abierta no falla: no todos los negocios operan con caja
     * todo el tiempo, y un pago no debe perderse por eso. Devuelve null para
     * que quien llama pueda avisar si le interesa.
     */
    public function registerPayment(Payment $payment, ?int $userId = null): ?CashMovement
    {
        $session = $this->currentSession();

        if ($session === null) {
            return null;
        }

        $payment->loadMissing('sale', 'paymentMethod');

        return $this->registerMovement(
            $session,
            CashMovementType::SALE,
            (float) $payment->amount,
            sprintf('Pago de la venta %s', $payment->sale?->code ?? '—'),
            $payment->payment_method_id,
            $payment->sale,
            $userId,
        );
    }

    /**
     * Saldo esperado en caja: apertura más la suma de movimientos.
     *
     * Solo cuentan los métodos en efectivo, porque el arqueo compara contra
     * el dinero físico; un cobro por Yape no está en el cajón.
     */
    public function expectedAmount(CashSession $session): float
    {
        $movements = (float) $session->movements()
            ->where('type', '!=', CashMovementType::OPENING->value)
            ->whereHas('paymentMethod', fn ($q) => $q->where('is_cash', true))
            ->sum('amount');

        // Los egresos y ajustes no llevan método de pago y siempre son efectivo.
        $withoutMethod = (float) $session->movements()
            ->where('type', '!=', CashMovementType::OPENING->value)
            ->whereNull('payment_method_id')
            ->sum('amount');

        return round((float) $session->opening_amount + $movements + $withoutMethod, 2);
    }

    /** @return array<string,float> Totales por método de pago, para el cierre. */
    public function totalsByMethod(CashSession $session): array
    {
        return $session->movements()
            ->with('paymentMethod')
            ->whereNotNull('payment_method_id')
            ->get()
            ->groupBy(fn (CashMovement $m) => $m->paymentMethod?->name ?? 'Sin método')
            ->map(fn ($group) => round((float) $group->sum('amount'), 2))
            ->all();
    }
}
