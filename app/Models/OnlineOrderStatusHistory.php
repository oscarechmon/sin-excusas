<?php

namespace App\Models;

use App\Enums\OnlineOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineOrderStatusHistory extends Model
{
    protected $fillable = ['online_order_id', 'status', 'note', 'internal', 'from_erp', 'user_id', 'actor_name', 'created_at'];

    protected function casts(): array
    {
        return [
            'status' => OnlineOrderStatus::class,
            'internal' => 'boolean',
            'from_erp' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
