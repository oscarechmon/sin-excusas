<?php

namespace App\Services\Erp;

use App\Exceptions\ErpException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Habla con la API de integración del sistema (ERP).
 *
 * Cada método devuelve el `data` de la respuesta. Si el sistema no contesta o
 * rechaza la operación lanza ErpException con un mensaje para el usuario.
 */
class ErpClient
{
    /** Con la integración apagada, el inventario se administra aquí como antes. */
    public function enabled(): bool
    {
        return (string) config('erp.url') !== '' && (string) config('erp.token') !== '';
    }

    /**
     * @param  list<int>|null  $ids  Solo esos ítems; null = todo el catálogo.
     * @return list<array<string, mixed>>
     */
    public function catalog(?array $ids = null): array
    {
        return $this->send(fn (PendingRequest $http) => $http->timeout((int) config('erp.read_timeout', 5))
            ->get('catalog', $ids === null ? [] : ['ids' => $ids]));
    }

    /** @return array<string, mixed> El ítem tal como lo describe el catálogo. */
    public function importProduct(array $payload): array
    {
        return $this->send(fn (PendingRequest $http) => $http->post('products', $payload));
    }

    /** @return array{sale: array<string, mixed>, stock: array<int|string, float>} */
    public function registerSale(array $payload): array
    {
        return $this->send(fn (PendingRequest $http) => $http->post('sales', $payload));
    }

    /** @return array{sale: array<string, mixed>, stock: array<int|string, float>} */
    public function cancelSale(string $reference, ?string $reason = null): array
    {
        return $this->send(fn (PendingRequest $http) => $http->post('sales/'.rawurlencode($reference).'/cancel', ['reason' => $reason]));
    }

    /**
     * @param  list<array{product_id:int, quantity:float}>  $items
     * @return array{stock: array<int|string, float>}
     */
    public function registerConsumption(string $reference, array $items, ?string $notes = null): array
    {
        return $this->send(fn (PendingRequest $http) => $http->post('consumptions', [
            'reference' => $reference,
            'items' => $items,
            'notes' => $notes,
        ]));
    }

    /**
     * Enlaza un cliente de la tienda con su ficha del sistema (la crea si no existe).
     *
     * @return array{id: int|null, code: string|null}
     */
    public function syncCustomer(array $payload): array
    {
        return $this->send(fn (PendingRequest $http) => $http->post('customers', $payload));
    }

    /**
     * Manda el pedido tal como está aquí. Si está pagado, el sistema registra
     * su venta (una sola vez) y devuelve el stock resultante.
     *
     * @return array{order: array<string, mixed>, sale: array<string, mixed>|null, stock: array<int|string, float>}
     */
    public function pushOrder(array $payload): array
    {
        return $this->send(fn (PendingRequest $http) => $http->post('orders', $payload));
    }

    /**
     * Estado y seguimiento hecho en el sistema de unos pedidos.
     *
     * @param  list<string>  $codes
     * @return list<array{code: string, status: string, history: list<array<string, mixed>>}>
     */
    public function orderStatuses(array $codes): array
    {
        return $this->send(fn (PendingRequest $http) => $http->get('orders/statuses', ['codes' => $codes]));
    }

    /**
     * Manda al sistema lo que la web mostraba de un ítem (descripción, si se
     * publicaba, duración y la foto) para que desde allá se administre.
     *
     * @param  array{description?: string, web_published?: bool, duration_minutes?: int}  $fields
     * @return array<string, mixed> El ítem tal como queda en el catálogo.
     */
    public function sendWebDetails(int $erpId, array $fields, ?string $imagePath = null): array
    {
        return $this->send(function (PendingRequest $http) use ($erpId, $fields, $imagePath) {
            $url = "products/{$erpId}/web";

            if ($imagePath === null) {
                return $http->post($url, $fields);
            }

            // Con archivo va como formulario: los valores, como texto.
            $form = array_map(fn ($value) => is_bool($value) ? ($value ? '1' : '0') : (string) $value, $fields);

            return $http->timeout(60)->attach('image', (string) file_get_contents($imagePath), basename($imagePath))->post($url, $form);
        });
    }

    /** Descripción de una categoría que la web tenía (el sistema la toma solo si no tiene una). */
    public function describeCategory(string $name, string $description): array
    {
        return $this->send(fn (PendingRequest $http) => $http->post('categories/describe', ['name' => $name, 'description' => $description]));
    }

    /**
     * Importa al sistema una tanda del historial de la web.
     *
     * @param  list<array<string, mixed>>  $records
     * @return array{links: array<int|string, int>, created: int, existing: int}
     */
    public function import(string $kind, array $records): array
    {
        return $this->send(fn (PendingRequest $http) => $http->timeout(120)->post("import/{$kind}", ['records' => $records]));
    }

    /** @param  callable(PendingRequest): Response  $call */
    private function send(callable $call): array
    {
        $http = Http::baseUrl(rtrim((string) config('erp.url'), '/').'/api/v1/integration')
            ->withHeaders(['X-Integration-Token' => (string) config('erp.token')])
            ->acceptJson()
            ->timeout((int) config('erp.timeout'));

        try {
            $response = $call($http);
        } catch (ConnectionException) {
            throw ErpException::unavailable();
        }

        if ($response->failed()) {
            throw ErpException::rejected(
                (string) ($response->json('message') ?: 'El sistema rechazó la operación.'),
                $response->status(),
            );
        }

        return (array) $response->json('data', []);
    }
}
