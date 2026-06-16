<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HabitWeeklyArchive extends Model
{
    use HasFactory;

    protected $fillable = [
        'habit_id',
        'user_id',
        'week_start',
        'week_end',
        'completed_dates',
        'perfect_week',
    ];

    protected $casts = [
        'completed_dates' => 'array',
        'perfect_week' => 'boolean',
    ];

    public function habit()
    {
        return $this->belongsTo(Habit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
