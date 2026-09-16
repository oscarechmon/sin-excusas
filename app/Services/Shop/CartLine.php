<?php

namespace App\Services\Shop;

use App\Models\InventoryItem;
use App\Models\Service;

/** Una línea del carrito, con el servicio o producto ya resuelto desde la BD. */
final readonly class CartLine
{
    public function __construct(
        public string $key,
        public string $type,
        public Service|InventoryItem $model,
        public int $quantity,
        public int $maxQuantity,
    ) {}

    public function name(): string
    {
        return $this->model->name;
    }

    /** Siempre el precio vigente: el carrito nunca guarda precios. */
    public function unitPrice(): float
    {
        return (float) ($this->isProduct() ? $this->model->sale_price : $this->model->price);
    }

    public function subtotal(): float
    {
        return round($this->unitPrice() * $this->quantity, 2);
    }

    public function isProduct(): bool
    {
        return $this->type === Cart::PRODUCT;
    }

    public function exceedsAvailable(): bool
    {
        return $this->quantity > $this->maxQuantity;
    }
}
