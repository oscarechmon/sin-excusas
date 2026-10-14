<?php

namespace App\Models;

use App\Models\Concerns\HasCatalogImage;
use App\Models\Concerns\HasPromotion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasCatalogImage, HasFactory, HasPromotion;

    protected $fillable = [
        'name', 'category_id', 'price', 'duration_minutes', 'description', 'image_path', 'active', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'active' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_service', 'service_id', 'employee_id');
    }

    /** Insumos configurados con su cantidad referencial (§20). */
    public function supplies(): BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'service_supplies')
            ->withPivot('default_quantity')
            ->withTimestamps();
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_services')->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** Ficha del servicio en la web pública. */
    public function webUrl(): string
    {
        return route('site.service', [$this->id, Str::slug($this->name)]);
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
