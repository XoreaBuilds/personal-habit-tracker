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

    public function focusSessions()
    {
        return $this->hasMany(FocusSession::class);
    }

    public function toggleDate($date)
    {
        $completed = $this->completed_dates ?? [];
        if (in_array($date, $completed)) {
            $completed = array_filter($completed, fn($d) => $d !== $date);
        } else {
            $completed[] = $date;
        }
        $this->completed_dates = array_values($completed);
        $this->streak = $this->calculateStreak();
        $this->save();
    }

    public function calculateStreak(): int
    {
        if (!$this->completed_dates || count($this->completed_dates) === 0) {
            return 0;
        }

        $dates = collect($this->completed_dates);
        $streak = 0;
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // If today is not completed and yesterday is not completed, streak is 0
        if (!$dates->contains($today) && !$dates->contains($yesterday)) {
            return 0;
        }

        $checkDate = $dates->contains($today) ? now() : now()->subDay();
        
        while ($dates->contains($checkDate->toDateString())) {
            $streak++;
            $checkDate->subDay();
        }

        return $streak;
    }
}
