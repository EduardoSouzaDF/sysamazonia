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
}
