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
        Schema::create('subject_campuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['subject_id', 'campus_id']);
            $table->index('campus_id');
        });

        // Every subject created before this feature existed was implicitly
        // usable from any campus - attach them all to every existing campus
        // so that behavior doesn't silently change for existing schools.
        $subjectIds = DB::table('subjects')->pluck('id');
        $campusIds = DB::table('campuses')->pluck('id');

        if ($subjectIds->isNotEmpty() && $campusIds->isNotEmpty()) {
            $now = now();
            $rows = [];
            foreach ($subjectIds as $subjectId) {
                foreach ($campusIds as $campusId) {
                    $rows[] = ['subject_id' => $subjectId, 'campus_id' => $campusId, 'created_at' => $now, 'updated_at' => $now];
                }
            }
            DB::table('subject_campuses')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_campuses');
    }
};
