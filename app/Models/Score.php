<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'scores';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'opinion_id',
        'evaluation_criterion_id',
        'valor',
        'descricao',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'valor' => 'integer',
    ];

    /**
     * Get the opinion (parecer) that owns this score.
     */
    public function opinion(): BelongsTo
    {
        return $this->belongsTo(Opinion::class);
    }

    /**
     * Get the evaluation criterion associated with this score.
     */
    public function evaluationCriterion(): BelongsTo
    {
        return $this->belongsTo(EvaluationCriterion::class);
    }
}
