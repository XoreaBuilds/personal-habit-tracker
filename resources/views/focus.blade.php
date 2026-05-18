@extends('layouts.app')

@section('title', 'Focus Session')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-12 text-center">
            <h1 class="text-4xl font-bold mb-2">Focus Mode</h1>
            <p class="text-gray-400">Zero distractions, total concentration.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <livewire:pomodoro-timer />
            </div>

            <div class="space-y-6">
                <div class="glass rounded-3xl p-6">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        How it works
                    </h3>
                    <ul class="space-y-4 text-sm text-gray-400">
                        <li class="flex gap-3">
                            <span
                                class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs shrink-0">1</span>
                            Select a habit you want to work on.
                        </li>
                        <li class="flex gap-3">
                            <span
                                class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs shrink-0">2</span>
                            Focus for 25 minutes until the timer ends.
                        </li>
                        <li class="flex gap-3">
                            <span
                                class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs shrink-0">3</span>
                            Take a short break to recharge.
                        </li>
                        <li class="flex gap-3">
                            <span
                                class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs shrink-0">4</span>
                            Repeat until your target is achieved.
                        </li>
                    </ul>
                </div>

                <div class="glass rounded-3xl p-6">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Your Stats
                    </h3>
                    @php
                        $todaySessions = \App\Models\FocusSession::where('user_id', Auth::id())
                            ->whereDate('completed_at', today())
                            ->count();
                        $totalMinutes = \App\Models\FocusSession::where('user_id', Auth::id())
                            ->whereDate('completed_at', today())
                            ->sum('minutes_completed');
                    @endphp
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white/5 rounded-2xl p-4">
                            <span class="block text-2xl font-bold">{{ $todaySessions }}</span>
                            <span class="text-[10px] text-gray-500 uppercase font-bold">Sessions Today</span>
                        </div>
                        <div class="bg-white/5 rounded-2xl p-4">
                            <span class="block text-2xl font-bold">{{ $totalMinutes }}</span>
                            <span class="text-[10px] text-gray-500 uppercase font-bold">Minutes Total</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection