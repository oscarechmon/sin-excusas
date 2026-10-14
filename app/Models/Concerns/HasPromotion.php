<?php

namespace App\Models\Concerns;

/**
 * Promoción de un elemento del catálogo web.
 *
 * Se decide en el sistema (ERP): si está en promoción y, si tiene, su precio de
 * promoción. LiveCatalog la pone con `useErpPromotion` y deja como precio del
 * ítem el que se cobra (el de promoción), así el carrito y el pago no cambian;
 * aquí queda el precio normal solo para mostrarlo tachado. Sin el sistema
 * conectado no hay promociones.
 */
trait HasPromotion
{
    private bool $onPromotion = false;

    private ?float $regularPrice = null;

    private ?float $promotionPrice = null;

    public function useErpPromotion(bool $onPromotion, ?float $regularPrice = null, ?float $promotionPrice = null): static
    {
        $this->onPromotion = $onPromotion;
        // Solo hay precio tachado si la promoción de verdad rebaja el precio.
        $discounted = $onPromotion && $promotionPrice !== null && $regularPrice !== null && $promotionPrice > 0 && $promotionPrice < $regularPrice;
        $this->regularPrice = $discounted ? $regularPrice : null;
        $this->promotionPrice = $discounted ? $promotionPrice : null;

        return $this;
    }

    public function onPromotion(): bool
    {
        return $this->onPromotion;
    }

    /** Precio normal, para mostrarlo tachado; null si la promoción no lo rebaja. */
    public function regularPrice(): ?float
    {
        return $this->regularPrice;
    }

    /** Cuánto se rebaja, en %; null si la promoción no tiene precio propio. */
    public function discountPercent(): ?int
    {
        if ($this->regularPrice === null || $this->promotionPrice === null) {
            return null;
        }

        return max(1, (int) round((1 - $this->promotionPrice / $this->regularPrice) * 100));
    }
}
