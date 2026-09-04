<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserExtraData extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'whatsapp',
        'area_atuacao',
        'indicado',
        'empresa',
        'cargo',
        'instagram',
        'facebook',
        'linkedin',
        'escolaridade',
        'estado',
        'cidade',
        'cep',
        'logradouro',
        'complemento',
        'unidade',
        'bairro',
    ];

    protected $casts = [
        'escolaridade' => 'array',
    ];

    /**
     * Get the user that owns the extra data.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
