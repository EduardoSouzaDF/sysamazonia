<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationCriterion extends Model
{
    use HasFactory;

    protected $table = 'evaluation_criteria';

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'weight',
        'min_score',
        'max_score',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'min_score' => 'decimal:2',
        'max_score' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
