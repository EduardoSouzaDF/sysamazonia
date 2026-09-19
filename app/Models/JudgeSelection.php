<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class JudgeSelection extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'inscription_type',
        'inscription_id',
    ];

    /**
     * Julgador (usuário com `is_judge`) que realizou a seleção (spec 0003).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Inscrição selecionada (RDD-02): união de `Registration` e `Nominee`,
     * resolvida pelo morph map (`registration` | `nominee`) do AppServiceProvider.
     */
    public function inscription(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include selections of a specific user.
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
