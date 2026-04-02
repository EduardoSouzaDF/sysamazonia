<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'description',
        'document_type',
    ];

    /**
     * Obter a inscrição relacionada a este arquivo
     */
    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }
}
