<?php

namespace App\Models;

use App\Enum\RegistrationStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Registration extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::updated(function (Registration $registration): void {
            if ($registration->wasChanged('status')) {
                $previousStatus = $registration->getRawOriginal('status');
                $currentStatus = $registration->status;
                event(new \App\Events\RegistrationStatusChanged(
                    $registration->id,
                    $previousStatus instanceof RegistrationStatusEnum ? $previousStatus->value : (int) $previousStatus,
                    $currentStatus instanceof RegistrationStatusEnum ? $currentStatus->value : (int) $currentStatus,
                ));
            }
        });
    }

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
        'evaluation_avg',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'evaluation_avg' => 'integer',
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
     * Indicações (indications) emitidos para esta inscrição.
     */
    public function indications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Indication::class);
    }

    public function aiExecutions(): HasMany
    {
        return $this->hasMany(AiExecution::class);
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

    public function statusName(): string
    {

        switch ((string) $this->status) {

            case '1':
                return RegistrationStatusEnum::Inscrito->label();
                break;
            case '2':
                return RegistrationStatusEnum::Rejeitado->label();
                break;
            case '3':
                return RegistrationStatusEnum::Habilitado->label();
                break;
            case '4':
                return RegistrationStatusEnum::Avaliado->label();
                break;
            case '5':
                return RegistrationStatusEnum::Agraciado->label();
                break;
            default:
                return 'Desconhecido';

        }
    }

    public function getTextEvaluationAvg(): string
    {
        if ($this->evaluation_avg === null) {
            return 'Sem Avaliação';
        }

        if ($this->getEvaluationAvgPercentage() <= 30) {
            return 'Não Recomendado';
        }

        if ($this->getEvaluationAvgPercentage() <= 40) {
            return 'Meritório';
        }

        if ($this->getEvaluationAvgPercentage() <= 50) {
            return 'Recomendado';
        }

        return '';
    }

    public function getEvaluationAvgPercentage(): ?float
    {
        if ($this->evaluation_avg === null) {
            return null;
        }

        return round($this->evaluation_avg / 2, 0);
    }

    public static function getStatusArray(): array
    {
        return RegistrationStatusEnum::toArray();
    }

    public function updateEvaluationsAvg(): void
    {
        app(\App\Services\EvaluationCompletionService::class)->recalculate($this);
    }
}
