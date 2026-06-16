@extends('layouts.app')

@section('title', 'Your Habits')

@section('content')
    <div class="mb-12">
        <h1 class="text-4xl font-bold mb-2">Welcome Back!</h1>
        <p class="text-gray-400">Track your progress and maintain your discipline.</p>
    </div>

    {{-- Perfect Week Success Banner --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            x-transition:leave="transition ease-in duration-500" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-4"
            class="mb-6 glass rounded-2xl p-4 flex items-center gap-4 border border-yellow-500/30 bg-yellow-500/5">
            <span class="text-3xl">🏆</span>
            <div>
                <p class="font-bold text-yellow-400">{{ session('message') }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Week archived and saved to your history.</p>
            </div>
            <button @click="show = false" class="ml-auto text-gray-500 hover:text-white">✕</button>
        </div>
    @endif

    {{-- Focus Heatmap --}}
    <div class="mb-12">
        <livewire:focus-heatmap />
    </div>

    @if($habits->isEmpty())
        <div class="glass rounded-3xl p-12 text-center max-w-2xl mx-auto">
            <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold mb-4">No habits yet</h2>
            <p class="text-gray-400 mb-8">Start your journey by creating your first daily habit.</p>
            <a href="{{ route('habits.create') }}"
                class="inline-flex items-center gap-2 px-8 py-3 rounded-full bg-primary hover:bg-primary/90 text-white font-bold transition-all hover:shadow-lg hover:shadow-primary/25">
                Get Started
            </a>
        </div>
    @else

        @php
            $lastWeekStart = now()->subWeek()->startOfWeek();
            $lastWeekEnd = now()->subWeek()->endOfWeek();

            // Check if ALL habits hit their target last week
            $allPerfect = $habits->every(function ($habit) use ($lastWeekStart, $lastWeekEnd) {
                $completedLastWeek = collect($habit->completed_dates ?? [])
                    ->filter(fn($d) => $d >= $lastWeekStart->toDateString() && $d <= $lastWeekEnd->toDateString())
                    ->count();
                return $completedLastWeek >= $habit->frequency;
            });
        @endphp

        {{-- Dashboard-level perfect week banner --}}
        @if($allPerfect && $habits->isNotEmpty())
            <div class="mb-8 glass rounded-3xl p-5 flex items-center gap-4 border border-yellow-500/30 bg-yellow-500/5">
                <div class="text-4xl">🏆</div>
                <div>
                    <p class="font-bold text-yellow-400 text-lg">Perfect Week — All Habits!</p>
                    <p class="text-sm text-gray-400">You hit every target last week. Keep the streak alive!</p>
                </div>
            </div>
        @endif

        {{-- Stats --}}
        <div class="mb-8">
            @livewire('dashboard-stats')
        </div>

        {{-- Habit Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            @foreach($habits as $habit)
                @php
                    $completedLastWeek = collect($habit->completed_dates ?? [])
                        ->filter(fn($d) => $d >= $lastWeekStart->toDateString() && $d <= $lastWeekEnd->toDateString())
                        ->count();
                    $perfectLastWeek = $completedLastWeek >= $habit->frequency;

                    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                    $today = now();
                    $startOfWeek = $today->copy()->startOfWeek();
                @endphp

                <div
                    class="glass rounded-3xl p-6 glass-hover transition-all duration-300 {{ $perfectLastWeek ? 'ring-1 ring-yellow-500/30' : '' }}">

                    {{-- FIXED SECTION: The habit header title block is now correctly inside the loop --}}
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h3 class="text-xl font-bold mb-1">{{ $habit->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $habit->description }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            {{-- Perfect week badge on card --}}
                            @if($perfectLastWeek)
                                <div class="flex items-center gap-1 px-2 py-1 rounded-full bg-yellow-500/10 border border-yellow-500/20"
                                    title="Hit target last week!">
                                    <span class="text-sm">🏆</span>
                                    <span class="text-[10px] font-bold text-yellow-400 uppercase">Last Week</span>
                                </div>
                            @endif
                            <a href="{{ route('habits.edit', $habit) }}"
                                class="p-2 rounded-lg hover:bg-white/5 text-gray-400 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    {{-- Week Toggles --}}
                    <div class="flex justify-between items-center mb-8">
                        <div class="flex gap-2">
                            @foreach(range(0, 6) as $i)
                                @php
                                    $currentDate = $startOfWeek->copy()->addDays($i);
                                    $isCompleted = in_array($currentDate->toDateString(), $habit->completed_dates ?? []);
                                    $isToday = $currentDate->isToday();
                                @endphp
                                <div class="flex flex-col items-center gap-2">
                                    <span class="text-[10px] uppercase font-bold text-gray-500">{{ $days[$i] }}</span>
                                    <form action="{{ route('habits.toggle', $habit) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="date" value="{{ $currentDate->toDateString() }}">
                                        <button type="submit"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-300
                                                                                                {{ $isCompleted ? 'bg-primary text-white scale-110 shadow-lg shadow-primary/30' : 'bg-white/5 text-gray-600 hover:bg-white/10' }}
                                                                                                {{ $isToday ? 'ring-2 ring-accent ring-offset-2 ring-offset-[#030712]' : '' }}">
                                            @if($isCompleted)
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Progress bar for current week --}}
                    @php
                        $thisWeekCount = $habit->currentWeekCompletions();
                        $progressPct = min(100, round(($thisWeekCount / max(1, $habit->frequency)) * 100));
                        $targetHitThisWeek = $thisWeekCount >= $habit->frequency;
                        $liveStreak = $habit->live_streak;
                    @endphp
                    <div class="mb-4">
                        <div class="flex justify-between text-[10px] text-gray-500 font-bold uppercase mb-1">
                            <span>
                                This week
                                @if($targetHitThisWeek)
                                    <span class="text-green-400 ml-1">✓ Complete</span>
                                @endif
                            </span>
                            <span class="{{ $targetHitThisWeek ? 'text-green-400' : '' }}">
                                {{ $thisWeekCount }} / {{ $habit->frequency }}
                            </span>
                        </div>
                        <div class="h-1.5 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500
                                                                    {{ $progressPct >= 100 ? 'bg-green-400' : 'bg-primary' }}"
                                style="width: {{ $progressPct }}%">
                            </div>
                        </div>
                    </div>

                    {{-- Footer Info --}}
                    <div class="flex items-center justify-between pt-4 border-t border-white/5">
                        <div class="flex items-center gap-2">
                            <div class="{{ $targetHitThisWeek ? 'text-green-400' : 'text-orange-500' }}">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1014 0c0-1.187-.29-2.285-.794-3.243L12.395 2.553zM13 13a3 3 0 11-6 0 3 3 0 016 0z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <span class="font-bold {{ $targetHitThisWeek ? 'text-green-400' : 'text-orange-500' }}">
                                🔥 {{ $liveStreak }} Week Streak
                            </span>
                        </div>
                        <div class="text-gray-500 text-xs font-medium">
                            Target: {{ $habit->frequency }}x / week
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- WakaTime Section --}}
        <div class="mb-8">
            <h2 class="text-lg font-bold text-gray-400 uppercase tracking-wider mb-4">⌨️ WakaTime</h2>
            @livewire('wakatime-dashboard')
        </div>

        {{-- Focus History --}}
        <div class="mt-8">
            @livewire('focus-history')
        </div>
    @endif
@endsection