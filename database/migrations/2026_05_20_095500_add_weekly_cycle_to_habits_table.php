<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Weekly Cycle Columns to Habits Table
 *
 * Adds columns to support weekly habit cycle tracking and auto-reset:
 * - week_start: the date when the current weekly cycle started
 * - last_reset_at: the last time the habit was reset (for auto-reset guard)
 * - archived_weeks: JSON log of past weekly performance for history/analytics
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->date('week_start')->nullable()->after('completed_dates');
            $table->timestamp('last_reset_at')->nullable()->after('week_start');
            $table->json('archived_weeks')->nullable()->after('last_reset_at');
        });
    }

    public function down(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->dropColumn(['week_start', 'last_reset_at', 'archived_weeks']);
        });
    }
};
