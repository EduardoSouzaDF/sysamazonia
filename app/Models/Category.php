<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    // SELEÇÕES DO JULGADOR / COTA (spec 0003)

    /**
     * Quantidade de seleções (JudgeSelection) já registradas pelo julgador
     * para inscrições (Registration | Nominee) desta categoria.
     */
    public function judgeSelectionsBy(User $user): int
    {
        $categoryId = $this->getKey();

        return JudgeSelection::query()
            ->where('user_id', $user->getKey())
            ->whereHasMorph(
                'inscription',
                [Registration::class, Nominee::class],
                fn (Builder $query) => $query->where('category_id', $categoryId)
            )
            ->count();
    }

    /**
     * Saldo da cota (`recipients_count`) que o julgador ainda pode
     * selecionar nesta categoria.
     */
    public function remainingQuotaFor(User $user): int
    {
        return max(0, (int) ($this->recipients_count ?? 0) - $this->judgeSelectionsBy($user));
    }

    /**
     * Informa se o julgador já completou a cota desta categoria.
     */
    public function isFullyJudgedBy(User $user): bool
    {
        $quota = (int) ($this->recipients_count ?? 0);

        return $quota > 0 && $this->remainingQuotaFor($user) === 0;
    }
}
