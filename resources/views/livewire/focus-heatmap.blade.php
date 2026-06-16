<?php

use function Livewire\Volt\{state, computed, on};
use App\Models\FocusSession;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

$sessions = computed(function () {
    // 1. Establish start (Monday) and pad the end date to the end of the current week (Sunday)
    // This ensures every single column chunk has exactly 7 days.
    $start = now()->subYear()->startOfWeek();
    $end = now()->endOfWeek();

    // 2. Fetch records safely casted/formatted
    $data = FocusSession::where('user_id', Auth::id())
        ->where('completed_at', '>=', $start)
        ->get()
        ->groupBy(function ($session) {
            // Safe parsing in case completed_at isn't casted in the Model
            return Carbon::parse($session->completed_at)->toDateString();
        })
        ->map(fn($group) => $group->sum('minutes_completed'));

    $heatmap = [];
    $current = $start->copy();

    // 3. Build the chronological array
    while ($current <= $end) {
        $date = $current->toDateString();
        $minutes = $data->get($date, 0);

        $heatmap[] = [
            'date' => $current->copy(),
            'minutes' => $minutes,
            'level' => match (true) {
                $minutes == 0 => 0,
                $minutes < 30 => 1,
                $minutes < 60 => 2,
                $minutes < 120 => 3,
                default => 4,
            }
        ];
        $current->addDay();
    }

    // Chunk them by 7 into perfect weeks directly inside the computed property
    return collect($heatmap)->chunk(7);
});

on([
    'session-completed' => function () {
        // Livewire updates computed properties automatically when a component re-renders.
        // Explicitly resetting or calling a render cycle enforces the refresh.
    }
]);

$syncHistory = function () {
    $userId = Auth::id();

    if ($userId) {
        app(\App\Services\WakaTimeService::class)->syncFullHistory($userId);
        $this->dispatch('stats-updated');
    }
};

?>

<div class="glass rounded-3xl p-8" wire:poll.300s>
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div>
            <h3 class="text-2xl font-bold tracking-tight">Focus Intensity</h3>
            <p class="text-gray-400 mt-1">Consistency is key to mastery. Keep the momentum going.</p>
        </div>

        <div class="flex items-center gap-4 self-start md:self-auto">
            <button wire:click="syncHistory" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-white/5 hover:bg-white/10 text-sm font-semibold transition-all border border-white/5 group">
                <svg wire:loading.class="animate-spin"
                    class="w-4 h-4 text-primary group-hover:rotate-180 transition-transform duration-500" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span wire:loading.remove>Sync Activity</span>
                <span wire:loading>Syncing...</span>
            </button>

            <div class="flex items-center gap-3 bg-white/5 px-4 py-2.5 rounded-2xl border border-white/5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500">Less</span>
                <div class="flex gap-1.5">
                    <div class="w-3 h-3 rounded-[3px] bg-white/5 shadow-inner"></div>
                    <div class="w-3 h-3 rounded-[3px] bg-primary/20 shadow-sm shadow-primary/10"></div>
                    <div class="w-3 h-3 rounded-[3px] bg-primary/40 shadow-sm shadow-primary/20"></div>
                    <div class="w-3 h-3 rounded-[3px] bg-primary/70 shadow-sm shadow-primary/30"></div>
                    <div class="w-3 h-3 rounded-[3px] bg-primary shadow-lg shadow-primary/40"></div>
                </div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500">More</span>
            </div>
        </div>
    </div>

    <div class="relative">
        <div class="overflow-x-auto pb-6 scrollbar-hide">
            <div class="inline-flex flex-col gap-2 min-w-full">

                {{-- Month Labels --}}
                <div class="flex h-5 text-[10px] font-bold uppercase tracking-widest text-gray-600 mb-1">
                    @php $currentMonth = null; @endphp
                    @foreach($this->sessions as $week)
                        <div class="w-3.5 flex-shrink-0 relative mr-1.5"> {{-- Matching block sizing + grid spacing --}}
                            @php $firstDayOfWeek = $week->first()['date']; @endphp
                            @if($firstDayOfWeek->format('M') !== $currentMonth)
                                <span class="absolute left-0 whitespace-nowrap">{{ $firstDayOfWeek->format('M') }}</span>
                                @php $currentMonth = $firstDayOfWeek->format('M'); @endphp
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Heatmap Grid --}}
                <div class="flex gap-1.5">
                    @foreach($this->sessions as $week)
                        <div class="flex flex-col gap-1.5">
                            @foreach($week as $day)
                                <div title="{{ $day['date']->format('M d, Y') }}: {{ $day['minutes'] }} mins"
                                    class="w-3.5 h-3.5 rounded-[3px] transition-all duration-300 hover:scale-125 hover:z-10 cursor-pointer
                                            {{ $day['level'] == 0 ? 'bg-white/5 hover:bg-white/20' : '' }}
                                            {{ $day['level'] == 1 ? 'bg-primary/20 hover:bg-primary/30 shadow-sm shadow-primary/5' : '' }}
                                            {{ $day['level'] == 2 ? 'bg-primary/40 hover:bg-primary/50 shadow-sm shadow-primary/10' : '' }}
                                            {{ $day['level'] == 3 ? 'bg-primary/70 hover:bg-primary/80 shadow-md shadow-primary/20' : '' }}
                                            {{ $day['level'] == 4 ? 'bg-primary hover:bg-primary shadow-lg shadow-primary/40' : '' }}"></div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div
        class="mt-6 flex flex-wrap items-center justify-between gap-4 text-xs text-gray-500 border-t border-white/5 pt-6">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-1.5">
                <div class="w-2 h-2 rounded-full bg-primary"></div>
                <span>Focus Sessions</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-2 h-2 rounded-full bg-secondary"></div>
                <span>Coding Activity</span>
            </div>
        </div>
        {{-- Total calculations unpacked cleanly from the pre-chunked collections --}}
        <p>Total time focused: <span
                class="text-white font-bold">{{ number_format($this->sessions->flatten(1)->sum('minutes')) }}</span>
            minutes</p>
    </div>
</div>