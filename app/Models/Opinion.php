<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opinion extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'opinions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'registration_id',
    ];

    /**
     * Get the judge (user) that authored the opinion.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias semântico para o julgador (mantém a nomenclatura do banco antigo).
     */
    public function judge(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Get the registration that is being evaluated.
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * Get the scores (notas) given under this opinion.
     */
    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }
}
