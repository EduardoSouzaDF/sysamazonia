<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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
        'voting_closed_at' => 'datetime',
    ];

    // RELATIONSHIPS
    public function modalities(): HasMany
    {
        return $this->hasMany(Modality::class);
    }

    public function categories(): HasManyThrough
    {
        return $this->hasManyThrough(Category::class, Modality::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_registration_active', true);
    }

    /**
     * Edição ativa (RDD-01) e em julgamento (RDD-03: data atual > `judgment_date`).
     */
    public function isInJudging(): bool
    {
        return $this->is_registration_active
            && $this->judgment_date !== null
            && $this->judgment_date->lt(today());
    }

    /**
     * Votação encerrada pelo admin em "Finalizar votação" (spec 0004 / RF-08).
     */
    public function isVotingClosed(): bool
    {
        return $this->voting_closed_at !== null;
    }
}
