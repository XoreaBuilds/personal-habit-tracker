<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class HabitController extends Controller
{
    /**
     * Display a listing of the user's habits.
     */
    public function index()
    {
        $userId = Auth::id();
        $habits = Habit::when($userId, fn($q) => $q->where('user_id', $userId))->get();
        return view('habits.index', compact('habits'));
    }

    /**
     * Show the form for creating a new habit.
     */
    public function create()
    {
        return view('habits.create');
    }

    /**
     * Store a newly created habit.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'frequency' => 'required|integer|min:1|max:7',
        ]);

        $habit = new Habit($validated);
        $habit->user_id = Auth::id();
        $habit->save();

        return Redirect::route('habits.index')->with('status', 'Habit created!');
    }

    /**
     * Show the form for editing the specified habit.
     */
    public function edit(Habit $habit)
    {
        // Ensure the habit belongs to the current user (optional)
        if (Auth::id() && $habit->user_id !== Auth::id()) {
            abort(403);
        }
        return view('habits.edit', compact('habit'));
    }

    /**
     * Update the specified habit.
     */
    public function update(Request $request, Habit $habit)
    {
        if (Auth::id() && $habit->user_id !== Auth::id()) {
            abort(403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'frequency' => 'required|integer|min:1|max:7',
        ]);
        $habit->update($validated);
        return Redirect::route('habits.index')->with('status', 'Habit updated!');
    }

    /**
     * Remove the specified habit.
     */
    public function destroy(Habit $habit)
    {
        if (Auth::id() && $habit->user_id !== Auth::id()) {
            abort(403);
        }
        $habit->delete();
        return Redirect::back()->with('status', 'Habit deleted');
    }

    /**
     * Toggle completion for a habit on a given date (default today).
     */
    public function toggle(Request $request, Habit $habit)
    {
        if (Auth::id() && $habit->user_id !== Auth::id()) {
            abort(403);
        }
        $date = $request->input('date', now()->toDateString());
        $completed = $habit->completed_dates ?? [];
        if (in_array($date, $completed)) {
            $completed = array_filter($completed, fn($d) => $d !== $date);
        } else {
            $completed[] = $date;
        }
        $habit->completed_dates = array_values($completed);
        // Update streak calculation (simple: count consecutive days ending today)
        $habit->streak = $this->calculateStreak($habit);
        $habit->save();
        return Redirect::back();
    }

    /**
     * Calculate current streak based on completed_dates.
     */
    protected function calculateStreak(Habit $habit): int
    {
        if (!$habit->completed_dates) {
            return 0;
        }
        $dates = collect($habit->completed_dates)->sortDesc();
        $streak = 0;
        $today = now()->toDateString();
        $cursor = $today;
        foreach ($dates as $d) {
            if ($d === $cursor) {
                $streak++;
                $cursor = now()->subDays($streak)->toDateString();
            }
        }
        return $streak;
    }
}
