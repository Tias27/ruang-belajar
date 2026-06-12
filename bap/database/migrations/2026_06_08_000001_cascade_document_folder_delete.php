<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('documents') || ! Schema::hasColumn('documents', 'folder_id')) {
            return;
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
            $table->foreign('folder_id')->references('id')->on('document_folders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('documents') || ! Schema::hasColumn('documents', 'folder_id')) {
            return;
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
            $table->foreign('folder_id')->references('id')->on('document_folders')->nullOnDelete();
        });
    }
};
