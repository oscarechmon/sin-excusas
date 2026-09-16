<?php

namespace App\Models;

use App\Enums\FulfillmentType;
use App\Enums\OnlineOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineOrder extends Model
{
    protected $fillable = [
        'code',
        'client_user_id',
        'client_id',
        'status',
        'fulfillment',
        'recipient_name',
        'phone',
        'address',
        'district',
        'reference',
        'notes',
        'subtotal',
        'delivery_fee',
        'total',
        'paid_at',
        'payment_reference',
    ];

    protected function casts(): array
    {
        return [
            'status' => OnlineOrderStatus::class,
            'fulfillment' => FulfillmentType::class,
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(ClientUser::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OnlineOrderItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OnlineOrderStatusHistory::class)->oldest('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OnlinePayment::class)->latest('id');
    }

    /** Deja constancia en el seguimiento. */
    public function recordStatus(
        OnlineOrderStatus $status,
        ?string $note = null,
        ?int $userId = null,
        bool $internal = false,
    ): void {
        $this->histories()->create([
            'status' => $status,
            'note' => $note,
            'user_id' => $userId,
            'internal' => $internal,
        ]);
    }

    /** Las pasarelas trabajan con enteros en céntimos. */
    public function totalInCents(): int
    {
        return (int) round((float) $this->total * 100);
    }

    public static function generateCode(): string
    {
        $next = static::max('id') + 1;

        return 'W-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
