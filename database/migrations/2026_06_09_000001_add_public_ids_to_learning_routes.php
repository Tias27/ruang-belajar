<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private array $tables = [
        'documents',
        'document_folders',
        'summaries',
        'flashcards',
        'quizzes',
        'chat_sessions',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'public_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('public_id', 32)->nullable()->after('id');
            });

            DB::table($table)
                ->whereNull('public_id')
                ->orderBy('id')
                ->get(['id'])
                ->each(fn ($row) => DB::table($table)->where('id', $row->id)->update(['public_id' => (string) Str::ulid()]));

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->unique('public_id', $table.'_public_id_unique');
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'public_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropUnique($table.'_public_id_unique');
                $blueprint->dropColumn('public_id');
            });
        }
    }
};
