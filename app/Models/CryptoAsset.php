<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CryptoAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'symbol',
        'rate',
        'wallet_address',
        'sale_type',
        'charges',
        'logo_path',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:8',
        'charges' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'logo_url',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }
}

