<?php

namespace App\Services\Shop;

use App\Models\InventoryItem;
use App\Models\Service;
use Illuminate\Support\Collection;

/**
 * Carrito de la web, guardado en sesión.
 *
 * Solo guarda "tipo:id => cantidad". Precio, nombre y disponibilidad se leen
 * cada vez del catálogo de la tienda (con el sistema conectado, del sistema),
 * así un cambio de precio o un producto retirado de la web se refleja en el
 * carrito sin datos viejos.
 */
class Cart
{
    public function __construct(private readonly StoreCatalog $catalog) {}

    public const SERVICE = 'service';

    public const PRODUCT = 'product';

    private const SESSION_KEY = 'shop.cart';

    /** Tope de sesiones de un mismo servicio por pedido. */
    private const MAX_SERVICE_QUANTITY = 10;

    /** @return array<string,int> */
    public function quantities(): array
    {
        return session(self::SESSION_KEY, []);
    }

    public function count(): int
    {
        return array_sum($this->quantities());
    }

    public function quantityOf(string $key): int
    {
        return $this->quantities()[$key] ?? 0;
    }

    public function put(string $key, int $quantity): void
    {
        $items = $this->quantities();
        $items[$key] = $quantity;
        session([self::SESSION_KEY => $items]);
    }

    public function remove(string $key): void
    {
        $items = $this->quantities();
        unset($items[$key]);
        session([self::SESSION_KEY => $items]);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public static function key(string $type, int $id): string
    {
        return "{$type}:{$id}";
    }

    /** Servicio o producto que se puede comprar en línea: publicado y con precio. */
    public function find(string $type, int $id): Service|InventoryItem|null
    {
        return $this->purchasable($type, [$id])->first();
    }

    public function maxQuantity(Service|InventoryItem $model): int
    {
        return $model instanceof InventoryItem
            ? max(0, (int) floor((float) $model->stock))
            : self::MAX_SERVICE_QUANTITY;
    }

    /**
     * Con `$fresh`, precio y stock se le preguntan al sistema en el momento
     * (para armar el pedido); si no contesta, lanza ErpException.
     *
     * @return Collection<int,CartLine>
     */
    public function lines(bool $fresh = false): Collection
    {
        $ids = [self::SERVICE => [], self::PRODUCT => []];

        foreach (array_keys($this->quantities()) as $key) {
            [$type, $id] = array_pad(explode(':', $key, 2), 2, null);
            if (isset($ids[$type])) {
                $ids[$type][] = (int) $id;
            }
        }

        $models = [
            self::SERVICE => $ids[self::SERVICE] === [] ? collect() : $this->purchasable(self::SERVICE, $ids[self::SERVICE], $fresh),
            self::PRODUCT => $ids[self::PRODUCT] === [] ? collect() : $this->purchasable(self::PRODUCT, $ids[self::PRODUCT], $fresh),
        ];

        return collect($this->quantities())
            ->map(function (int $quantity, string $key) use ($models) {
                [$type, $id] = array_pad(explode(':', $key, 2), 2, null);
                $model = isset($models[$type]) ? $models[$type]->get((int) $id) : null;

                return $model
                    ? new CartLine($key, $type, $model, $quantity, $this->maxQuantity($model))
                    : null;
            })
            ->filter()
            ->values();
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Service|InventoryItem>
     */
    private function purchasable(string $type, array $ids, bool $fresh = false): Collection
    {
        return match ($type) {
            self::SERVICE => $this->catalog->purchasableServices($ids, $fresh),
            self::PRODUCT => $this->catalog->purchasableProducts($ids, $fresh),
            default => collect(),
        };
    }
}
