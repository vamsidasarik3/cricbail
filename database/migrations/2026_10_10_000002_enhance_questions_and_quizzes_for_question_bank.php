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
        // 1. Enhance questions table for reusable 100-question bank
        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedBigInteger('quiz_id')->nullable()->change();
            $table->string('topic', 100)->nullable()->after('quiz_id')->index();
            $table->string('difficulty', 20)->default('medium')->after('topic')->index();
            $table->boolean('is_active')->default(true)->after('display_order')->index();
            $table->string('source_reference')->nullable()->after('explanation');
        });

        // 2. Enhance quizzes table with quiz_type
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('quiz_type', 20)->default('fixed')->after('status')->index(); // 'fixed', 'random_bank'
        });

        // 3. Enhance quiz_attempts table to track randomized question selections
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->json('question_ids')->nullable()->after('answers');
            $table->json('option_manifest')->nullable()->after('question_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn(['question_ids', 'option_manifest']);
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('quiz_type');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['topic', 'difficulty', 'is_active', 'source_reference']);
        });
    }
};
