<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OnlineOrderItem extends Model
{
    public const TYPE_SERVICE = 'service';

    public const TYPE_PRODUCT = 'product';

    protected $fillable = [
        'online_order_id',
        'itemable_type',
        'itemable_id',
        'item_type',
        'name',
        'unit_price',
        'quantity',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'subtotal' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OnlineOrder::class, 'online_order_id');
    }

    /** Servicio o producto de origen. */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }
}
