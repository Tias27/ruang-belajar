<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quizzes') || Schema::hasColumn('quizzes', 'question_type')) {
            return;
        }

        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('question_type', 30)->default('multiple_choice')->after('title');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('quizzes') || ! Schema::hasColumn('quizzes', 'question_type')) {
            return;
        }

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('question_type');
        });
    }
};
