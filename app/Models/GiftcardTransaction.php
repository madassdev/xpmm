<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftcardTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'giftcard_id',
        'type',
        'currency',
        'card_type',
        'amount',
        'quantity',
        'payment_method',
        'status',
        'images',
        'notes',
        'amount_received',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'quantity' => 'integer',
        'amount_received' => 'integer',
        'images' => 'array',
        'completed_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';

    // Type constants
    public const TYPE_BUY = 'buy';
    public const TYPE_SELL = 'sell';

    /* ---------- Relationships ---------- */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function giftcard(): BelongsTo
    {
        return $this->belongsTo(Giftcard::class);
    }

    /* ---------- Scopes ---------- */
    public function scopeBuy($query)
    {
        return $query->where('type', self::TYPE_BUY);
    }

    public function scopeSell($query)
    {
        return $query->where('type', self::TYPE_SELL);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /* ---------- Helpers ---------- */
    public function getAmountNairaAttribute(): float
    {
        return $this->amount / 100;
    }

    public function getAmountReceivedNairaAttribute(): ?float
    {
        return $this->amount_received ? $this->amount_received / 100 : null;
    }
}
