<?php

namespace App\Services\Erp;

use App\Exceptions\ErpException;
use App\Models\Client;
use Illuminate\Support\Facades\Log;

/**
 * Los clientes se administran en el sistema. Los que nacen aquí (al crear una
 * cuenta en la tienda) se dan de alta allá y quedan enlazados (`erp_id`).
 *
 * Si el sistema no contesta, el cliente igual compra: sus pedidos llevan sus
 * datos y la sincronización vuelve a intentar el enlace.
 */
class ClientLinker
{
    public function __construct(private readonly ErpClient $erp) {}

    public function link(Client $client): ?int
    {
        if (! $this->erp->enabled() || $client->erp_id) {
            return $client->erp_id;
        }

        try {
            $result = $this->erp->syncCustomer([
                'web_id' => $client->id,
                'code' => $client->code,
                'name' => $client->full_name,
                'document_number' => $client->document_number,
                'email' => $client->email,
                'phone' => $client->phone ?? $client->whatsapp,
            ]);
        } catch (ErpException $e) {
            Log::warning('No se pudo enlazar el cliente con el sistema.', ['cliente' => $client->code, 'error' => $e->getMessage()]);

            return null;
        }

        if (! empty($result['id'])) {
            $client->forceFill(['erp_id' => (int) $result['id']])->save();
        }

        return $client->erp_id;
    }

    /**
     * Clientes de la tienda (con cuenta) que aún no están en el sistema.
     *
     * @return array{enlazados:int, pendientes:int}
     */
    public function linkPending(): array
    {
        $linked = 0;
        $pending = 0;

        Client::whereNull('erp_id')->whereHas('users')->each(function (Client $client) use (&$linked, &$pending) {
            $this->link($client) ? $linked++ : $pending++;
        });

        return ['enlazados' => $linked, 'pendientes' => $pending];
    }
}
