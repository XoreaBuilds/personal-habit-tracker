<?php

use function Livewire\Volt\{state, computed, on};
use App\Models\Habit;
use App\Models\FocusSession;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

state([
    'timeLeft' => 25 * 60,
    'isActive' => false,
    'timerEndsAt' => null,
    'mode' => 'focus', // focus, short_break, long_break
    'selectedHabitId' => null,
    'totalMinutesCompleted' => 0,
    'isEditingTimer' => false,
    'editMinutes' => 25,
    'editSeconds' => 0,
    'isMinimized' => false,
]);

$toggleMinimize = function () {
    $this->isMinimized = !$this->isMinimized;
};

$habits = computed(fn() => Habit::where('user_id', Auth::id())->get());

$startTimer = function () {
    if (!$this->selectedHabitId && $this->mode === 'focus') {
        $this->dispatch('notify', message: 'Select a habit to focus on.', type: 'error');
        return;
    }
    $this->isActive = true;
    $this->timerEndsAt = now()->addSeconds($this->timeLeft)->toIso8601String();
};

$pauseTimer = function () {
    $this->isActive = false;
};

$resetTimer = function () {
    $this->isActive = false;
    $this->isEditingTimer = false;
    $this->setTimeByMode();
};

$setTimeByMode = function () {
    $this->timeLeft = match ($this->mode) {
        'focus' => 25 * 60,
        'short_break' => 5 * 60,
        'long_break' => 15 * 60,
    };
    $this->editMinutes = (int) floor($this->timeLeft / 60);
    $this->editSeconds = $this->timeLeft % 60;
};

$switchMode = function ($newMode) {
    $this->mode = $newMode;
    $this->resetTimer();
};

$startEditing = function () {
    if ($this->isActive)
        return;
    $this->isEditingTimer = true;
    $this->editMinutes = (int) floor($this->timeLeft / 60);
    $this->editSeconds = $this->timeLeft % 60;
};

$applyEditedTime = function () {
    $minutes = max(0, min(99, (int) $this->editMinutes));
    $seconds = max(0, min(59, (int) $this->editSeconds));
    $total = $minutes * 60 + $seconds;
    $this->timeLeft = $total > 0 ? $total : 1;
    $this->isEditingTimer = false;
};

$cancelEditing = function () {
    $this->isEditingTimer = false;
};

$tick = function () {
    if (!$this->isActive || !$this->timerEndsAt) {
        return;
    }

    $endTime = Carbon::parse($this->timerEndsAt);
    
    if (now()->greaterThanOrEqualTo($endTime)) {
        $this->timeLeft = 0;
        $this->completeSession();
    } else {
        $this->timeLeft = now()->diffInSeconds($endTime, false);
    }
};

$completeSession = function () {
    $this->isActive = false;

    if ($this->mode === 'focus') {
        $minutes = 25;

        FocusSession::create([
            'user_id' => Auth::id(),
            'habit_id' => $this->selectedHabitId,
            'minutes_completed' => $minutes,
            'completed_at' => now(),
        ]);

        if ($this->selectedHabitId) {
            $habit = Habit::find($this->selectedHabitId);
            $today = now()->toDateString();
            if ($habit && !in_array($today, $habit->completed_dates ?? [])) {
                $habit->toggleDate($today);
            }
        }

        $this->dispatch('notify', message: "Session complete. Progress saved.", type: 'success');
        $this->dispatch('session-completed');
    } else {
        $this->dispatch('notify', message: "Break over. Ready?", type: 'info');
    }

    $this->resetTimer();
};

?>

<div wire:poll.1s.keep-alive="tick">
    @if($isMinimized)
    <div class="fixed bottom-8 right-8 z-[999] animate-slide-in">
        <div class="glass rounded-3xl overflow-hidden w-56 p-4 border border-white/10 shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full animate-pulse"
                         style="background: {{ match($mode) { 'focus' => '#6366f1', 'short_break' => '#10B981', default => '#F59E0B' } }}">
                    </div>
                    <span class="text-gray-400 text-[10px] font-bold uppercase tracking-widest">
                        {{ str_replace('_', ' ', $mode) }}
                    </span>
                </div>
                <button wire:click="toggleMinimize" class="text-gray-500 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="flex items-center justify-between gap-4">
                <div class="text-2xl font-black tabular-nums tracking-tighter">
                    {{ sprintf('%02d:%02d', floor($timeLeft / 60), $timeLeft % 60) }}
                </div>
                
                <div class="flex items-center gap-2">
                    @if(!$isActive)
                        <button wire:click="startTimer" class="w-8 h-8 rounded-full bg-primary flex items-center justify-center hover:scale-110 transition-transform">
                            <svg class="w-4 h-4 text-white ml-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M6.3 2.841A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                            </svg>
                        </button>
                    @else
                        <button wire:click="pauseTimer" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:scale-110 transition-transform">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm4 0a1 1 0 112 0v4a1 1 0 11-2 0V8z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @else
        <div class="glass rounded-3xl p-10 flex flex-col items-center border border-white/5 relative overflow-hidden group">
            <div class="absolute -top-24 -left-24 w-64 h-64 bg-primary/5 rounded-full blur-3xl group-hover:bg-primary/10 transition-all duration-1000"></div>
            
            <div class="w-full flex flex-col sm:flex-row justify-between items-center gap-6 mb-12 relative">
                <div class="flex p-1.5 rounded-2xl bg-white/5 border border-white/5">
                    @foreach(['focus' => 'Focus', 'short_break' => 'Short Break', 'long_break' => 'Long Break'] as $key => $label)
                        <button wire:click="switchMode('{{ $key }}')"
                            class="px-5 py-2.5 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all {{ $mode === $key ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'text-gray-500 hover:text-gray-300' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <button wire:click="toggleMinimize" class="text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:text-white px-4 py-2 rounded-xl border border-white/5 hover:bg-white/5 transition-all">
                    Minimize ↘
                </button>
            </div>

            @php
                $totalTime = match ($mode) { 'focus' => 25 * 60, 'short_break' => 5 * 60, 'long_break' => 15 * 60 };
                $circumference = 753.98;
                $progress = $totalTime > 0 ? $timeLeft / $totalTime : 0;
                $dashOffset = $circumference * (1 - $progress);
                $ringColor = match ($mode) { 'focus' => '#6366f1', 'short_break' => '#10B981', default => '#F59E0B' };
            @endphp
            
            <div class="relative w-72 h-72 flex items-center justify-center mb-12">
                <svg class="w-full h-full -rotate-90" viewBox="0 0 256 256">
                    <circle cx="128" cy="128" r="120" stroke="rgba(255,255,255,0.03)" stroke-width="6" fill="transparent" />
                    <circle cx="128" cy="128" r="120" stroke="{{ $ringColor }}" stroke-width="6" fill="transparent"
                        stroke-linecap="round" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $dashOffset }}"
                        class="transition-all duration-1000 ease-linear" />
                </svg>

                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    @if ($isEditingTimer)
                        <div class="flex items-center gap-2" x-data x-init="$nextTick(() => $refs.minInput.focus())">
                            <input x-ref="minInput" wire:model.lazy="editMinutes" type="number" min="0" max="99"
                                class="w-20 text-center text-5xl font-black tabular-nums bg-transparent border-b-4 border-primary text-white focus:outline-none"
                                x-on:keydown.enter="$wire.applyEditedTime()" x-on:keydown.escape="$wire.cancelEditing()" />
                            <span class="text-5xl font-black text-gray-600">:</span>
                            <input wire:model.lazy="editSeconds" type="number" min="0" max="59"
                                class="w-20 text-center text-5xl font-black tabular-nums bg-transparent border-b-4 border-primary text-white focus:outline-none"
                                x-on:keydown.enter="$wire.applyEditedTime()" x-on:keydown.escape="$wire.cancelEditing()" />
                        </div>
                        <div class="flex gap-4 mt-6">
                            <button wire:click="applyEditedTime" class="text-[11px] font-black uppercase tracking-widest px-6 py-2 rounded-full bg-primary text-white transition-all">Save</button>
                            <button wire:click="cancelEditing" class="text-[11px] font-black uppercase tracking-widest px-6 py-2 rounded-full bg-white/5 text-gray-500 transition-all">Cancel</button>
                        </div>
                    @else
                        <button wire:click="startEditing" class="group flex flex-col items-center transition-all {{ !$isActive ? 'hover:scale-105' : 'cursor-default' }}" @disabled($isActive)>
                            <span class="text-7xl font-black tabular-nums tracking-tighter">
                                {{ sprintf('%02d:%02d', floor($timeLeft / 60), $timeLeft % 60) }}
                            </span>
                            @if (!$isActive)
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-600 mt-2 opacity-0 group-hover:opacity-100 transition-all">Tap to Edit</span>
                            @else
                                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-primary mt-4 animate-pulse">Deep Focus</span>
                            @endif
                        </button>
                    @endif
                </div>
            </div>

            <div class="w-full max-w-sm mb-10 relative">
                <label class="block text-[10px] uppercase font-black text-gray-600 mb-3 text-center tracking-[0.3em]">Objective</label>
                <div class="relative">
                    <select wire:model.live="selectedHabitId"
                        class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all text-sm appearance-none cursor-pointer font-bold text-gray-300">
                        <option value="" class="bg-[#030712]">Select a habit to focus on</option>
                        @foreach($this->habits as $habit)
                            <option value="{{ $habit->id }}" class="bg-[#030712]">{{ $habit->name }}</option>
                        @endforeach
                    </select>
                    <div class="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </div>

                @php $selectedHabit = $this->selectedHabitId ? Habit::find($this->selectedHabitId) : null; @endphp
                @if($selectedHabit)
                    <div class="mt-4 flex justify-center animate-bounce">
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-orange-500/10 border border-orange-500/20 shadow-lg shadow-orange-500/5">
                            <span class="text-lg">🔥</span>
                            <span class="text-[10px] font-black text-orange-500 uppercase tracking-widest">{{ $selectedHabit->live_streak }} Week Streak</span>
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-6 relative">
                @if(!$isActive)
                    <button wire:click="startTimer" class="px-16 py-5 rounded-3xl bg-primary text-white font-black uppercase tracking-widest text-sm hover:shadow-2xl hover:shadow-primary/40 transition-all active:scale-95 hover:-translate-y-1">
                        Start Session
                    </button>
                @else
                    <button wire:click="pauseTimer" class="px-16 py-5 rounded-3xl bg-white/10 text-white font-black uppercase tracking-widest text-sm hover:bg-white/20 transition-all active:scale-95">
                        Pause
                    </button>
                @endif
                <button wire:click="resetTimer" class="p-5 rounded-3xl bg-white/5 text-gray-600 hover:text-white hover:bg-white/10 transition-all border border-white/5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                </button>
            </div>
        </div>
    @endif

    <div id="notification-container" class="fixed bottom-8 right-8 z-[100] flex flex-col gap-4"></div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('notify', (event) => {
                const container = document.getElementById('notification-container');
                const notification = document.createElement('div');
                const color = event.type === 'error' ? 'bg-accent' : (event.type === 'success' ? 'bg-primary' : 'bg-secondary');
                notification.className = `${color} text-white px-8 py-5 rounded-3xl shadow-2xl flex items-center gap-4 animate-slide-in border border-white/10`;
                notification.innerHTML = `<span class="text-lg">${event.type === 'success' ? '🎯' : (event.type === 'error' ? '⚠️' : 'ℹ️')}</span><span class="font-black uppercase tracking-widest text-xs">${event.message}</span>`;
                container.appendChild(notification);
                setTimeout(() => {
                    notification.classList.add('animate-slide-out');
                    setTimeout(() => notification.remove(), 500);
                }, 4000);
            });
        });
    </script>
</div>