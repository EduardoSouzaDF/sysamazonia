<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'candidates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nome',
        'cpf',
        'dt_nascimento',
        'rg',
        'rg_expeditor',
        'rg_uf',
        'sexo',
        'cep',
        'ufendereco',
        'cidade',
        'endereco',
        'numero',
        'complemento',
        'ddd',
        'celular',
        'whatsapp',
        'email',
        'instituicao',
        'escolaridade',
        'instagram',
        'facebook',
        'outra_rede_social',
        'resumo_curricular',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'dt_nascimento' => 'date',
        'whatsapp' => 'boolean',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Get the candidate's age.
     *
     * @return int
     */
    public function getAgeAttribute()
    {
        return $this->dt_nascimento->diffInYears(now());
    }

    /**
     * Scope a query to only include candidates with a specific education level.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $level
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEscolaridade($query, $level)
    {
        return $query->where('escolaridade', $level);
    }

    /**
     * Scope a query to search candidates by name.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchByName($query, $name)
    {
        return $query->where('nome', 'like', "%{$name}%");
    }

    /**
     * Get the registrations for the candidate.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function nominees(): HasMany
    {
        return $this->hasMany(Nominee::class);
    }
}
