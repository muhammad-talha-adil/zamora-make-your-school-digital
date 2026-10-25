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
        Schema::create('session_campuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('academic_sessions')->onDelete('cascade');
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['session_id', 'campus_id']);
            $table->index('campus_id');
        });

        // Every session created before this feature existed was implicitly
        // usable from any campus - attach them all to every existing campus
        // so that behavior doesn't silently change for existing schools.
        $sessionIds = DB::table('academic_sessions')->pluck('id');
        $campusIds = DB::table('campuses')->pluck('id');

        if ($sessionIds->isNotEmpty() && $campusIds->isNotEmpty()) {
            $now = now();
            $rows = [];
            foreach ($sessionIds as $sessionId) {
                foreach ($campusIds as $campusId) {
                    $rows[] = ['session_id' => $sessionId, 'campus_id' => $campusId, 'created_at' => $now, 'updated_at' => $now];
                }
            }
            DB::table('session_campuses')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_campuses');
    }
};
