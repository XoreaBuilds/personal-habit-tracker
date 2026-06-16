<?php

use function Livewire\Volt\{computed, on};
use App\Models\FocusSession;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

$sessions = computed(function () {
    return FocusSession::where('user_id', Auth::id())
        ->orderBy('completed_at', 'desc')
        ->take(30)
        ->get()
        ->groupBy(function ($session) {
            $date = $session->completed_at;
            if ($date->isToday()) return 'Today';
            if ($date->isYesterday()) return 'Yesterday';
            return $date->format('F d, Y');
        });
});

on([
    'session-completed' => function () {},
    'stats-updated' => function () {}
]);

?>

<div class="glass rounded-3xl p-8" wire:poll.300s>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h3 class="text-2xl font-bold tracking-tight">Recent Activity</h3>
            <p class="text-gray-400 mt-1">Your journey through time and focus.</p>
        </div>
        <div class="p-3 rounded-2xl bg-primary/10 border border-primary/20">
            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
    </div>

    @if($this->sessions->isEmpty())
        <div class="text-center py-20">
            <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-gray-400 font-medium">No focus sessions recorded yet.</p>
            <p class="text-sm text-gray-500 mt-1">Every great achievement starts with a single focused minute.</p>
        </div>
    @else
        <div class="space-y-8">
            @foreach($this->sessions as $dateGroup => $groupSessions)
                <div>
                    <h4 class="text-[11px] font-bold uppercase tracking-[0.2em] text-gray-500 mb-4 flex items-center gap-3">
                        {{ $dateGroup }}
                        <div class="h-px bg-white/5 flex-grow"></div>
                    </h4>
                    
                    <div class="grid gap-3">
                        @foreach($groupSessions as $session)
                            <div class="group relative">
                                <div class="glass glass-hover rounded-2xl px-6 py-4 flex items-center justify-between transition-all duration-300 border border-white/5 hover:border-white/10">
                                    <div class="flex items-center gap-5">
                                        {{-- Icon --}}
                                        <div class="relative">
                                            <div class="p-3 rounded-xl {{ $session->source === 'wakatime' ? 'bg-secondary/10' : 'bg-primary/10' }} transition-transform group-hover:scale-110 duration-300">
                                                @if($session->source === 'wakatime')
                                                    <svg class="w-5 h-5 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                                    </svg>
                                                @else
                                                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                @endif
                                            </div>
                                            @if($session->source === 'wakatime')
                                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-secondary rounded-full border-2 border-[#030712]"></div>
                                            @endif
                                        </div>

                                        {{-- Info --}}
                                        <div>
                                            <p class="text-sm font-bold text-white group-hover:text-primary transition-colors">
                                                {{ $session->source === 'wakatime' ? 'Code Integration' : 'Deep Focus Session' }}
                                            </p>
                                            <p class="text-[11px] text-gray-500 font-medium mt-0.5">
                                                {{ $session->completed_at->format('h:i A') }} • {{ $session->source === 'wakatime' ? 'WakaTime' : 'App' }}
                                            </p>
                                        </div>
                                    </div>

                                    {{-- Duration --}}
                                    <div class="text-right">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-black tracking-tight {{ $session->source === 'wakatime' ? 'text-secondary' : 'text-primary' }}">
                                                {{ $session->minutes_completed }} <span class="text-[10px] uppercase opacity-70">min</span>
                                            </span>
                                            <span class="text-[10px] text-gray-600 font-bold uppercase tracking-tighter">
                                                {{ number_format($session->minutes_completed / 60, 1) }} hours
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>