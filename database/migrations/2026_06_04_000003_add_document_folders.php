<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_folders')) {
            Schema::create('document_folders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('documents', 'folder_id')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->foreignId('folder_id')->nullable()->after('user_id')->constrained('document_folders')->cascadeOnDelete();
            });
        }

        foreach (['summaries', 'flashcards', 'quizzes', 'chat_sessions'] as $table) {
            DB::statement("ALTER TABLE {$table} MODIFY document_id BIGINT UNSIGNED NULL");
            if (! Schema::hasColumn($table, 'folder_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->foreignId('folder_id')->nullable()->after('document_id')->constrained('document_folders')->cascadeOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['chat_sessions', 'quizzes', 'flashcards', 'summaries'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('folder_id');
            });
            DB::statement("ALTER TABLE {$table} MODIFY document_id BIGINT UNSIGNED NOT NULL");
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('folder_id');
        });

        Schema::dropIfExists('document_folders');
    }
};
