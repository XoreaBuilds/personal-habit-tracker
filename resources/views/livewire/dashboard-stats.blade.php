<?php
/**
 * DashboardStats - Livewire Volt Component
 * Provides real-time statistics for the user dashboard, including focus hours,
 * habit completion counts, and streak tracking.
 */

// FIXED: Added 'on' to the imported functions array below
use function Livewire\Volt\{computed, on};
use App\Models\FocusSession;
use App\Models\Habit;
use Illuminate\Support\Facades\Auth;

// --- COMPUTED PROPERTIES ---

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

    if ($dates->count() === 0) {
        return 0;
    }

    for ($i = 1; $i < $dates->count(); $i++) {
        $prev = \Carbon\Carbon::parse($dates[$i - 1]);
        $curr = \Carbon\Carbon::parse($dates[$i]);

        if ($prev->diffInDays($curr) === 1) {
            $current++;
            $best = max($best, $current);
        } else if ($prev->diffInDays($curr) > 1) {
            $current = 1;
        }
    }

    return max($best, $current);
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

// Real-time WakaTime Stats
$wakaStats = computed(function () {
    return app(\App\Services\WakaTimeService::class)->todayStats();
});

// Current streak (today or yesterday has a session)
$currentStreak = computed(function () {
    $dates = FocusSession::where('user_id', Auth::id())
        ->orderByDesc('completed_at')
        ->pluck('completed_at')
        ->map(fn($d) => $d->toDateString())
        ->unique()
        ->values();

    if ($dates->isEmpty()) {
        return 0;
    }

    $streak = 0;
    $today = now()->toDateString();
    $yesterday = now()->subDay()->toDateString();

    if ($dates[0] === $today) {
        $checkDay = $today;
    } elseif ($dates[0] === $yesterday) {
        $checkDay = $yesterday;
    } else {
        return 0; // Streak broken
    }

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

// Calculate how many habits have met their frequency target this week
$habitsPerfectCount = computed(function () {
    $startOfWeek = now()->startOfWeek()->toDateString();
    $endOfWeek = now()->endOfWeek()->toDateString();

    return auth()->user()->habits->filter(function ($habit) use ($startOfWeek, $endOfWeek) {
        $completionsThisWeek = collect($habit->completed_dates ?? [])
            ->filter(fn($date) => $date >= $startOfWeek && $date <= $endOfWeek)
            ->count();

        return $completionsThisWeek >= $habit->frequency;
    })->count();
});

// Now this works perfectly because 'on' is explicitly imported!
on([
    'stats-updated' => function () { },
    'session-completed' => function () { },
    'habit-toggled' => function () { }
]);

?>