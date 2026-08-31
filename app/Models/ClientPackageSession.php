<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPackageSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_package_id',
        'attendance_id',
        'session_number',
        'consumed_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'session_number' => 'integer',
            'consumed_at' => 'datetime',
        ];
    }

    public function clientPackage(): BelongsTo
    {
        return $this->belongsTo(ClientPackage::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
