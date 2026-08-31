<?php

namespace App\Models;

use App\Enums\CashSessionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'opening_amount',
        'expected_amount',
        'counted_amount',
        'difference',
        'status',
        'opened_at',
        'closed_at',
        'opened_by',
        'closed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_amount' => 'decimal:2',
            'expected_amount' => 'decimal:2',
            'counted_amount' => 'decimal:2',
            'difference' => 'decimal:2',
            'status' => CashSessionStatus::class,
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isOpen(): bool
    {
        return $this->status === CashSessionStatus::OPEN;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', CashSessionStatus::OPEN->value);
    }
}
