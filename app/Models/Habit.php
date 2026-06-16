<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\FocusSession;
use Carbon\Carbon;

/**
 * Class Habit
 * 
 * Represents a single habit tracked by a user.
 * Stores completion history in a JSON array and calculates streaks dynamically.
 * 
 * Weekly Cycle Logic:
 * - Each habit runs on a Mon–Sun weekly cycle
 * - `week_start` anchors the current cycle
 * - At the start of a new week, the system checks if the frequency target was met
 * - If met → streak increments; if missed → streak resets to 0
 * - completed_dates are pruned to only the current week
 * - Past weeks are archived in `archived_weeks` for history
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
        'week_start',
        'last_reset_at',
        'archived_weeks',
    ];

    /**
     * The attributes that should be cast.
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'completed_dates' => 'array',
        'archived_weeks' => 'array',
        'week_start' => 'date',
        'last_reset_at' => 'datetime',
    ];

    /**
     * Boot the model — auto-initialize week_start on creation.
     */


    // app/Models/Habit.php
    public function weeklyArchives()
    {
        return $this->hasMany(HabitWeeklyArchive::class);
    }

    // Call this when all 7 days are checked
    public function archiveAndResetWeek(): void
    {
        $weekStart = now()->startOfWeek()->toDateString();
        $weekEnd = now()->endOfWeek()->toDateString();

        // Get this week's completed dates
        $thisWeekDates = collect($this->completed_dates ?? [])
            ->filter(fn($d) => $d >= $weekStart && $d <= $weekEnd)
            ->values()
            ->all();

        // Archive the week
        HabitWeeklyArchive::create([
            'habit_id' => $this->id,
            'user_id' => $this->user_id,
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'completed_dates' => $thisWeekDates,
            'perfect_week' => count($thisWeekDates) === 7,
        ]);

        // Remove this week's dates from completed_dates, keep history
        $this->completed_dates = collect($this->completed_dates ?? [])
            ->filter(fn($d) => $d < $weekStart || $d > $weekEnd)
            ->values()
            ->all();

        $this->save();
    }
    protected static function booted(): void
    {
        static::creating(function (Habit $habit) {
            if (!$habit->week_start) {
                $habit->week_start = now()->startOfWeek();
            }
        });
    }

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
     * Triggers lazy weekly reset check before toggling.
     * Automatically recalculates and saves the current streak.
     * 
     * @param string $date YYYY-MM-DD format
     * @return void
     */
    public function toggleDate($date)
    {
        // Ensure weekly cycle is current before toggling
        $this->checkAndResetWeekIfNeeded();

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

    // =========================================================================
    //  WEEKLY CYCLE & AUTO-RESET LOGIC
    // =========================================================================

    /**
     * Get the number of completed days in the current week.
     * 
     * @return int
     */
    public function currentWeekCompletions(): int
    {
        $weekStart = $this->getEffectiveWeekStart();
        $weekEnd = $weekStart->copy()->endOfWeek();

        return collect($this->completed_dates ?? [])
            ->filter(fn($d) => $d >= $weekStart->toDateString() && $d <= $weekEnd->toDateString())
            ->count();
    }

    /**
     * Check whether the user hit their weekly frequency target.
     * 
     * @return bool
     */
    public function hitWeeklyTarget(): bool
    {
        return $this->currentWeekCompletions() >= $this->frequency;
    }

    /**
     * Get the effective week_start date (fallback to this Monday if null).
     * 
     * @return Carbon
     */
    public function getEffectiveWeekStart(): Carbon
    {
        return $this->week_start
            ? Carbon::parse($this->week_start)->startOfDay()
            : now()->startOfWeek();
    }

    /**
     * Lazy reset guard — called on every page load and toggle.
     * If the current week_start is before this Monday, a new week has begun
     * and we need to evaluate the previous week and reset.
     * 
     * This ensures the reset happens even if the scheduled command was missed.
     * 
     * @return bool Whether a reset was performed
     */
    public function checkAndResetWeekIfNeeded(): bool
    {
        $currentMonday = now()->startOfWeek()->toDateString();
        $habitWeekStart = $this->getEffectiveWeekStart()->toDateString();

        // No reset needed if we're still in the same week
        if ($habitWeekStart >= $currentMonday) {
            return false;
        }

        // A new week has started — evaluate and reset
        $this->performWeeklyReset();
        return true;
    }

    /**
     * Perform the weekly reset:
     * 1. Archive the completed week's performance
     * 2. Evaluate if the weekly target was hit → update streak
     * 3. Clear completed_dates for the new week
     * 4. Update week_start to current Monday
     * 
     * @return void
     */
    public function performWeeklyReset(): void
    {
        $previousWeekStart = $this->getEffectiveWeekStart();
        $previousWeekEnd = $previousWeekStart->copy()->endOfWeek();
        $currentMonday = now()->startOfWeek();

        // Count completions for the previous week
        $previousWeekDates = collect($this->completed_dates ?? [])
            ->filter(fn($d) => $d >= $previousWeekStart->toDateString() && $d <= $previousWeekEnd->toDateString())
            ->values()
            ->toArray();

        $completionCount = count($previousWeekDates);
        $targetHit = $completionCount >= $this->frequency;

        // Archive the previous week's performance
        $archive = $this->archived_weeks ?? [];
        $archive[] = [
            'week_start' => $previousWeekStart->toDateString(),
            'week_end' => $previousWeekEnd->toDateString(),
            'completed' => $completionCount,
            'target' => $this->frequency,
            'target_hit' => $targetHit,
            'streak_at' => $this->streak,
            'archived_at' => now()->toIso8601String(),
        ];

        // Keep only last 52 weeks of archive (1 year)
        if (count($archive) > 52) {
            $archive = array_slice($archive, -52);
        }

        // Update streak: increment if target hit, reset to 0 if missed
        // Handle multi-week gaps (e.g., user was away for 2+ weeks)
        $weeksMissed = $previousWeekStart->diffInWeeks($currentMonday) - 1;

        if ($weeksMissed > 0) {
            // User missed entire weeks in between — streak breaks
            $newStreak = 0;
        } else {
            $newStreak = $targetHit ? $this->streak + 1 : 0;
        }

        // Clear completed_dates (remove all dates before this Monday)
        $remainingDates = collect($this->completed_dates ?? [])
            ->filter(fn($d) => $d >= $currentMonday->toDateString())
            ->values()
            ->toArray();

        // Persist everything
        $this->streak = $newStreak;
        $this->completed_dates = $remainingDates;
        $this->week_start = $currentMonday;
        $this->last_reset_at = now();
        $this->archived_weeks = $archive;
        $this->save();
    }

    /**
     * Live streak — the real-time projected streak visible to the user.
     * 
     * If the user has already hit their weekly frequency target this week,
     * the streak is shown as (stored streak + 1) immediately — no need to
     * wait until Monday's reset for the dopamine hit.
     * 
     * If the target hasn't been hit yet, it shows the stored streak value
     * (which reflects how many consecutive past weeks were successful).
     * 
     * @return int
     */
    public function getLiveStreakAttribute(): int
    {
        if ($this->hitWeeklyTarget()) {
            return $this->streak + 1;
        }

        return $this->streak;
    }

    /**
     * Calculate how many consecutive past weeks the target was hit
     * by reading from the archived_weeks history.
     * 
     * @return int
     */
    public function calculateHistoricalWeekStreak(): int
    {
        $archive = collect($this->archived_weeks ?? [])->reverse()->values();

        $streak = 0;
        foreach ($archive as $week) {
            if ($week['target_hit'] ?? false) {
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }
}
