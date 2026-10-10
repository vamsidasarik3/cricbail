<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->uuid('attempt_token')->nullable()->unique()->after('id');
            $table->timestamp('started_at')->nullable()->after('attempted_at');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->string('status', 20)->default('in_progress')->index()->after('completed_at');
            $table->json('answers')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn(['attempt_token', 'started_at', 'completed_at', 'status', 'answers']);
        });
    }
};
