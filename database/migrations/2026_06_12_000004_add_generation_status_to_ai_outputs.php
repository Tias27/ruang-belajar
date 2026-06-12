<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('summaries')) {
            Schema::table('summaries', function (Blueprint $table) {
                if (! Schema::hasColumn('summaries', 'status')) {
                    $table->string('status', 20)->default('completed')->index()->after('raw_response');
                }

                if (! Schema::hasColumn('summaries', 'generation_error')) {
                    $table->text('generation_error')->nullable()->after('status');
                }
            });
        }

        if (Schema::hasTable('quizzes')) {
            Schema::table('quizzes', function (Blueprint $table) {
                if (! Schema::hasColumn('quizzes', 'status')) {
                    $table->string('status', 20)->default('completed')->index()->after('question_count');
                }

                if (! Schema::hasColumn('quizzes', 'generation_error')) {
                    $table->text('generation_error')->nullable()->after('status');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('summaries')) {
            Schema::table('summaries', function (Blueprint $table) {
                if (Schema::hasColumn('summaries', 'generation_error')) {
                    $table->dropColumn('generation_error');
                }

                if (Schema::hasColumn('summaries', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }

        if (Schema::hasTable('quizzes')) {
            Schema::table('quizzes', function (Blueprint $table) {
                if (Schema::hasColumn('quizzes', 'generation_error')) {
                    $table->dropColumn('generation_error');
                }

                if (Schema::hasColumn('quizzes', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }
    }
};
