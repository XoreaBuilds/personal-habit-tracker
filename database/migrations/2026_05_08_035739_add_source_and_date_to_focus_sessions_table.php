<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('focus_sessions', function (Blueprint $table) {
            $table->string('source')->default('manual')->after('habit_id');
            $table->date('date')->nullable()->after('source');

            // Optional: add a unique constraint to prevent duplicate entries for the same day/source
            $table->unique(['user_id', 'date', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('focus_sessions', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'date', 'source']);
            $table->dropColumn(['source', 'date']);
        });
    }
};
