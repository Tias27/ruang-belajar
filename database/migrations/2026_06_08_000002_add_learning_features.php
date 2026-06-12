<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flashcards', function (Blueprint $table) {
            $table->string('study_status', 20)->default('baru')->after('position')->index();
            $table->unsignedInteger('review_count')->default(0)->after('study_status');
            $table->timestamp('last_reviewed_at')->nullable()->after('review_count');
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('answers');
            $table->json('metadata')->nullable();
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['quiz_id', 'user_id']);
        });

        Schema::create('study_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('document_folders')->cascadeOnDelete();
            $table->longText('content');
            $table->timestamps();

            $table->unique(['user_id', 'document_id']);
            $table->unique(['user_id', 'folder_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_notes');
        Schema::dropIfExists('quiz_attempts');

        Schema::table('flashcards', function (Blueprint $table) {
            $table->dropColumn(['study_status', 'review_count', 'last_reviewed_at']);
        });
    }
};
