@extends('layouts.app')

@section('title', 'Edit Habit')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-12 flex justify-between items-end">
        <div>
            <a href="{{ route('habits.index') }}" class="text-primary hover:text-primary/80 flex items-center gap-2 mb-4 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>
            <h1 class="text-4xl font-bold">Refine Habit</h1>
            <p class="text-gray-400">Adjust your course.</p>
        </div>
        
        <form action="{{ route('habits.destroy', $habit) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this habit? All progress will be lost.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-gray-600 hover:text-accent transition-colors flex items-center gap-2 text-sm font-bold uppercase tracking-wider">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Delete Habit
            </button>
        </form>
    </div>

    <form action="{{ route('habits.update', $habit) }}" method="POST" class="glass rounded-3xl p-8 space-y-8">
        @csrf
        @method('PUT')
        
        <div class="space-y-2">
            <label for="name" class="block text-sm font-bold uppercase tracking-wider text-gray-500">Habit Name</label>
            <input type="text" name="name" id="name" required value="{{ $habit->name }}"
                class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
            @error('name') <p class="text-accent text-sm">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-2">
            <label for="description" class="block text-sm font-bold uppercase tracking-wider text-gray-500">Description</label>
            <textarea name="description" id="description" rows="3"
                class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all resize-none">{{ $habit->description }}</textarea>
            @error('description') <p class="text-accent text-sm">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-4">
            <label class="block text-sm font-bold uppercase tracking-wider text-gray-500">Frequency (Days per Week)</label>
            <div class="flex justify-between items-center gap-4">
                @foreach(range(1, 7) as $i)
                <label class="flex-1 cursor-pointer group">
                    <input type="radio" name="frequency" value="{{ $i }}" class="sr-only peer" {{ $habit->frequency == $i ? 'checked' : '' }}>
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
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
