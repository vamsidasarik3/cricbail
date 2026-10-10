<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Categories
        if (Schema::hasTable('categories') && !Schema::hasColumn('categories', 'uuid')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id')->index();
            });

            // Backfill UUIDs for existing categories
            $categories = DB::table('categories')->whereNull('uuid')->get();
            foreach ($categories as $cat) {
                $generatedUuid = (string) Str::uuid();
                DB::table('categories')->where('id', $cat->id)->update(['uuid' => $generatedUuid]);
            }

            Schema::table('categories', function (Blueprint $table) {
                $table->unique('uuid');
            });
        }

        // 2. Articles
        if (Schema::hasTable('articles') && !Schema::hasColumn('articles', 'uuid')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id')->index();
            });

            // Backfill UUIDs for existing articles
            $articles = DB::table('articles')->whereNull('uuid')->get();
            foreach ($articles as $art) {
                $generatedUuid = (string) Str::uuid();
                DB::table('articles')->where('id', $art->id)->update(['uuid' => $generatedUuid]);
            }

            Schema::table('articles', function (Blueprint $table) {
                $table->unique('uuid');
            });
        }

        // 3. Quizzes
        if (Schema::hasTable('quizzes') && !Schema::hasColumn('quizzes', 'uuid')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id')->index();
            });

            // Backfill UUIDs for existing quizzes
            $quizzes = DB::table('quizzes')->whereNull('uuid')->get();
            foreach ($quizzes as $quiz) {
                $generatedUuid = (string) Str::uuid();
                DB::table('quizzes')->where('id', $quiz->id)->update(['uuid' => $generatedUuid]);
            }

            Schema::table('quizzes', function (Blueprint $table) {
                $table->unique('uuid');
            });
        }

        // 4. Questions
        if (Schema::hasTable('questions') && !Schema::hasColumn('questions', 'uuid')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id')->index();
            });

            // Backfill UUIDs for existing questions
            $questions = DB::table('questions')->whereNull('uuid')->get();
            foreach ($questions as $q) {
                $generatedUuid = (string) Str::uuid();
                DB::table('questions')->where('id', $q->id)->update(['uuid' => $generatedUuid]);
            }

            Schema::table('questions', function (Blueprint $table) {
                $table->unique('uuid');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('questions') && Schema::hasColumn('questions', 'uuid')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropColumn('uuid');
            });
        }

        if (Schema::hasTable('quizzes') && Schema::hasColumn('quizzes', 'uuid')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dropColumn('uuid');
            });
        }

        if (Schema::hasTable('articles') && Schema::hasColumn('articles', 'uuid')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropColumn('uuid');
            });
        }

        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'uuid')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('uuid');
            });
        }
    }
};
