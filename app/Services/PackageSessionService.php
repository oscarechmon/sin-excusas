<?php

namespace App\Services;

use App\Enums\PackageStatus;
use App\Exceptions\PackageWithoutRemainingSessionsException;
use App\Models\ClientPackage;
use App\Models\ClientPackageSession;
use Illuminate\Support\Facades\DB;

/**
 * Control del saldo de sesiones de un paquete (§18).
 *
 * El saldo no se maneja restando un contador a ciegas: cada consumo crea una
 * fila en `client_package_sessions`, y el índice único sobre
 * (client_package_id, session_number) impide que un doble clic consuma dos
 * sesiones (§47).
 */
class PackageSessionService
{
    public function consumeSession(
        ClientPackage $clientPackage,
        ?int $attendanceId = null,
        ?int $userId = null,
    ): ClientPackageSession {
        return DB::transaction(function () use ($clientPackage, $attendanceId, $userId) {
            $locked = ClientPackage::whereKey($clientPackage->getKey())->lockForUpdate()->firstOrFail();

            $this->markExpiredIfNeeded($locked);

            if (! $locked->canConsumeSession()) {
                throw PackageWithoutRemainingSessionsException::for($locked);
            }

            $sessionNumber = $locked->used_sessions + 1;

            $session = ClientPackageSession::create([
                'client_package_id' => $locked->id,
                'attendance_id' => $attendanceId,
                'session_number' => $sessionNumber,
                'consumed_at' => now(),
                'user_id' => $userId,
            ]);

            $locked->used_sessions = $sessionNumber;

            if ($locked->used_sessions >= $locked->total_sessions) {
                $locked->status = PackageStatus::COMPLETED;
            }

            $locked->save();

            $clientPackage->refresh();

            return $session;
        });
    }

    /**
     * Devuelve una sesión al paquete. Se usa al anular una atención, para que
     * el cliente no pierda la sesión de un registro que se deshizo.
     */
    public function restoreSession(ClientPackageSession $session): void
    {
        DB::transaction(function () use ($session) {
            $clientPackage = ClientPackage::whereKey($session->client_package_id)
                ->lockForUpdate()->firstOrFail();

            $session->delete();

            $clientPackage->used_sessions = max(0, $clientPackage->used_sessions - 1);

            if ($clientPackage->status === PackageStatus::COMPLETED) {
                $clientPackage->status = PackageStatus::ACTIVE;
            }

            $clientPackage->save();
        });
    }

    private function markExpiredIfNeeded(ClientPackage $clientPackage): void
    {
        if ($clientPackage->status === PackageStatus::ACTIVE && $clientPackage->isExpired()) {
            $clientPackage->status = PackageStatus::EXPIRED;
            $clientPackage->save();
        }
    }
}
