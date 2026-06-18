<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('study_rooms', function (Blueprint $table) {
            $table->string('uuid')->nullable()->unique()->after('id');
        });
        
        // Fill existing records with random numbers
        $rooms = \App\Models\StudyRoom::all();
        foreach ($rooms as $room) {
            do {
                $uuid = (string) random_int(100000000000, 999999999999);
            } while (\App\Models\StudyRoom::where('uuid', $uuid)->exists());
            $room->update(['uuid' => $uuid]);
        }
        
        Schema::table('study_rooms', function (Blueprint $table) {
            $table->string('uuid')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_rooms', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
