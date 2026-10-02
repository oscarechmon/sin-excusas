<?php

namespace App\Console\Commands;

use App\Exceptions\ErpException;
use App\Services\Erp\CatalogSync;
use App\Services\Erp\ErpClient;
use App\Services\Erp\OnlineOrderRegistrar;
use Illuminate\Console\Command;

/**
 * Lo mismo que el botón "Sincronizar ahora" del panel: trae el catálogo
 * completo del sistema y reintenta los pedidos pagados que no llegaron allá.
 *
 * Los cambios llegan solos al instante; esto recupera los avisos perdidos
 * (por ejemplo, si la web estaba caída). Se puede programar en un cron.
 */
class ErpSincronizarCommand extends Command
{
    protected $signature = 'erp:sincronizar';

    protected $description = 'Trae el catálogo del sistema y reintenta los pedidos web pendientes';

    public function handle(ErpClient $erp, CatalogSync $catalog, OnlineOrderRegistrar $orders): int
    {
        if (! $erp->enabled()) {
            $this->error('Configura ERP_URL y ERP_TOKEN en el .env (y ejecuta php artisan optimize).');

            return self::FAILURE;
        }

        try {
            $result = $catalog->pull();
        } catch (ErpException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $pending = $orders->retryPending();

        $this->info("Catálogo: {$result['actualizados']} ítems actualizados, {$result['desactivados']} desactivados.");
        $this->info("Pedidos web: {$pending['registrados']} registrados, {$pending['pendientes']} siguen pendientes.");

        return $pending['pendientes'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
