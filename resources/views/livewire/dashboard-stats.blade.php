<?php
/**
 * DashboardStats - Livewire Volt Component
 * 
 * Provides real-time statistics for the user dashboard, including focus hours,
 * habit completion counts, and streak tracking.
 */

use function Livewire\Volt\{computed};
use App\Models\FocusSession;
use App\Models\Habit;
use Illuminate\Support\Facades\Auth;

// Total focus hours this month
$monthlyHours = computed(function () {
    $minutes = FocusSession::where('user_id', Auth::id())
        ->whereMonth('completed_at', now()->month)
        ->whereYear('completed_at', now()->year)
        ->sum('minutes_completed');

    return round($minutes / 60, 1);
});

// Best streak ever (consecutive days with focus session)
$bestStreak = computed(function () {
    $dates = FocusSession::where('user_id', Auth::id())
        ->orderBy('completed_at')
        ->pluck('completed_at')
        ->map(fn($d) => $d->toDateString())
        ->unique()
        ->values();

    $best = 0;
    $current = 1;

    for ($i = 1; $i < $dates->count(); $i++) {
        $prev = \Carbon\Carbon::parse($dates[$i - 1]);
        $curr = \Carbon\Carbon::parse($dates[$i]);

        if ($prev->diffInDays($curr) === 1) {
            $current++;
            $best = max($best, $current);
        } else {
            $current = 1;
        }
    }

    return max($best, $dates->count() > 0 ? 1 : 0);
});

// Total habits completed this week
$weeklyHabits = computed(function () {
    $startOfWeek = now()->startOfWeek()->toDateString();
    $endOfWeek = now()->endOfWeek()->toDateString();

    return Habit::where('user_id', Auth::id())
        ->get()
        ->flatMap(fn($habit) => $habit->completed_dates ?? [])
        ->filter(fn($date) => $date >= $startOfWeek && $date <= $endOfWeek)
        ->count();
});

// Current streak (today or yesterday has a session)
$currentStreak = computed(function () {
    $dates = FocusSession::where('user_id', Auth::id())
        ->orderByDesc('completed_at')
        ->pluck('completed_at')
        ->map(fn($d) => $d->toDateString())
        ->unique()
        ->values();

    if ($dates->isEmpty())
        return 0;

    $streak = 0;
    $checkDay = now()->toDateString();

    foreach ($dates as $date) {
        if ($date === $checkDay) {
            $streak++;
            $checkDay = \Carbon\Carbon::parse($checkDay)->subDay()->toDateString();
        } else {
            break;
        }
    }

    return $streak;
});

?>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">

    {{-- Total Focus Hours This Month --}}
    <div class="glass rounded-2xl p-6 flex flex-col gap-2">
        <div class="p-2 rounded-xl bg-primary/10 w-fit">
            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <p class="text-3xl font-bold gradient-text">{{ $this->monthlyHours }}h</p>
        <p class="text-xs font-bold uppercase text-gray-500">Focus This Month</p>
    </div>

    {{-- Best Streak Ever --}}
    <div class="glass rounded-2xl p-6 flex flex-col gap-2">
        <div class="p-2 rounded-xl bg-accent/10 w-fit">
            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
            </svg>
        </div>
        <p class="text-3xl font-bold gradient-text">{{ $this->bestStreak }}</p>
        <p class="text-xs font-bold uppercase text-gray-500">Best Streak (days)</p>
    </div>

    {{-- Habits This Week --}}
    <div class="glass rounded-2xl p-6 flex flex-col gap-2">
        <div class="p-2 rounded-xl bg-secondary/10 w-fit">
            <svg class="w-5 h-5 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
        </div>
        <p class="text-3xl font-bold gradient-text">{{ $this->weeklyHabits }}</p>
        <p class="text-xs font-bold uppercase text-gray-500">Habits This Week</p>
    </div>

    {{-- Current Streak --}}
    <div class="glass rounded-2xl p-6 flex flex-col gap-2">
        <div class="p-2 rounded-xl bg-green-500/10 w-fit">
            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
        </div>
        <p class="text-3xl font-bold gradient-text">{{ $this->currentStreak }}</p>
        <p class="text-xs font-bold uppercase text-gray-500">Current Streak (days)</p>
    </div>

</div>