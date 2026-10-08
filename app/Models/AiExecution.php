<?php

namespace App\Models;

use App\Enum\AiExecutionStatus;
use App\Enum\AiExecutionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiExecution extends Model
{
    protected $fillable = [
        'registration_id', 'evaluator_id', 'ai_setting_version_id', 'opinion_id', 'indication_id', 'type',
        'status', 'correlation_id', 'provider', 'model', 'prompt_version', 'evaluation_configuration_hash',
        'rubric_version', 'knowledge_version', 'attempts', 'duration_ms', 'service_http_status',
        'retry_round', 'parent_execution_id', 'requested_by', 'requested_at', 'superseded_at', 'retry_configuration', 'retry_reason',
        'response_metadata', 'error_code', 'error_message', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => AiExecutionType::class,
            'status' => AiExecutionStatus::class,
            'response_metadata' => 'array',
            'retry_round' => 'integer',
            'requested_at' => 'datetime',
            'superseded_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // Audit links do not copy provider credentials into execution history.
    public function parentExecution(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_execution_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function settingVersion(): BelongsTo
    {
        return $this->belongsTo(AiSettingVersion::class, 'ai_setting_version_id');
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
