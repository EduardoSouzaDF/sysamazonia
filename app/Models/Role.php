<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
     const ADMIN = 'admin';
     const JURADO = 'jurado';
     const LEITOR = 'leitor';


    protected $fillable = [
        'name',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    // Relacionamentos
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role')
                    ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('name', $name);
    }
}
