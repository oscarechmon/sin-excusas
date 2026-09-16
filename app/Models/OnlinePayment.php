<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Respuesta de la pasarela para un pedido, aprobada o rechazada. */
class OnlinePayment extends Model
{
    protected $fillable = ['online_order_id', 'gateway', 'status', 'amount', 'transaction_id', 'payload'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payload' => 'array',
        ];
    }
}
