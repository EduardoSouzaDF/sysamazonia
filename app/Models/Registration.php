<?php

namespace App\Models;

use App\Enum\RegistrationStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'registrations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'candidate_id',
        'category_id',
        'title',
        'coautores',
        'resumo',
        'desenvolvimento',
        'objetivo',
        'conclusao',
        'status',
        'protocol',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'status' => RegistrationStatusEnum::class,
    ];

    /**
     * Get the candidate that owns the registration.
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Get the category that owns the registration.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scope a query to only include registrations with a specific status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to search registrations by title.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $title
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchByTitle($query, $title)
    {
        return $query->where('title', 'like', "%{$title}%");
    }

    public function files()
    {
        return $this->hasMany(RegistrationFile::class);
    }

    /**
     * Critérios de avaliação (EvaluationCriteria) da categoria desta inscrição.
     *
     * Caminho: Registration -> category -> evaluationCriteria.
     * Retorna query vazia caso a inscrição não tenha categoria.
     */
    public function evaluationCriteria(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            EvaluationCriterion::class,
            Category::class,
            'id',          // local key em categories referenciada por registrations.category_id
            'category_id', // FK em evaluation_criteria referenciando categories.id
            'category_id', // local key em registrations
            'id'           // local key em categories
        );
    }

    /**
     * Coleção de critérios de avaliação da categoria desta inscrição.
     */
    public function evaluationCriteriaList(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->category_id) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        return $this->evaluationCriteria()->get();
    }

    /**
     * Critérios ordenados por peso (decrescente por padrão).
     * Útil para renderizar formulários de avaliação na ordem de importância.
     */
    public function evaluationCriteriaByWeight(string $direction = 'desc'): \Illuminate\Database\Eloquent\Collection
    {
        return $this->evaluationCriteriaList()->sortBy('weight', SORT_REGULAR, $direction === 'desc');
    }

    /**
     * Pareceres (opinions) emitidos para esta inscrição.
     */
    public function opinions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Opinion::class);
    }

    /**
     * Pareceres já com seus scores (notas) carregados.
     * Útil para listagens e dashboards.
     */
    public function opinionsWithScores(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->opinions()
            ->with(['scores.evaluationCriterion', 'user'])
            ->latest()
            ->get();
    }

    /**
     * Média ponderada dos scores desta inscrição,
     * usando `weight` de cada `evaluation_criteria` como peso.
     *
     * Fórmula: Σ(valor × peso) / Σ(peso)
     *
     * Critérios com peso 0 ou nulo são desconsiderados.
     * Retorna null quando não houver notas válidas.
     */
    public function averageScore(): ?float
    {
        $scores = Score::query()
            ->whereIn('opinion_id', $this->opinions()->pluck('id'))
            ->with('evaluationCriterion:id,weight')
            ->get();

        if ($scores->isEmpty()) {
            return null;
        }

        $sumWeighted = 0.0;
        $sumWeights = 0.0;

        foreach ($scores as $score) {
            $weight = (float) ($score->evaluationCriterion?->weight ?? 0);

            if ($weight <= 0) {
                continue;
            }

            $sumWeighted += ((int) $score->valor) * $weight;
            $sumWeights += $weight;
        }

        if ($sumWeights === 0.0) {
            return null;
        }

        return round($sumWeighted / $sumWeights, 2);
    }

    /**
     * Soma dos pesos considerados no cálculo do averageScore().
     * Útil para exibir o denominador em telas de auditoria.
     */
    public function totalScoreWeight(): float
    {
        return (float) Score::query()
            ->whereIn('opinion_id', $this->opinions()->pluck('id'))
            ->join('evaluation_criteria', 'evaluation_criteria.id', '=', 'scores.evaluation_criterion_id')
            ->where('evaluation_criteria.weight', '>', 0)
            ->sum('evaluation_criteria.weight');
    }

    public function statusName(): string
    {
        return $this->status?->label() ?? RegistrationStatusEnum::Inscrito->label();
    }

    public static function getStatusArray(): array
    {
        return RegistrationStatusEnum::toArray();
    }
}
