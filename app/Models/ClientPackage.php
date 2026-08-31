<?php

namespace App\Models;

use App\Enums\PackageStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'package_id',
        'package_name',
        'price',
        'total_sessions',
        'used_sessions',
        'purchased_at',
        'expires_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'total_sessions' => 'integer',
            'used_sessions' => 'integer',
            'purchased_at' => 'date',
            'expires_at' => 'date',
            'status' => PackageStatus::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClientPackageSession::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function remainingSessions(): int
    {
        return max(0, $this->total_sessions - $this->used_sessions);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Un paquete solo puede consumirse si está activo, vigente y con saldo. */
    public function canConsumeSession(): bool
    {
        return $this->status === PackageStatus::ACTIVE
            && $this->remainingSessions() > 0
            && ! $this->isExpired();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PackageStatus::ACTIVE->value);
    }
}
