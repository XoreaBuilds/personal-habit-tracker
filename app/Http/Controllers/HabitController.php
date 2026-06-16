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
     * 
     * Triggers a lazy weekly reset check on each habit to ensure
     * streaks and completed_dates are current, even if the scheduler
     * was missed (e.g., server was down over Monday midnight).
     */
    public function index()
    {
        $userId = Auth::id();
        $habits = Habit::when($userId, fn($q) => $q->where('user_id', $userId))->get();

        // Lazy reset guard — ensures weekly cycle is current
        foreach ($habits as $habit) {
            $habit->checkAndResetWeekIfNeeded();
        }

        // Re-fetch to get freshly updated data
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
     * Toggle completion for a habit on a given date.
     * 
     * After toggling, checks if all 7 days of the current week are now
     * completed. If so, archives the week, increments the streak,
     * and redirects with a success message.
     */
    public function toggle(Request $request, Habit $habit)
    {
        if (Auth::id() && $habit->user_id !== Auth::id()) {
            abort(403);
        }

        $date = $request->input('date', today()->toDateString());
        $habit->toggleDate($date);

        // Check if all 7 days this week are now completed
        $weekStart = now()->startOfWeek()->toDateString();
        $weekEnd = now()->endOfWeek()->toDateString();

        $thisWeekDates = collect($habit->completed_dates ?? [])
            ->filter(fn($d) => $d >= $weekStart && $d <= $weekEnd)
            ->values();

        // Generate all 7 day strings for this week
        $allSevenDays = collect(range(0, 6))
            ->map(fn($i) => now()->startOfWeek()->addDays($i)->toDateString());

        $allChecked = $allSevenDays->every(
            fn($day) => $thisWeekDates->contains($day)
        );

        if ($allChecked) {
            // Increment streak before archiving
            $habit->streak = $habit->streak + 1;
            $habit->save();

            // Archive and clear the week
            $habit->archiveAndResetWeek();

            return Redirect::back()->with([
                'success' => true,
                'message' => '🏆 Perfect week archived! Starting fresh. Streak: ' . $habit->streak,
            ]);
        }

        return Redirect::back();
    }
}
