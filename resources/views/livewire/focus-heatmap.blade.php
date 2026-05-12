<?php

use function Livewire\Volt\{state, computed};
use App\Models\FocusSession;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

$sessions = computed(function () {
    $start = now()->subYear()->startOfWeek();
    $data = FocusSession::where('user_id', Auth::id())
        ->where('completed_at', '>=', $start)
        ->get()
        ->groupBy(fn($s) => $s->completed_at->toDateString())
        ->map(fn($group) => $group->sum('minutes_completed'));

    $heatmap = [];
    $current = $start->copy();
    $end = now();

    while ($current <= $end) {
        $date = $current->toDateString();
        $minutes = $data->get($date, 0);

        $heatmap[] = [
            'date' => $date,
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

    return collect($heatmap);
});

$syncHistory = function () {
    $userId = Auth::id();

    if ($userId) {
        app(\App\Services\WakaTimeService::class)->syncFullHistory($userId);
        $this->dispatch('stats-updated');
    }
};

?>

<div class="glass rounded-3xl p-8" wire:poll.300s>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h3 class="text-xl font-bold">Focus Intensity</h3>
            <p class="text-sm text-gray-500">Your concentration patterns over the past year.</p>
        </div>
        <div class="flex items-center gap-4">
            <button wire:click="syncHistory" wire:loading.attr="disabled"
                class="text-[10px] font-bold uppercase tracking-widest px-4 py-2 rounded-full bg-white/5 hover:bg-white/10 transition-colors flex items-center gap-2 group">
                <svg wire:loading.class="animate-spin"
                    class="w-3 h-3 text-primary group-hover:rotate-180 transition-transform" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span wire:loading.remove>Sync Activity</span>
                <span wire:loading>Syncing...</span>
            </button>
            <div class="flex items-center gap-2 text-[10px] font-bold uppercase text-gray-600">
                <span>Less</span>
                <div class="flex gap-1">
                    <div class="w-3 h-3 rounded-sm bg-white/5"></div>
                    <div class="w-3 h-3 rounded-sm bg-primary/20"></div>
                    <div class="w-3 h-3 rounded-sm bg-primary/40"></div>
                    <div class="w-3 h-3 rounded-sm bg-primary/70"></div>
                    <div class="w-3 h-3 rounded-sm bg-primary"></div>
                </div>
                <span>More</span>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto pb-4">
        <div class="flex flex-col gap-1 min-w-[800px]">
            <div class="flex gap-1">
                @php
                    $chunks = $this->sessions->chunk(7);
                @endphp

                @foreach($chunks as $week)
                    <div class="flex flex-col gap-1">
                        @foreach($week as $day)
                            <div class="w-3 h-3 rounded-sm transition-all duration-500 hover:ring-2 hover:ring-white/20 cursor-help
                                                        {{ $day['level'] == 0 ? 'bg-white/5' : '' }}
                                                        {{ $day['level'] == 1 ? 'bg-primary/20' : '' }}
                                                        {{ $day['level'] == 2 ? 'bg-primary/40' : '' }}
                                                        {{ $day['level'] == 3 ? 'bg-primary/70' : '' }}
                                                        {{ $day['level'] == 4 ? 'bg-primary' : '' }}"
                                title="{{ $day['date'] }}: {{ $day['minutes'] }} mins focused"></div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <div class="flex justify-between mt-4 text-[10px] font-bold uppercase text-gray-600 px-1">
                <span>Jan</span>
                <span>Feb</span>
                <span>Mar</span>
                <span>Apr</span>
                <span>May</span>
                <span>Jun</span>
                <span>Jul</span>
                <span>Aug</span>
                <span>Sep</span>
                <span>Oct</span>
                <span>Nov</span>
                <span>Dec</span>
            </div>
        </div>
    </div>
</div>