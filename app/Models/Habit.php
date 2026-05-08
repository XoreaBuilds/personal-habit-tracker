<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Habit
 * 
 * Represents a single habit tracked by a user.
 * Stores completion history in a JSON array and calculates streaks dynamically.
 */
class Habit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * 
     * @var array<string>
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
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'completed_dates' => 'array',
    ];

    /**
     * Get the user that owns the habit.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the focus sessions associated with this habit (if any).
     */
    public function focusSessions()
    {
        return $this->hasMany(FocusSession::class);
    }

    /**
     * Add or remove a date from the completed_dates array.
     * Automatically recalculates and saves the current streak.
     * 
     * @param string $date YYYY-MM-DD format
     * @return void
     */
    public function toggleDate($date)
    {
        $completed = $this->completed_dates ?? [];
        
        if (in_array($date, $completed)) {
            // Remove date if it exists
            $completed = array_filter($completed, fn($d) => $d !== $date);
        } else {
            // Add date if it doesn't exist
            $completed[] = $date;
        }
        
        $this->completed_dates = array_values($completed);
        $this->streak = $this->calculateStreak();
        $this->save();
    }

    /**
     * Logic to calculate the current consecutive day streak.
     * Checks backwards from today or yesterday to see how many
     * consecutive days the habit was marked as completed.
     * 
     * @return int
     */
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

        // Start checking from today if completed, otherwise start from yesterday
        $checkDate = $dates->contains($today) ? now() : now()->subDay();
        
        while ($dates->contains($checkDate->toDateString())) {
            $streak++;
            $checkDate->subDay();
        }

        return $streak;
    }
}
