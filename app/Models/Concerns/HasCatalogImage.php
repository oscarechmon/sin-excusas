<?php

namespace App\Models\Concerns;

/**
 * Foto pública de un elemento del catálogo web.
 *
 * Con el sistema (ERP) conectado, la foto vive allá y se muestra desde allá
 * (LiveCatalog la pone con `useErpImage`). La guardada aquí en el disco
 * `public` queda para lo que el sistema aún no tiene.
 */
trait HasCatalogImage
{
    private ?string $erpImageUrl = null;

    public function useErpImage(?string $url): static
    {
        $this->erpImageUrl = $url;

        return $this;
    }

    public function imageUrl(): ?string
    {
        if ($this->erpImageUrl !== null) {
            return $this->erpImageUrl;
        }

        // asset() y no Storage::url(): respeta el host con el que se navega
        // en lugar de depender de APP_URL.
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }
}
