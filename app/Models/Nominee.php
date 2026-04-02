<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nominee extends Model
{
    use HasFactory;

    protected $table = 'nominees';

    protected $fillable = [
        'candidate_id',
        'category_id',
        'name',
        'state',
        'contact_data',
        'presentation',
        'activities',
        'justification',
    ];

    protected $casts = [
        //
    ];


    protected $dates = [
        'created_at',
        'updated_at',
    ];


    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }


    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
