<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiSettingVersion extends Model
{
    protected $fillable = ['ai_setting_id', 'provider', 'model', 'technical_prompt', 'selection_prompt', 'technical_prompt_version', 'selection_prompt_version', 'knowledge_version', 'changed_by'];

    public function setting(): BelongsTo
    {
        return $this->belongsTo(AiSetting::class, 'ai_setting_id');
    }
}
