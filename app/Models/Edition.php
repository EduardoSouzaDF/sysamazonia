<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Edition extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'regulation',
        'regulation_file_path',
        'registration_start',
        'registration_end',
        'grant_date',
        'judgment_date',
        'is_registration_active',
        'applications_per_candidate',
    ];

    protected $casts = [
        'registration_start' => 'date',
        'registration_end' => 'date',
        'grant_date' => 'date',
        'judgment_date' => 'date',
        'is_registration_active' => 'boolean',
        'applications_per_candidate' => 'integer',
    ];

    // RELATIONSHIPS
    public function modalities(): HasMany
    {
        return $this->hasMany(Modality::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_registration_active', true);
    }
}
