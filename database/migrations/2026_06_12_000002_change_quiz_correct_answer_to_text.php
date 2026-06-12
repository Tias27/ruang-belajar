<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quiz_questions') || ! Schema::hasColumn('quiz_questions', 'correct_answer')) {
            return;
        }

        DB::statement('ALTER TABLE quiz_questions MODIFY correct_answer TEXT NOT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('quiz_questions') || ! Schema::hasColumn('quiz_questions', 'correct_answer')) {
            return;
        }

        DB::statement('ALTER TABLE quiz_questions MODIFY correct_answer VARCHAR(255) NOT NULL');
    }
};
