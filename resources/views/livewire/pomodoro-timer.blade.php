<?php

use function Livewire\Volt\{state, computed, on};
use App\Models\Habit;
use App\Models\FocusSession;
use Illuminate\Support\Facades\Auth;

state([
    'timeLeft' => 25 * 60,
    'isActive' => false,
    'mode' => 'focus', // focus, short_break, long_break
    'selectedHabitId' => null,
    'totalMinutesCompleted' => 0,
    'isEditingTimer' => false,
    'editMinutes' => 25,
    'editSeconds' => 0,
]);

$habits = computed(fn() => Habit::where('user_id', Auth::id())->get());

$startTimer = function () {
    if (!$this->selectedHabitId && $this->mode === 'focus') {
        $this->dispatch('notify', message: 'Please select a habit to focus on first.', type: 'error');
        return;
    }
    $this->isActive = true;
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
        return; // can't edit while running
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
    if ($this->isActive && $this->timeLeft > 0) {
        $this->timeLeft--;
    } elseif ($this->isActive && $this->timeLeft === 0) {
        $this->completeSession();
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

        // Auto-fill the habit streak for today
        if ($this->selectedHabitId) {
            $habit = Habit::find($this->selectedHabitId);
            $today = now()->toDateString();
            if ($habit && !in_array($today, $habit->completed_dates ?? [])) {
                $habit->toggleDate($today);
            }
        }

        $this->dispatch('notify', message: "Great job! Session saved and streak updated.", type: 'success');
        $this->dispatch('session-completed');
    } else {
        $this->dispatch('notify', message: "Break over! Ready to focus?", type: 'info');
    }

    $this->resetTimer();
};

?>

<div class="glass rounded-3xl p-8 flex flex-col items-center" wire:poll.1s="tick">
    {{-- Mode Switcher --}}
    <div class="flex gap-4 mb-12">
        <button wire:click="switchMode('focus')"
            class="px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $mode === 'focus' ? 'bg-primary text-white' : 'bg-white/5 text-gray-500 hover:bg-white/10' }}">
            Focus
        </button>
        <button wire:click="switchMode('short_break')"
            class="px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $mode === 'short_break' ? 'bg-secondary text-white' : 'bg-white/5 text-gray-500 hover:bg-white/10' }}">
            Short Break
        </button>
        <button wire:click="switchMode('long_break')"
            class="px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $mode === 'long_break' ? 'bg-accent text-white' : 'bg-white/5 text-gray-500 hover:bg-white/10' }}">
            Long Break
        </button>
    </div>

    {{-- Timer Circle --}}
    @php
        $totalTime = match ($mode) {
            'focus' => 25 * 60,
            'short_break' => 5 * 60,
            'long_break' => 15 * 60,
        };
        $circumference = 753.98;
        $progress = $totalTime > 0 ? $timeLeft / $totalTime : 0;
        $dashOffset = $circumference * (1 - $progress);
        $ringColor = match ($mode) {
            'focus' => '#7C3AED',        // purple / primary
            'short_break' => '#10B981',  // green / secondary
            'long_break' => '#F59E0B',   // amber / accent
        };
    @endphp
    <div class="relative w-64 h-64 flex items-center justify-center mb-12">
        <svg class="w-full h-full -rotate-90" viewBox="0 0 256 256">
            <circle cx="128" cy="128" r="120" stroke="rgba(255,255,255,0.05)" stroke-width="8" fill="transparent" />
            <circle cx="128" cy="128" r="120" stroke="{{ $ringColor }}" stroke-width="8" fill="transparent"
                stroke-linecap="round" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $dashOffset }}"
                style="transition: stroke-dashoffset 1s linear;" />
        </svg>

        <div class="absolute inset-0 flex flex-col items-center justify-center">
            @if ($isEditingTimer)
                {{-- Inline Edit Form --}}
                <div class="flex items-center gap-1" x-data x-init="$nextTick(() => $refs.minInput.focus())">
                    <input x-ref="minInput" wire:model.lazy="editMinutes" type="number" min="0" max="99"
                        class="w-16 text-center text-4xl font-bold tabular-nums bg-transparent border-b-2 border-primary text-white focus:outline-none appearance-none [&::-webkit-inner-spin-button]:hidden"
                        placeholder="MM" x-on:keydown.enter="$wire.applyEditedTime()"
                        x-on:keydown.escape="$wire.cancelEditing()" />
                    <span class="text-4xl font-bold text-gray-400 leading-none">:</span>
                    <input wire:model.lazy="editSeconds" type="number" min="0" max="59"
                        class="w-16 text-center text-4xl font-bold tabular-nums bg-transparent border-b-2 border-primary text-white focus:outline-none appearance-none [&::-webkit-inner-spin-button]:hidden"
                        placeholder="SS" x-on:keydown.enter="$wire.applyEditedTime()"
                        x-on:keydown.escape="$wire.cancelEditing()" />
                </div>
                <div class="flex gap-3 mt-3">
                    <button wire:click="applyEditedTime"
                        class="text-xs font-bold px-3 py-1 rounded-lg bg-primary/20 text-primary hover:bg-primary/30 transition-all">
                        Set
                    </button>
                    <button wire:click="cancelEditing"
                        class="text-xs font-bold px-3 py-1 rounded-lg bg-white/5 text-gray-500 hover:bg-white/10 transition-all">
                        Cancel
                    </button>
                </div>
            @else
                {{-- Display Time — click to edit (only when paused) --}}
                <button wire:click="startEditing"
                    class="group flex flex-col items-center {{ !$isActive ? 'cursor-text hover:opacity-80' : 'cursor-default' }} transition-opacity"
                    title="{{ !$isActive ? 'Click to edit time' : '' }}" @disabled($isActive)>
                    <span class="text-6xl font-bold tabular-nums">
                        {{ sprintf('%02d:%02d', floor($timeLeft / 60), $timeLeft % 60) }}
                    </span>
                    @if (!$isActive)
                        <span
                            class="text-[10px] font-bold uppercase tracking-widest text-gray-600 mt-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            click to edit
                        </span>
                    @else
                        <span class="text-sm font-bold uppercase tracking-widest text-gray-500 mt-2">
                            {{ str_replace('_', ' ', $mode) }}
                        </span>
                    @endif
                </button>
            @endif
        </div>
    </div>

    {{-- Habit Selector --}}
    <div class="w-full max-w-xs mb-8">
        <label class="block text-[10px] uppercase font-bold text-gray-500 mb-2 text-center">Focusing on</label>
        <select wire:model.live="selectedHabitId"
            class="w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all text-sm appearance-none cursor-pointer">
            <option value="" class="bg-gray-900">Select a Habit</option>
            @foreach($this->habits as $habit)
                <option value="{{ $habit->id }}" class="bg-gray-900">{{ $habit->name }}</option>
            @endforeach
        </select>

        @php
            $selectedHabit = $this->selectedHabitId ? \App\Models\Habit::find($this->selectedHabitId) : null;
        @endphp
        
        @if($selectedHabit)
            <div class="mt-4 flex justify-center">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-500/10 border border-orange-500/20">
                    <svg class="w-3 h-3 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1014 0c0-1.187-.29-2.285-.794-3.243L12.395 2.553zM13 13a3 3 0 11-6 0 3 3 0 016 0z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-[10px] font-bold text-orange-500 uppercase">{{ $selectedHabit->streak }} Day Streak</span>
                </div>
            </div>
        @endif
    </div>

    {{-- Controls --}}
    <div class="flex gap-4">
        @if(!$isActive)
            <button wire:click="startTimer"
                class="px-12 py-4 rounded-2xl bg-gradient-to-r from-primary to-secondary text-white font-bold text-lg hover:shadow-xl hover:shadow-primary/20 transition-all active:scale-[0.98]">
                Start
            </button>
        @else
            <button wire:click="pauseTimer"
                class="px-12 py-4 rounded-2xl bg-white/10 text-white font-bold text-lg hover:bg-white/20 transition-all active:scale-[0.98]">
                Pause
            </button>
        @endif

        <button wire:click="resetTimer"
            class="p-4 rounded-2xl bg-white/5 text-gray-500 hover:bg-white/10 transition-all">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
        </button>
    </div>

    {{-- Notification Container --}}
    <div id="notification-container" class="fixed bottom-8 right-8 z-[100] flex flex-col gap-4"></div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('notify', (event) => {
                const container = document.getElementById('notification-container');
                const notification = document.createElement('div');
                const color = event.type === 'error' ? 'bg-accent' : (event.type === 'success' ? 'bg-primary' : 'bg-secondary');

                notification.className = `${color} text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-3 animate-slide-in`;
                notification.innerHTML = `<span class="font-bold">${event.message}</span>`;

                container.appendChild(notification);
                setTimeout(() => {
                    notification.classList.add('animate-slide-out');
                    setTimeout(() => notification.remove(), 500);
                }, 4000);
            });
        });
    </script>

    <style>
        @keyframes slide-in {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slide-out {
            from {
                transform: translateX(0);
                opacity: 1;
            }

            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .animate-slide-in {
            animation: slide-in 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        .animate-slide-out {
            animation: slide-out 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        /* Hide number input spinners */
        input[type=number]::-webkit-outer-spin-button,
        input[type=number]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
</div>