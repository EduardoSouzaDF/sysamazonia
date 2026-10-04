<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiSetting extends Model
{
    protected $attributes = ['local_type' => 'ollama'];

    protected $fillable = ['provider', 'model', 'base_url', 'local_type', 'api_key', 'technical_prompt', 'selection_prompt', 'technical_prompt_version', 'selection_prompt_version', 'evaluation_enabled', 'selection_enabled', 'technical_evaluator_id', 'selection_evaluator_id', 'connect_timeout', 'timeout', 'tries', 'knowledge_version', 'updated_by'];

    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return ['api_key' => 'encrypted', 'evaluation_enabled' => 'boolean', 'selection_enabled' => 'boolean', 'connect_timeout' => 'integer', 'timeout' => 'integer', 'tries' => 'integer'];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AiSettingVersion::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
