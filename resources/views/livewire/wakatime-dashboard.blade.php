<?php

use function Livewire\Volt\{computed, state};
use App\Services\WakaTimeService;
use Illuminate\Support\Facades\Auth;

state(['apiKeyInput' => '']);

$saveApiKey = function () {
    $this->validate([
        'apiKeyInput' => 'required|string|min:10'
    ]);

    if ($user = Auth::user()) {
        $user->update(['wakatime_api_key' => $this->apiKeyInput]);
        $this->apiKeyInput = '';
    } else {
        session()->flash('error', 'You must be logged in to save your API key.');
    }
};

$hasKey = computed(function () {
    return app(WakaTimeService::class)->hasApiKey();
});

$today = computed(function () {
    if (!$this->hasKey)
        return [];
    return app(WakaTimeService::class)->todayStats();
});

$heatmap = computed(function () {
    if (!$this->hasKey)
        return collect();

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

$months = computed(function () {
    $months = [];
    $start = now()->subYear()->startOfMonth();
    for ($i = 0; $i < 12; $i++) {
        $months[] = $start->copy()->addMonths($i)->format('M');
    }
    return $months;
});

?>

<div class="space-y-6">

    @if(!$this->hasKey)
        <div class="glass rounded-3xl p-12 text-center">
            <div class="w-20 h-20 bg-secondary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
            </div>
            <h3 class="text-2xl font-bold mb-2">Connect WakaTime</h3>
            <p class="text-gray-400 mb-8 max-w-md mx-auto">
                Track your coding activity automatically by adding your WakaTime API key.
                You can find it in your WakaTime account settings.
            </p>
            <div class="flex flex-col gap-4 max-w-sm mx-auto">
                <input type="password" placeholder="Paste your API Key here..."
                    class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-secondary/50"
                    wire:model="apiKeyInput">
                <button wire:click="saveApiKey"
                    class="bg-secondary hover:bg-secondary-light text-white font-bold py-3 px-6 rounded-xl transition-all">
                    Save API Key
                </button>
            </div>
        </div>
    @else

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
                        @foreach($this->months as $month)
                            <span>{{ $month }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>