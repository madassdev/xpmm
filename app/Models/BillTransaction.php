<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillTransaction extends Model
{
    // Normalize status values and keep them in one place
    public const S_PENDING = 'PENDING';
    public const S_SUCCESS = 'SUCCESS';
    public const S_FAILED  = 'FAILED';
    public const S_RETRIED = 'RETRIED';
    public const S_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'reference','service','product','network','phone','account','plan_id','ported',
        'amount','amount_paid','amount_discount','provider','provider_txn_id',
        'callback_url','status','paid_at','failed_at',
        'request_payload','provider_response','webhook_payload',
    ];

    protected $casts = [
        'ported'            => 'boolean',
        'amount'            => 'integer',
        'amount_paid'       => 'integer',
        'amount_discount'   => 'integer',
        'paid_at'           => 'datetime',
        'failed_at'         => 'datetime',
        'request_payload'   => 'array',
        'provider_response' => 'array',
        'webhook_payload'   => 'array',
    ];

    public function electricityTokens() { return $this->hasMany(\App\Models\ElectricityToken::class); }


    /* ---------- Scopes ---------- */
    public function scopeRef($q, string $ref)     { return $q->where('reference', $ref); }
    public function scopeService($q, string $s)   { return $q->where('service', $s); }
    public function scopeSuccess($q)              { return $q->where('status', self::S_SUCCESS); }
    public function scopePending($q)              { return $q->where('status', self::S_PENDING); }

    /* ---------- Helpers ---------- */
    // Treat monetary values as kobo; provide naira accessors if you want
    public function getAmountNairaAttribute(): float
    {
        return $this->amount / 100;
    }

    public function markSuccess(array $provider = []): void
    {
        $this->update([
            'status' => self::S_SUCCESS,
            'paid_at' => now(),
            'provider_response' => $provider ?: $this->provider_response,
        ]);
    }

    public function markFailed(array $provider = []): void
    {
        $this->update([
            'status' => self::S_FAILED,
            'failed_at' => now(),
            'provider_response' => $provider ?: $this->provider_response,
        ]);
    }
}
