<?php

namespace App\Models\Concerns;

/** Foto pública de un elemento del catálogo web, guardada en el disco `public`. */
trait HasCatalogImage
{
    public function imageUrl(): ?string
    {
        // asset() y no Storage::url(): respeta el host con el que se navega
        // en lugar de depender de APP_URL.
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }
}
