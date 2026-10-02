<?php

namespace App\Console\Commands;

use App\Exceptions\ErpException;
use App\Models\InventoryItem;
use App\Models\Service;
use App\Services\Erp\CatalogSync;
use App\Services\Erp\ErpClient;
use Illuminate\Console\Command;

/**
 * Se ejecuta una vez, al conectar la web con el sistema: da de alta allá los
 * productos, insumos y servicios que ya existían aquí (con su stock actual) y
 * los deja enlazados. Desde ahí, el sistema es el dueño.
 *
 * Se puede repetir sin miedo: el sistema reconoce cada ítem por su código
 * (WEB-P-12, WEB-S-5…) y no lo duplica, y aquí solo se toma lo no enlazado.
 */
class ErpVincularCommand extends Command
{
    protected $signature = 'erp:vincular';

    protected $description = 'Da de alta en el sistema el catálogo actual de la web y lo enlaza (una sola vez)';

    public function handle(ErpClient $erp, CatalogSync $catalog): int
    {
        if (! $erp->enabled()) {
            $this->error('Configura ERP_URL y ERP_TOKEN en el .env (y ejecuta php artisan optimize).');

            return self::FAILURE;
        }

        try {
            $products = InventoryItem::with('category')->whereNull('erp_id')->orderBy('id')->get();
            foreach ($products as $item) {
                $this->link($item, $erp->importProduct([
                    'code' => CatalogSync::webCode($item),
                    'type' => 'product',
                    'name' => $item->name,
                    'description' => $item->description,
                    'category' => $item->category?->name,
                    'unit' => $item->unit,
                    'price' => (float) ($item->sale_price ?? 0),
                    'cost' => (float) $item->cost,
                    'stock' => (float) $item->stock,
                    'stock_min' => (float) $item->min_stock,
                    'active' => $item->active,
                ]), $catalog);
            }

            $services = Service::with('category')->whereNull('erp_id')->orderBy('id')->get();
            foreach ($services as $service) {
                $this->link($service, $erp->importProduct([
                    'code' => CatalogSync::webCode($service),
                    'type' => 'service',
                    'name' => $service->name,
                    'description' => $service->description,
                    'category' => $service->category?->name,
                    'price' => (float) $service->price,
                    'active' => $service->active,
                ]), $catalog);
            }

            // Lo que el sistema ya tenía y la web no (p. ej. del POS) llega sin publicar.
            $result = $catalog->pull();
        } catch (ErpException $e) {
            $this->error($e->getMessage());
            $this->line('Lo enlazado hasta aquí queda enlazado; vuelve a ejecutar el comando para seguir.');

            return self::FAILURE;
        }

        $this->info("Enlazados: {$products->count()} productos e insumos y {$services->count()} servicios.");
        $this->info("Catálogo sincronizado: {$result['actualizados']} ítems.");

        return self::SUCCESS;
    }

    /**
     * El sincronizador reconoce el código WEB-… y enlaza esta misma fila (el
     * aviso que manda el sistema por su cuenta hace lo mismo).
     *
     * @param  array<string, mixed>  $remote
     */
    private function link(InventoryItem|Service $local, array $remote, CatalogSync $catalog): void
    {
        $catalog->apply([$remote]);

        $this->line("  {$remote['code']}  {$local->name}");
    }
}
