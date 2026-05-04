<?php

use App\Http\Controllers\HabitController;

Route::get('/', function () {
    return redirect()->route('habits.index');
});

Route::resource('habits', HabitController::class);
Route::post('habits/{habit}/toggle', [HabitController::class, 'toggle'])->name('habits.toggle');
Route::get('focus', function () {
    return view('focus');
})->name('focus');
