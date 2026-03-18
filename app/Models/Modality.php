<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modality extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'candidacy_limit_per_modality',
        'is_active',
        'edition_id',
    ];

    protected $casts = [
        'candidacy_limit_per_modality' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // RELATIONSHIPS
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }
}
