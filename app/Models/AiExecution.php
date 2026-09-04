<?php

namespace App\Models;

use App\Enum\AiExecutionStatus;
use App\Enum\AiExecutionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiExecution extends Model
{
    protected $fillable = [
        'registration_id', 'evaluator_id', 'opinion_id', 'indication_id', 'type',
        'status', 'correlation_id', 'provider', 'model', 'prompt_version', 'evaluation_configuration_hash',
        'rubric_version', 'knowledge_version', 'attempts', 'duration_ms',
        'response_metadata', 'error_code', 'error_message', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => AiExecutionType::class,
            'status' => AiExecutionStatus::class,
            'response_metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function opinion(): BelongsTo
    {
        return $this->belongsTo(Opinion::class);
    }

    public function indication(): BelongsTo
    {
        return $this->belongsTo(Indication::class);
    }
}
