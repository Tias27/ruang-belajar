<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->unique()->after('name');
            });
        }

        DB::table('users')
            ->whereNull('username')
            ->orderBy('id')
            ->get(['id', 'email', 'name'])
            ->each(function ($user) {
                $base = Str::of($user->email ?: $user->name ?: 'user-'.$user->id)
                    ->before('@')
                    ->lower()
                    ->replaceMatches('/[^a-z0-9_]+/', '_')
                    ->trim('_')
                    ->value() ?: 'user_'.$user->id;

                $username = $base;
                $counter = 1;

                while (DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                    $username = $base.'_'.$counter;
                    $counter++;
                }

                DB::table('users')->where('id', $user->id)->update(['username' => $username]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('username');
            });
        }
    }
};
