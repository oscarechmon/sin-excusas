<?php

namespace App\Console\Commands;

use App\Exceptions\ErpException;
use App\Services\Erp\CatalogSync;
use App\Services\Erp\ClientLinker;
use App\Services\Erp\ErpClient;
use App\Services\Erp\OnlineOrderRegistrar;
use Illuminate\Console\Command;

/**
 * Lo mismo que el botón "Sincronizar ahora" del panel: trae el catálogo
 * completo del sistema (enlaza lo nuevo y renueva el que lee la web),
 * reintenta los pedidos pagados que no llegaron allá, enlaza los clientes de
 * la tienda pendientes y trae el seguimiento de los pedidos en curso.
 *
 * Los cambios llegan solos al instante; esto recupera los avisos perdidos
 * (por ejemplo, si la web estaba caída). Se puede programar en un cron.
 */
class ErpSincronizarCommand extends Command
{
    protected $signature = 'erp:sincronizar';

    protected $description = 'Trae el catálogo y el seguimiento de pedidos del sistema, y reintenta lo pendiente';

    public function handle(ErpClient $erp, CatalogSync $catalog, OnlineOrderRegistrar $orders, ClientLinker $clients): int
    {
        if (! $erp->enabled()) {
            $this->error('Configura ERP_URL y ERP_TOKEN en el .env (y ejecuta php artisan optimize).');

            return self::FAILURE;
        }

        try {
            $result = $catalog->pull();
            $tracking = $orders->pullStatuses();
        } catch (ErpException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $pending = $orders->retryPending();
        $linked = $clients->linkPending();

        $this->info("Catálogo: {$result['actualizados']} ítems enlazados; {$result['fuera_del_sistema']} ya no están en el sistema y no se muestran.");
        $this->info("Pedidos web: {$pending['registrados']} registrados, {$pending['pendientes']} siguen pendientes; {$tracking} con seguimiento nuevo.");
        $this->info("Clientes de la tienda: {$linked['enlazados']} enlazados, {$linked['pendientes']} pendientes.");

        return $pending['pendientes'] + $linked['pendientes'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
