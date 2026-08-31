<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'full_name',
        'document_number',
        'birth_date',
        'gender',
        'phone',
        'whatsapp',
        'email',
        'district',
        'address',
        'how_knew',
        'observations',
        'allergies',
        'restrictions',
        'contraindications',
        'medications',
        'relevant_info',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'active' => 'boolean',
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(ClientPackage::class);
    }

    public static function generateCode(): string
    {
        // max(id) y no count(): si se elimina un cliente, count() reutilizaría
        // un código ya emitido.
        $next = static::max('id') + 1;

        return 'CLI-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
