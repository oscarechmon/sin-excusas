<?php

namespace App\Models\Concerns;

/**
 * Fotos públicas de un elemento del catálogo web.
 *
 * Con el sistema (ERP) conectado, la foto es solo la del sistema y se muestra
 * desde allá (LiveCatalog la pone con `useErpImage`): si allá no tiene foto, no
 * hay foto, aunque aquí quede una de antes (esas se pasan al sistema con
 * `php artisan erp:fichas`, que también corre en cada despliegue). La guardada
 * aquí en el disco `public` solo se usa sin el sistema conectado.
 *
 * Las fotos adicionales de la ficha también vienen del sistema; aquí no hay.
 */
trait HasCatalogImage
{
    private ?string $erpImageUrl = null;

    /** @var list<string> */
    private array $erpGallery = [];

    private bool $fromErp = false;

    /** @param  array<int, mixed>  $gallery  Fotos adicionales, en orden. */
    public function useErpImage(?string $url, array $gallery = []): static
    {
        $this->erpImageUrl = filled($url) ? $url : null;
        $this->erpGallery = array_values(array_filter($gallery, fn ($photo) => is_string($photo) && $photo !== ''));
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

    /**
     * Todas las fotos de la ficha: la principal primero y después las
     * adicionales. Sin principal, la primera adicional hace de principal.
     *
     * @return list<string>
     */
    public function photoUrls(): array
    {
        return array_values(array_unique(array_filter([$this->imageUrl(), ...$this->erpGallery])));
    }
}
