<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActionToken extends Model
{
    protected $fillable = [
        'protocol',
        'token',
        'action',
        'activated_at',
        'expires_at',
        'consumed_at'
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];


    /**
     * Escopo para buscar apenas tokens válidos
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
                     ->whereNull('consumed_at');
    }

    /**
     * Verifica se o token já foi ativado pelo e-mail
     */
    public function isActivated(): bool
    {
        return !is_null($this->activated_at);
    }

    /**
     * Verifica se o token ainda está no prazo de validade
     */
    public function isValid(): bool
    {
        return $this->expires_at->isFuture() && is_null($this->consumed_at);
    }

    /**
     * Ativa o token e renova a expiração para mais 2 horas (conforme seu requisito)
     */
    public function activate(): bool
    {
        return $this->update([
            'activated_at' => now()
        ]);
    }

    /**
     * Marca o token como usado (útil para o Delete)
     */
    public function consume(): bool
    {
        return $this->update(['consumed_at' => now()]);
    }

    /**
     * Factory para gerar o token automaticamente na criação
     */
    protected static function booted()
    {
        static::creating(function ($actionToken) {
            $actionToken->token = Str::random(64);
        });
    }
}
