<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Habit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'frequency',
        'streak',
        'completed_dates',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'completed_dates' => 'array',
    ];

    /**
     * Relationship to the user (optional).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
