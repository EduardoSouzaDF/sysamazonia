<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'telefone',
        'password',
        'is_judge',
        'is_organizer',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        // 'password' => 'hashed',
    ];

    // Relacionamentos
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withTimestamps()
            ->using(UserRole::class)
            ->where('roles.active', true);
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    // Métodos auxiliares
    public function hasRole($role)
    {

        if (is_string($role)) {
            return $this->roles->contains('name', $role);
        }

        return (bool) $role->intersect($this->roles)->count();
    }

    public function assignRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->attach($role);
    }

    public function removeRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }
        $this->roles()->detach($role);
    }

    public function hasAnyRole($roles)
    {
        return $this->roles()->whereIn('name', (array) $roles)->exists();
    }

    public function hasAllRoles($roles)
    {
        foreach ($roles as $role) {
            if (! $this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    public function extraData()
    {
        return $this->hasOne(UserExtraData::class);
    }

    public function isJudge(): bool
    {
        return $this->is_judge;
    }

    public function isOrganizer(): bool
    {
        return $this->is_organizer;
    }

    public function indicatorCategories()
    {
        return $this->belongsToMany(Category::class, 'indicators');
    }

    public function isIndicator()
    {
        return $this->indicatorCategories()->exists();
    }

    public function evaluatorCategories()
    {
        return $this->belongsToMany(Category::class, 'evaluators');
    }

    public function isEvaluator()
    {
        return $this->evaluatorCategories()->exists();
    }

    /**
     * Indications (opinions) que este usuário emitiu como júri/autor.
     *
     * Caminho: User -> indications (FK `user_id`).
     */
    public function indications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Indication::class);
    }

    /**
     * Coleção de indications (opinions) que este usuário emitiu.
     * Retorna coleção vazia se o usuário não possui indications.
     */
    public function getIndicationsList(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->indications()->get();
    }

    /**
     * Query builder das inscrições que o usuário pode avaliar como avaliador.
     *
     * Caminho: User -> categories (pivô `evaluators`) -> registrations.
     * Retorna query vazia quando o usuário não é avaliador de nenhuma categoria.
     */
    public function evaluatorRegistrations(): \Illuminate\Database\Eloquent\Builder
    {
        $categoryIds = $this->evaluatorCategories()->pluck('categories.id');

        return Registration::query()
            ->whereIn('category_id', $categoryIds);
    }

    /**
     * Coleção de inscrições para as quais o usuário é avaliador.
     * Retorna coleção vazia se não for avaliador.
     */
    public function getEvaluatorRegistrationsList(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->isEvaluator()) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        return $this->evaluatorRegistrations()->get();
    }
}
