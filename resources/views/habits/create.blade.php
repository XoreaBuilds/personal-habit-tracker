@extends('layouts.app')

@section('title', 'Create New Habit')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-12">
        <a href="{{ route('habits.index') }}" class="text-primary hover:text-primary/80 flex items-center gap-2 mb-4 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Dashboard
        </a>
        <h1 class="text-4xl font-bold">New Journey</h1>
        <p class="text-gray-400">Define your next milestone.</p>
    </div>

    <form action="{{ route('habits.store') }}" method="POST" class="glass rounded-3xl p-8 space-y-8">
        @csrf
        
        <div class="space-y-2">
            <label for="name" class="block text-sm font-bold uppercase tracking-wider text-gray-500">Habit Name</label>
            <input type="text" name="name" id="name" required placeholder="e.g. Morning Meditation"
                class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all placeholder:text-gray-600">
            @error('name') <p class="text-accent text-sm">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-2">
            <label for="description" class="block text-sm font-bold uppercase tracking-wider text-gray-500">Description (Optional)</label>
            <textarea name="description" id="description" rows="3" placeholder="Why is this habit important to you?"
                class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all placeholder:text-gray-600 resize-none"></textarea>
            @error('description') <p class="text-accent text-sm">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-4">
            <label class="block text-sm font-bold uppercase tracking-wider text-gray-500">Frequency (Days per Week)</label>
            <div class="flex justify-between items-center gap-4">
                @foreach(range(1, 7) as $i)
                <label class="flex-1 cursor-pointer group">
                    <input type="radio" name="frequency" value="{{ $i }}" class="sr-only peer" {{ $i == 7 ? 'checked' : '' }}>
                    <div class="text-center py-4 rounded-2xl border border-white/10 bg-white/5 peer-checked:bg-primary peer-checked:border-primary peer-checked:text-white transition-all group-hover:bg-white/10">
                        <span class="block font-bold">{{ $i }}</span>
                    </div>
                </label>
                @endforeach
            </div>
            @error('frequency') <p class="text-accent text-sm">{{ $message }}</p> @enderror
        </div>

        <div class="pt-4">
            <button type="submit" class="w-full py-4 rounded-2xl bg-gradient-to-r from-primary to-secondary text-white font-bold text-lg hover:shadow-xl hover:shadow-primary/20 transition-all active:scale-[0.98]">
                Create Habit
            </button>
        </div>
    </form>
</div>
@endsection
