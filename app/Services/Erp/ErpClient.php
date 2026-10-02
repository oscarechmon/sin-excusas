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
        return $this->send(fn (PendingRequest $http) => $http->get('catalog', $ids === null ? [] : ['ids' => $ids]));
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
