<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\WakaTimeService;
use App\Models\User;

/**
 * console.php - Artisan Command and Task Scheduling
 * 
 * This file is where you may define all of your closure based console commands.
 * Each closure is bound to a command instance allowing a simple approach to 
 * interacting with each command's IO methods.
 */

/**
 * Command: php artisan inspire
 * Displays a random inspiring quote in the terminal.
 */
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Scheduled Task: Daily WakaTime Sync
 * 
 * This task runs every night at 11:55 PM. It iterates through all users
 * who have a WakaTime API key and synchronizes their coding activity 
 * into local FocusSessions.
 */
Schedule::call(function () {
    // Fetch all users with a valid WakaTime API key
    $users = User::whereNotNull('wakatime_api_key')->get();

    foreach ($users as $user) {
        // Use the WakaTimeService to sync data for the specific user
        app(WakaTimeService::class)->syncToFocusSession($user->id);
    }
})->dailyAt('23:55');