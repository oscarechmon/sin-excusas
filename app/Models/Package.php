<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'total_sessions',
        'validity_days',
        'active',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'total_sessions' => 'integer',
            'validity_days' => 'integer',
            'active' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'package_services')->withTimestamps();
    }

    public function clientPackages(): HasMany
    {
        return $this->hasMany(ClientPackage::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /** Visible en la web: publicado y todavía activo en el ERP. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->where('active', true);
    }
}
