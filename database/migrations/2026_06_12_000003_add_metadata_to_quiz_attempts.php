<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quiz_attempts') || Schema::hasColumn('quiz_attempts', 'metadata')) {
            return;
        }

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('answers');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('quiz_attempts') || ! Schema::hasColumn('quiz_attempts', 'metadata')) {
            return;
        }

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
