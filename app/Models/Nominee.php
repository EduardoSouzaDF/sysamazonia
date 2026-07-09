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
        'protocol',
        'status',
    ];

    protected $casts = [
         'status' => 'integer',
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

     /**
     * Scope a query to only include registrations with a specific status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to search registrations by title.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $title
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchByTitle($query, $title)
    {
        return $query->where('name', 'like', "%{$title}%");
    }

     public function files()
    {
        return $this->hasMany(RegistrationFile::class);
    }

    public function statusName(){

        switch($this->status){

            case 1:
                return 'Inscrição Realizada';
                break;
            default:
                return '';
            break;

        }
    }
}
