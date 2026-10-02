<?php

namespace App\Http\Middleware;

use App\Exceptions\ErpException;
use App\Services\Erp\ErpClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Corta lo que, con el sistema (ERP) conectado, se administra allá: crear y
 * borrar productos, servicios y sus categorías, y mover stock a mano. Sin el
 * sistema, deja pasar todo como antes.
 */
class ManagedLocally
{
    public function __construct(private readonly ErpClient $erp) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->erp->enabled()) {
            throw ErpException::managedInErp('El catálogo de productos y servicios, con su stock,');
        }

        return $next($request);
    }
}
