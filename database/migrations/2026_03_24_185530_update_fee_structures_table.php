<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            // Add new columns that are missing in the old schema
            $table->string('title')->nullable()->after('id');
            $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('cascade')->after('class_id');
            $table->boolean('is_default')->default(false)->after('section_id');
            $table->date('effective_from')->nullable()->after('is_default');
            $table->date('effective_to')->nullable()->after('effective_from');
            $table->string('status')->default('active')->after('effective_to');
            $table->text('notes')->nullable()->after('status');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->after('notes');

            // Drop old columns that are no longer needed
            $table->dropForeign(['fee_type_id']);
            $table->dropColumn('fee_type_id');
            $table->dropColumn('amount');
            $table->dropColumn('due_day_of_month');
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            // Add back the old columns
            $table->foreignId('fee_type_id')->nullable()->constrained('fee_types')->onDelete('cascade');
            $table->decimal('amount', 10, 2)->nullable();
            $table->integer('due_day_of_month')->nullable();
            $table->boolean('is_active')->default(true);

            // Drop new columns
            $table->dropForeign(['section_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn([
                'title',
                'section_id',
                'is_default',
                'effective_from',
                'effective_to',
                'status',
                'notes',
                'created_by',
            ]);
        });
    }
};
