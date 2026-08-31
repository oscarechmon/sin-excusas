<?php

namespace App\Models;

use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'attendance_id',
        'sale_id',
        'service_id',
        'base_amount',
        'type',
        'value',
        'amount',
        'status',
        'generated_at',
        'paid_at',
        'paid_by',
    ];

    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:2',
            'type' => CommissionType::class,
            'value' => 'decimal:2',
            'amount' => 'decimal:2',
            'status' => CommissionStatus::class,
            'generated_at' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', CommissionStatus::PENDING->value);
    }
}
