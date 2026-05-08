<?php

use function Livewire\Volt\{computed};
use App\Models\FocusSession;
use Illuminate\Support\Facades\Auth;

$sessions = computed(function () {
    return FocusSession::where('user_id', Auth::id())
        ->orderBy('completed_at', 'desc')
        ->take(20)
        ->get();
});

?>

<div class="glass rounded-3xl p-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-xl font-bold">Focus History</h3>
            <p class="text-sm text-gray-500">Your recent focus sessions.</p>
        </div>
        <div class="p-3 rounded-2xl bg-primary/10">
            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
    </div>

    @if($this->sessions->isEmpty())
        <div class="text-center py-12">
            <p class="text-gray-500 text-sm">No focus sessions yet. Start focusing! 🎯</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($this->sessions as $session)
                <div class="glass-hover glass rounded-2xl px-5 py-4 flex items-center justify-between transition-all">
                    <div class="flex items-center gap-4">
                        {{-- Icon --}}
                        <div class="p-2 rounded-xl {{ $session->source === 'wakatime' ? 'bg-secondary/10' : 'bg-primary/10' }}">
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

                        {{-- Info --}}
                        <div>
                            <p class="text-sm font-semibold text-white">
                                {{ $session->source === 'wakatime' ? 'WakaTime Sync' : 'Focus Session' }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $session->completed_at->format('D, d M Y · h:i A') }}
                            </p>
                        </div>
                    </div>

                    {{-- Duration --}}
                    <div class="text-right">
                        <p class="text-sm font-bold gradient-text">
                            {{ $session->minutes_completed }} mins
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ round($session->minutes_completed / 60, 1) }} hrs
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>