<?php

use function Livewire\Volt\{computed};
use App\Services\WakaTimeService;
use Illuminate\Support\Facades\Auth;

$today = computed(function () {
    return app(WakaTimeService::class)->todayStats();
});

$heatmap = computed(function () {
    $data = app(WakaTimeService::class)->yearSummaries();

    $start = now()->subYear()->startOfWeek();
    $end = now();
    $current = $start->copy();
    $map = [];

    while ($current <= $end) {
        $date = $current->toDateString();
        $seconds = $data[$date] ?? 0;

        $map[] = [
            'date' => $date,
            'seconds' => $seconds,
            'human' => gmdate('H:i', $seconds),
            'level' => match (true) {
                $seconds == 0 => 0,
                $seconds < 1800 => 1,  // < 30 mins
                $seconds < 3600 => 2,  // < 1 hr
                $seconds < 7200 => 3,  // < 2 hrs
                default => 4,  // 2hrs+
            },
        ];

        $current->addDay();
    }

    return collect($map);
});

?>

<div class="space-y-6">

    {{-- Today's Stats Cards --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="glass rounded-2xl p-5">
            <p class="text-xs font-bold uppercase text-gray-500 mb-1">Today's Coding</p>
            <p class="text-2xl font-bold gradient-text">{{ $this->today['human_readable'] ?? '—' }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-xs font-bold uppercase text-gray-500 mb-1">Top Language</p>
            <p class="text-2xl font-bold text-white">{{ $this->today['top_language'] ?? '—' }}</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-xs font-bold uppercase text-gray-500 mb-1">Top Project</p>
            <p class="text-2xl font-bold text-white">{{ $this->today['top_project'] ?? '—' }}</p>
        </div>
    </div>

    {{-- Coding Heatmap --}}
    <div class="glass rounded-3xl p-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h3 class="text-xl font-bold">Coding Activity</h3>
                <p class="text-sm text-gray-500">Your WakaTime coding patterns over the past year.</p>
            </div>
            <div class="flex items-center gap-2 text-[10px] font-bold uppercase text-gray-600">
                <span>Less</span>
                <div class="flex gap-1">
                    <div class="w-3 h-3 rounded-sm bg-white/5"></div>
                    <div class="w-3 h-3 rounded-sm bg-secondary/20"></div>
                    <div class="w-3 h-3 rounded-sm bg-secondary/40"></div>
                    <div class="w-3 h-3 rounded-sm bg-secondary/70"></div>
                    <div class="w-3 h-3 rounded-sm bg-secondary"></div>
                </div>
                <span>More</span>
            </div>
        </div>

        <div class="overflow-x-auto pb-4">
            <div class="flex flex-col gap-1 min-w-[800px]">
                <div class="flex gap-1">
                    @foreach($this->heatmap->chunk(7) as $week)
                        <div class="flex flex-col gap-1">
                            @foreach($week as $day)
                                <div class="w-3 h-3 rounded-sm transition-all duration-500 hover:ring-2 hover:ring-white/20 cursor-help
                                                            {{ $day['level'] == 0 ? 'bg-white/5' : '' }}
                                                            {{ $day['level'] == 1 ? 'bg-secondary/20' : '' }}
                                                            {{ $day['level'] == 2 ? 'bg-secondary/40' : '' }}
                                                            {{ $day['level'] == 3 ? 'bg-secondary/70' : '' }}
                                                            {{ $day['level'] == 4 ? 'bg-secondary' : '' }}"
                                    title="{{ $day['date'] }}: {{ $day['human'] }} coded">
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-between mt-4 text-[10px] font-bold uppercase text-gray-600 px-1">
                    @foreach(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $month)
                        <span>{{ $month }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>