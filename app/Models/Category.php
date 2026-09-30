<?php

namespace App\Models;

use App\Enum\EvaluationMode;
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
        'evaluation_mode',
        'human_evaluations_required',
        'indication_mode',
        'human_indications_required',
        'recipients_count',
        'submissions_per_candidate',
        'judging_start',
        'judging_end',
        'is_open_for_submissions',
    ];

    protected $casts = [
        'is_honorific' => 'boolean',
        'evaluation_mode' => EvaluationMode::class,
        'human_evaluations_required' => 'integer',
        'indication_mode' => EvaluationMode::class,
        'human_indications_required' => 'integer',
        'recipients_count' => 'integer',
        'submissions_per_candidate' => 'integer',
        'judging_start' => 'date',
        'judging_end' => 'date',
        'is_open_for_submissions' => 'boolean',
    ];

    public function requiredAiEvaluations(): int
    {
        return (int) $this->allowsAiEvaluation();
    }

    public function requiredAiIndications(): int
    {
        return (int) $this->allowsAiIndication();
    }

    public function requiredEvaluations(): int
    {
        return ($this->allowsHumanEvaluation() ? (int) $this->human_evaluations_required : 0)
            + $this->requiredAiEvaluations();
    }

    public function requiredIndications(): int
    {
        return ($this->allowsHumanIndication() ? (int) $this->human_indications_required : 0)
            + $this->requiredAiIndications();
    }

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

    public function allowsHumanEvaluation(): bool
    {
        return ! $this->is_honorific && ($this->evaluation_mode?->allowsHuman() ?? false);
    }

    public function allowsAiEvaluation(): bool
    {
        return ! $this->is_honorific && ($this->evaluation_mode?->allowsAi() ?? false);
    }

    public function allowsHumanIndication(): bool
    {
        return ! $this->is_honorific && ($this->indication_mode?->allowsHuman() ?? false);
    }

    public function allowsAiIndication(): bool
    {
        return ! $this->is_honorific && ($this->indication_mode?->allowsAi() ?? false);
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
