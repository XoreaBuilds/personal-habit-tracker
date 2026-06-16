@extends('layouts.app')

@section('title', 'Focus Session')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-12 text-center">
            <h1 class="text-5xl font-black mb-4 tracking-tighter">Focus Mode</h1>
            <p class="text-gray-400 text-lg">Silence the noise. Amplify your potential.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-12">
            {{-- Main Timer Section --}}
            <div class="lg:col-span-8">
                <livewire:pomodoro-timer />
            </div>

            {{-- Sidebar Stats & Info --}}
            <div class="lg:col-span-4 space-y-6">
                {{-- Quick Stats Card --}}
                <div class="glass rounded-3xl p-8 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg class="w-24 h-24 text-primary" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    
                    <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                        <span class="p-2 rounded-lg bg-primary/10 text-primary">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </span>
                        Daily Progress
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
                        <div class="bg-white/5 rounded-2xl p-5 border border-white/5">
                            <span class="block text-3xl font-black text-primary">{{ $todaySessions }}</span>
                            <span class="text-[10px] text-gray-500 uppercase font-bold tracking-widest">Sessions</span>
                        </div>
                        <div class="bg-white/5 rounded-2xl p-5 border border-white/5">
                            <span class="block text-3xl font-black text-secondary">{{ $totalMinutes }}</span>
                            <span class="text-[10px] text-gray-500 uppercase font-bold tracking-widest">Minutes</span>
                        </div>
                    </div>
                </div>

                {{-- Guide Card --}}
                <div class="glass rounded-3xl p-8 border border-white/5">
                    <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                        <span class="p-2 rounded-lg bg-accent/10 text-accent">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        The Protocol
                    </h3>
                    <ul class="space-y-5">
                        <li class="flex gap-4">
                            <span class="w-7 h-7 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs shrink-0 border border-primary/20">1</span>
                            <div class="text-sm">
                                <p class="text-white font-bold">Select Objective</p>
                                <p class="text-gray-500 text-xs">Choose a specific habit to work on.</p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="w-7 h-7 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs shrink-0 border border-primary/20">2</span>
                            <div class="text-sm">
                                <p class="text-white font-bold">Deep Work</p>
                                <p class="text-gray-400 font-medium">25 minutes of focus.</p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="w-7 h-7 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs shrink-0 border border-primary/20">3</span>
                            <div class="text-sm">
                                <p class="text-white font-bold">Strategic Rest</p>
                                <p class="text-gray-400 font-medium">5 minutes to recharge.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Heatmap Section --}}
        <div class="mt-12">
            <livewire:focus-heatmap />
        </div>
    </div>
@endsection