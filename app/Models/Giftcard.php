<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Giftcard extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rate',
        'available_regions',
        'available_values',
        'image_path',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'available_regions' => 'array',
        'available_values' => 'array',
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    /* ---------- Relationships ---------- */
    public function transactions(): HasMany
    {
        return $this->hasMany(GiftcardTransaction::class);
    }
}

