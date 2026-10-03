<?php

namespace App\Console\Commands;

use App\Exceptions\ErpException;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Package;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Erp\ErpClient;
use App\Services\Erp\LiveCatalog;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Se ejecuta una vez, al pasar el catálogo de la web al sistema: manda allá lo
 * que la web mostraba de cada ítem enlazado (foto, descripción, si se
 * publicaba y la duración de los servicios) y las descripciones de sus
 * categorías. Desde ahí todo se administra en el sistema y la web solo lo
 * muestra.
 *
 * Se puede repetir sin miedo: lo que en el sistema ya tiene decidido si se
 * publica (porque ya se pasó o porque se editó allá) no se toca, y una
 * categoría solo toma la descripción si allá no tiene una.
 */
class ErpFichasCommand extends Command
{
    protected $signature = 'erp:fichas';

    protected $description = 'Pasa al sistema las fotos, descripciones y lo publicado del catálogo de la web (una sola vez)';

    private int $sent = 0;

    private int $skipped = 0;

    private int $failed = 0;

    public function handle(ErpClient $erp, LiveCatalog $live): int
    {
        if (! $erp->enabled()) {
            $this->error('Configura ERP_URL y ERP_TOKEN en el .env (y ejecuta php artisan optimize).');

            return self::FAILURE;
        }

        try {
            $remote = $live->fresh(array_merge(
                InventoryItem::whereNotNull('erp_id')->pluck('erp_id')->all(),
                Service::whereNotNull('erp_id')->pluck('erp_id')->all(),
                Package::whereNotNull('erp_id')->pluck('erp_id')->all(),
            ));
        } catch (ErpException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach (InventoryItem::whereNotNull('erp_id')->orderBy('id')->get() as $item) {
            $this->send($erp, $item, $remote, [
                'description' => $item->description,
                // Un insumo nunca se mostró en la web.
                'web_published' => $item->is_published && $item->is_sellable,
            ]);
        }

        foreach (Service::whereNotNull('erp_id')->orderBy('id')->get() as $service) {
            $this->send($erp, $service, $remote, [
                'description' => $service->description,
                'web_published' => (bool) $service->is_published,
                'duration_minutes' => $service->duration_minutes,
            ]);
        }

        foreach (Package::whereNotNull('erp_id')->orderBy('id')->get() as $package) {
            $this->send($erp, $package, $remote, [
                'description' => $package->description,
                'web_published' => (bool) $package->is_published,
            ]);
        }

        $categories = $this->describeCategories($erp);
        $live->forget();

        $this->info("Fichas pasadas al sistema: {$this->sent}. Ya estaban allá: {$this->skipped}. Con error: {$this->failed}.");
        $this->info("Descripciones de categoría tomadas por el sistema: {$categories}.");

        if ($this->failed > 0) {
            $this->line('Vuelve a ejecutar el comando: lo que ya pasó no se repite.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $remote
     * @param  array<string, mixed>  $fields
     */
    private function send(ErpClient $erp, Model $local, array $remote, array $fields): void
    {
        $current = $remote[$local->erp_id] ?? null;

        // Ya se decidió allá (se pasó antes o se editó en el sistema): manda el sistema.
        if ($current === null || ($current['web_published'] ?? null) !== null) {
            $this->skipped++;

            return;
        }

        $fields = array_filter($fields, fn ($value) => $value !== null && $value !== '');
        $image = $local->image_path && Storage::disk('public')->exists($local->image_path)
            ? Storage::disk('public')->path($local->image_path)
            : null;

        try {
            $erp->sendWebDetails($local->erp_id, $fields, $image);
            $this->sent++;
            $this->line("  {$local->name}".($image ? ' (con foto)' : ''));
        } catch (ErpException $e) {
            $this->failed++;
            $this->warn("  {$local->name}: {$e->getMessage()}");
        }
    }

    private function describeCategories(ErpClient $erp): int
    {
        $taken = 0;
        $categories = InventoryCategory::whereNotNull('description')->get()
            ->concat(ServiceCategory::whereNotNull('description')->get())
            ->filter(fn (Model $category) => filled($category->description));

        foreach ($categories as $category) {
            try {
                $taken += $erp->describeCategory($category->name, $category->description)['updated'] ? 1 : 0;
            } catch (ErpException $e) {
                $this->warn("  Categoría {$category->name}: {$e->getMessage()}");
            }
        }

        return $taken;
    }
}
