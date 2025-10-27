<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    protected $fillable = [
        'service',      // data, cable, electricity, etc.
        'provider',     // MTN, Airtel, DStv, etc.
        'code',         // plan code from provider
        'name',         // display name
        'price',        // price in kobo/cents
        'meta',         // additional metadata from provider
        'synced_at',    // when last synced from API
    ];

    protected $casts = [
        'price' => 'integer',
        'meta' => 'array',
        'synced_at' => 'datetime',
    ];

    /* ---------- Scopes ---------- */
    public function scopeService($q, string $service)
    {
        return $q->where('service', $service);
    }

    public function scopeProvider($q, string $provider)
    {
        return $q->where('provider', $provider);
    }

    public function scopeCode($q, string $code)
    {
        return $q->where('code', $code);
    }

    public function scopeActive($q)
    {
        // Plans synced within last 24 hours are considered active
        return $q->where('synced_at', '>=', now()->subDay());
    }

    /* ---------- Helpers ---------- */
    public function getPriceNairaAttribute(): float
    {
        return $this->price / 100;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'price' => $this->price,
            'meta' => $this->meta,
        ];
    }
}

