<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('summaries', function (Blueprint $table) {
            $table->json('selected_document_ids')->nullable()->after('folder_id');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->json('selected_document_ids')->nullable()->after('folder_id');
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->json('selected_document_ids')->nullable()->after('folder_id');
        });

        Schema::table('study_rooms', function (Blueprint $table) {
            $table->json('selected_document_ids')->nullable()->after('target_id');
        });
    }

    public function down(): void
    {
        Schema::table('summaries', function (Blueprint $table) {
            $table->dropColumn('selected_document_ids');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('selected_document_ids');
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropColumn('selected_document_ids');
        });

        Schema::table('study_rooms', function (Blueprint $table) {
            $table->dropColumn('selected_document_ids');
        });
    }
};
