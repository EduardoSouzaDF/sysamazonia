<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
        'is_organizing',
        'is_evaluating',
        'is_nominating',
        'is_judging',
        'is_active',
    ];

    protected $casts = [
        'is_organizing' => 'boolean',
        'is_evaluating' => 'boolean',
        'is_nominating' => 'boolean',
        'is_judging' => 'boolean',
        'is_active' => 'boolean',
    ];

    // RELATIONSHIPS
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
