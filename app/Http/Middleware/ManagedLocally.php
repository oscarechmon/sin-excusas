<?php

namespace App\Http\Middleware;

use App\Exceptions\ErpException;
use App\Services\Erp\ErpClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Corta lo que, con el sistema (ERP) conectado, se administra allá. Sin el
 * sistema, deja pasar todo como antes.
 *
 * Al conectarlo, este panel queda para la web: contenido, datos del sitio,
 * imágenes y descripciones del catálogo, y qué se publica. La operación
 * (clientes, agenda, atenciones, ventas, caja, paquetes, personal,
 * comisiones y pedidos online) se hace en el sistema.
 *
 * Uso: `erp.local` (catálogo y stock) o `erp.local:clients`, etc.
 */
class ManagedLocally
{
    private const WHAT = [
        'catalog' => 'El catálogo de productos y servicios, con su stock, se administra',
        'clients' => 'Los clientes se administran',
        'agenda' => 'La agenda se administra',
        'attendances' => 'Las atenciones se registran',
        'sales' => 'Las ventas y sus cobros se registran',
        'cash' => 'La caja se administra',
        'packages' => 'Los paquetes se administran',
        'staff' => 'El personal se administra',
        'commissions' => 'Las comisiones se administran',
        'orders' => 'El seguimiento de los pedidos online se hace',
    ];

    public function __construct(private readonly ErpClient $erp) {}

    public function handle(Request $request, Closure $next, string $area = 'catalog'): Response
    {
        if ($this->erp->enabled()) {
            throw ErpException::movedToErp(self::WHAT[$area] ?? self::WHAT['catalog']);
        }

        return $next($request);
    }
}
