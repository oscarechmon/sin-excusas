<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public static function generateCode(): string
    {
        $count = static::count() + 1;
        return 'CLI-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }
}
