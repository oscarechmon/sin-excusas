<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Cuenta de un cliente en la tienda web (guard `customer`).
 *
 * No es un usuario del ERP: no tiene roles ni acceso al panel. Se vincula a
 * la ficha `clients` para que sus compras aparezcan en el centro.
 *
 * `google_id`, `email_verified_at` y los campos del código no están en
 * $fillable a propósito: solo los asignan el flujo de Google y el de
 * verificación, nunca datos que llegan de un formulario.
 */
class ClientUser extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['client_id', 'name', 'email', 'phone', 'document_number', 'password'];

    protected $hidden = ['password', 'remember_token', 'verification_code'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'verification_code_expires_at' => 'datetime',
            'verification_sent_at' => 'datetime',
            'verification_attempts' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(OnlineOrder::class);
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /** La cuenta se creó con Google y no tiene contraseña propia. */
    public function usesOnlyGoogle(): bool
    {
        return $this->google_id !== null && $this->password === null;
    }
}
