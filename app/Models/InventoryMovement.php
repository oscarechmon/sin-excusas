<?php

namespace App\Models;

use App\Enums\InventoryMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'type',
        'quantity',
        'stock_after',
        'unit_cost',
        'source_type',
        'source_id',
        'user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => InventoryMovementType::class,
            'quantity' => 'decimal:2',
            'stock_after' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Atención, venta o ajuste que originó el movimiento. */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
