<?php

namespace App\Models\Concerns;

/**
 * Foto pública de un elemento del catálogo web.
 *
 * Con el sistema (ERP) conectado, la foto es solo la del sistema y se muestra
 * desde allá (LiveCatalog la pone con `useErpImage`): si allá no tiene foto, no
 * hay foto, aunque aquí quede una de antes (esas se pasan al sistema con
 * `php artisan erp:fichas`, que también corre en cada despliegue). La guardada
 * aquí en el disco `public` solo se usa sin el sistema conectado.
 */
trait HasCatalogImage
{
    private ?string $erpImageUrl = null;

    private bool $fromErp = false;

    public function useErpImage(?string $url): static
    {
        $this->erpImageUrl = filled($url) ? $url : null;
        $this->fromErp = true;

        return $this;
    }

    public function imageUrl(): ?string
    {
        if ($this->fromErp) {
            return $this->erpImageUrl;
        }

        // asset() y no Storage::url(): respeta el host con el que se navega
        // en lugar de depender de APP_URL.
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }
}
