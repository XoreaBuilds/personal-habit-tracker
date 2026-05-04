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
$selectedHabit = computed(fn() => $this->selectedHabitId ? Habit::find($this->selectedHabitId) : null);

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
    $this->setTimeByMode();
};

$setTimeByMode = function () {
    $this->timeLeft = match ($this->mode) {
        'focus' => 25 * 60,
        'short_break' => 5 * 60,
        'long_break' => 15 * 60,
    };
};

$switchMode = function ($newMode) {
    $this->mode = $newMode;
    $this->resetTimer();
};

$startEditingTimer = function () {
    if ($this->isActive)
        $this->isActive = false;
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
        $minutes = (int) floor(($this->mode === 'focus' ? 25*60 : ($this->mode === 'short_break' ? 5*60 : 15*60)) / 60); // Use planned duration
        
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
            if (!in_array($today, $habit->completed_dates ?? [])) {
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

    <div class="relative w-64 h-64 flex items-center justify-center mb-12">
        <svg class="w-full h-full -rotate-90">
            <circle cx="128" cy="128" r="120" stroke="currentColor" stroke-width="8" fill="transparent"
                class="text-white/5" />
            <circle cx="128" cy="128" r="120" stroke="currentColor" stroke-width="8" fill="transparent"
                class="{{ $mode === 'focus' ? 'text-primary' : ($mode === 'short_break' ? 'text-secondary' : 'text-accent') }}"
                stroke-dasharray="753.98"
                stroke-dashoffset="{{ 753.98 * (1 - $timeLeft / ($mode === 'focus' ? 25 * 60 : ($mode === 'short_break' ? 5 * 60 : 15 * 60))) }}"
                style="transition: stroke-dashoffset 1s linear;" />
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center">
            @if($isEditingTimer)
                <div class="flex items-center gap-1">
                    <input type="number" wire:model="editMinutes" min="0" max="99"
                        class="w-16 bg-white/10 border border-white/20 rounded-xl text-3xl font-bold text-center focus:outline-none focus:ring-2 focus:ring-primary">
                    <span class="text-3xl font-bold">:</span>
                    <input type="number" wire:model="editSeconds" min="0" max="59"
                        class="w-16 bg-white/10 border border-white/20 rounded-xl text-3xl font-bold text-center focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div class="flex gap-2 mt-4">
                    <button wire:click="applyEditedTime"
                        class="p-2 rounded-lg bg-primary hover:bg-primary/80 transition-colors">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                    <button wire:click="cancelEditing"
                        class="p-2 rounded-lg bg-white/10 hover:bg-white/20 transition-colors text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @else
                <button wire:click="startEditingTimer" class="group relative">
                    <span class="text-6xl font-bold tabular-nums group-hover:text-primary transition-colors">
                        {{ sprintf('%02d:%02d', floor($timeLeft / 60), $timeLeft % 60) }}
                    </span>
                    <div class="absolute -top-4 -right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </div>
                </button>
                <span
                    class="text-sm font-bold uppercase tracking-widest text-gray-500 mt-2">{{ str_replace('_', ' ', $mode) }}</span>
            @endif
        </div>
    </div>

    <div class="w-full max-w-xs mb-8">
        <label class="block text-[10px] uppercase font-bold text-gray-500 mb-2 text-center">Focusing on</label>
        <select wire:model.live="selectedHabitId"
            class="w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all text-sm appearance-none cursor-pointer">
            <option value="" class="bg-gray-900">Select a Habit</option>
            @foreach($this->habits as $habit)
                <option value="{{ $habit->id }}" class="bg-gray-900">{{ $habit->name }}</option>
            @endforeach
        </select>
        
        @if($this->selectedHabit)
            <div class="mt-4 flex justify-center">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-500/10 border border-orange-500/20">
                    <svg class="w-3 h-3 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1014 0c0-1.187-.29-2.285-.794-3.243L12.395 2.553zM13 13a3 3 0 11-6 0 3 3 0 016 0z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-[10px] font-bold text-orange-500 uppercase">{{ $this->selectedHabit->streak }} Day Streak</span>
                </div>
            </div>
        @endif
    </div>

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

    <div id="notification-container" class="fixed bottom-8 right-8 z-[100] flex flex-col gap-4"></div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('notify', (event) => {
                const container = document.getElementById('notification-container');
                const notification = document.createElement('div');
                const color = event.type === 'error' ? 'bg-accent' : (event.type === 'success' ? 'bg-primary' : 'bg-secondary');

                notification.className = `${color} text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-3 animate-slide-in`;
                notification.innerHTML = `
                    <span class="font-bold">${event.message}</span>
                `;

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
    </style>
</div>