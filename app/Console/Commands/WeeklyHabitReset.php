<?php

namespace App\Console\Commands;

use App\Models\Habit;
use Illuminate\Console\Command;

/**
 * Command: habits:weekly-reset
 * 
 * Iterates through all habits and triggers the weekly cycle reset for any
 * habit whose week_start is behind the current Monday. This is the scheduled
 * counterpart to the lazy reset guard in the Habit model.
 * 
 * Designed to run every Monday at 00:00 via the Laravel scheduler, but can
 * also be run manually: `php artisan habits:weekly-reset`
 */
class WeeklyHabitReset extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'habits:weekly-reset';

    /**
     * The console command description.
     */
    protected $description = 'Evaluate all habits for the past week and reset for the new weekly cycle';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Starting weekly habit reset...');

        $currentMonday = now()->startOfWeek()->toDateString();
        
        // Find all habits whose week_start is behind the current Monday
        $habits = Habit::where(function ($query) use ($currentMonday) {
            $query->whereNull('week_start')
                  ->orWhere('week_start', '<', $currentMonday);
        })->get();

        if ($habits->isEmpty()) {
            $this->info('✅ All habits are already up to date. No resets needed.');
            return self::SUCCESS;
        }

        $resetCount = 0;
        $streakBumps = 0;
        $streakBreaks = 0;

        foreach ($habits as $habit) {
            $oldStreak = $habit->streak;
            $completions = $habit->currentWeekCompletions();
            $targetHit = $completions >= $habit->frequency;

            $habit->performWeeklyReset();

            $resetCount++;

            if ($habit->streak > $oldStreak) {
                $streakBumps++;
                $this->line("  🔥 <fg=green>{$habit->name}</> — {$completions}/{$habit->frequency} ✓ Streak → {$habit->streak}");
            } else {
                $streakBreaks++;
                $this->line("  💔 <fg=red>{$habit->name}</> — {$completions}/{$habit->frequency} ✗ Streak reset to 0");
            }
        }

        $this->newLine();
        $this->info("📊 Reset complete: {$resetCount} habits processed");
        $this->info("   🔥 Streaks continued: {$streakBumps}");
        $this->info("   💔 Streaks broken: {$streakBreaks}");

        return self::SUCCESS;
    }
}
