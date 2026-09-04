<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'modality_id',
        'title',
        'acronym',
        'description',
        'is_honorific',
        'nominations_count',
        'evaluations_count',
        'recipients_count',
        'submissions_per_candidate',
        'judging_start',
        'judging_end',
        'is_open_for_submissions',
    ];

    protected $casts = [
        'is_honorific' => 'boolean',
        'nominations_count' => 'integer',
        'evaluations_count' => 'integer',
        'recipients_count' => 'integer',
        'submissions_per_candidate' => 'integer',
        'judging_start' => 'date',
        'judging_end' => 'date',
        'is_open_for_submissions' => 'boolean',
    ];

    // RELATIONSHIPS
    public function modality(): BelongsTo
    {
        return $this->belongsTo(Modality::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function evaluationCriteria()
    {
        return $this->hasMany(EvaluationCriterion::class);
    }

    public function indicators()
    {
        return $this->belongsToMany(User::class, 'indicators');
    }

    public function evaluators()
    {
        return $this->belongsToMany(User::class, 'evaluators');
    }

    /**
     * Get the registrations for the category.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function nominees(): HasMany
    {
        return $this->hasMany(Nominee::class);
    }
}
