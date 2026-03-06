<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This is a CONSOLIDATED migration that:
     * 1. Creates student_inventory_records (parent table)
     * 2. Creates student_inventory_items (child table)
     * 3. Migrates data from old student_inventory table
     * 4. Updates inventory_returns to reference new structure
     * 
     * Previously this was split across multiple files which caused issues.
     */
    public function up(): void
    {
        // Step 1: Create student_inventory_records table (parent)
        Schema::create('student_inventory_records', function (Blueprint $table) {
            $table->id();
            $table->string('student_inventory_id')->unique();
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('total_discount', 12, 2)->default(0);
            $table->decimal('final_amount', 12, 2)->default(0);
            $table->date('assigned_date');
            $table->enum('status', ['assigned', 'partial_return', 'returned'])->default('assigned');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->foreignId('student_class_id')->nullable()->constrained('school_classes')->onDelete('set null');
            $table->foreignId('student_section_id')->nullable()->constrained('sections')->onDelete('set null');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['campus_id', 'student_id']);
            $table->index(['campus_id', 'status']);
            $table->index(['assigned_date']);
            $table->index(['status', 'assigned_date']);
        });

        // Step 2: Create student_inventory_items table (child)
        Schema::create('student_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_inventory_record_id')
                ->nullable()
                ->constrained('student_inventory_records')
                ->onDelete('cascade');
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->integer('quantity');
            $table->integer('returned_quantity')->default(0);
            $table->decimal('unit_price_snapshot', 10, 2);
            $table->decimal('purchase_rate_snapshot', 10, 2)->nullable();
            $table->string('item_name_snapshot');
            $table->text('description_snapshot')->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->date('assigned_date');
            $table->enum('status', ['assigned', 'partial_return', 'returned']);
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['student_inventory_record_id']);
            $table->index(['campus_id', 'student_id']);
            $table->index(['assigned_date']);
        });

        // Step 3: Migrate data from old student_inventory table to new structure
        if (Schema::hasTable('student_inventory')) {
            $oldRecords = DB::table('student_inventory')
                ->select('*')
                ->get();

            if ($oldRecords->count() > 0) {
                // Group old records by student + date to create parent records
                $grouped = $oldRecords->groupBy(function ($item) {
                    return $item->student_id . '-' . $item->assigned_date;
                });

                foreach ($grouped as $group) {
                    $firstItem = $group->first();
                    
                    // Generate student_inventory_id
                    $year = date('Y', strtotime($firstItem->assigned_date));
                    $count = DB::table('student_inventory_records')
                        ->whereYear('created_at', $year)
                        ->count() + 1;
                    $studentInventoryId = 'SI-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

                    // Calculate totals from items in this group
                    $totalAmount = $group->sum(function ($item) {
                        return ($item->quantity ?? 0) * ($item->unit_price_snapshot ?? 0);
                    });
                    $totalDiscount = $group->sum(function ($item) {
                        $lineTotal = ($item->quantity ?? 0) * ($item->unit_price_snapshot ?? 0);
                        $discountAmount = $item->discount_amount ?? 0;
                        $discountPercentage = $item->discount_percentage ?? 0;
                        return $discountAmount + ($lineTotal * $discountPercentage / 100);
                    });
                    $finalAmount = $totalAmount - $totalDiscount;

                    // Determine overall status based on returned quantities
                    $totalQty = $group->sum('quantity');
                    $totalReturned = $group->sum('returned_quantity');
                    $status = 'assigned';
                    if ($totalReturned >= $totalQty && $totalQty > 0) {
                        $status = 'returned';
                    } elseif ($totalReturned > 0) {
                        $status = 'partial_return';
                    }

                    // Create parent record
                    $recordId = DB::table('student_inventory_records')->insertGetId([
                        'student_inventory_id' => $studentInventoryId,
                        'campus_id' => $firstItem->campus_id,
                        'student_id' => $firstItem->student_id,
                        'student_class_id' => $firstItem->student_class_id ?? null,
                        'student_section_id' => $firstItem->student_section_id ?? null,
                        'total_amount' => $totalAmount,
                        'total_discount' => $totalDiscount,
                        'final_amount' => $finalAmount,
                        'assigned_date' => $firstItem->assigned_date,
                        'status' => $status,
                        'invoice_id' => $firstItem->invoice_id ?? null,
                        'created_at' => $firstItem->created_at ?? now(),
                        'updated_at' => $firstItem->updated_at ?? now(),
                    ]);

                    // Migrate each item to student_inventory_items
                    foreach ($group as $item) {
                        DB::table('student_inventory_items')->insert([
                            'student_inventory_record_id' => $recordId,
                            'campus_id' => $item->campus_id,
                            'student_id' => $item->student_id,
                            'inventory_item_id' => $item->inventory_item_id,
                            'quantity' => $item->quantity,
                            'returned_quantity' => $item->returned_quantity ?? 0,
                            'unit_price_snapshot' => $item->unit_price_snapshot,
                            'purchase_rate_snapshot' => $item->purchase_rate_snapshot ?? null,
                            'item_name_snapshot' => $item->item_name_snapshot,
                            'description_snapshot' => $item->description_snapshot ?? null,
                            'discount_amount' => $item->discount_amount ?? 0,
                            'discount_percentage' => $item->discount_percentage ?? 0,
                            'assigned_date' => $item->assigned_date,
                            'status' => $item->status ?? 'assigned',
                            'invoice_id' => $item->invoice_id ?? null,
                            'created_at' => $item->created_at ?? now(),
                            'updated_at' => $item->updated_at ?? now(),
                        ]);
                    }
                }
            }

            // Drop the old student_inventory table after migration (regardless of whether data existed)
            Schema::dropIfExists('student_inventory');
        }

        // Step 4: Update inventory_returns to reference new structure
        if (Schema::hasTable('inventory_returns')) {
            // Add foreign key to student_inventory_items
            if (!Schema::hasColumn('inventory_returns', 'student_inventory_item_id')) {
                Schema::table('inventory_returns', function (Blueprint $table) {
                    $table->foreignId('student_inventory_item_id')
                        ->nullable()
                        ->constrained('student_inventory_items')
                        ->onDelete('set null')
                        ->after('student_inventory_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop FK from inventory_returns first
        Schema::table('inventory_returns', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_returns', 'student_inventory_item_id')) {
                $table->dropForeign(['student_inventory_item_id']);
                $table->dropColumn('student_inventory_item_id');
            }
        });

        // Drop the new tables
        Schema::dropIfExists('student_inventory_items');
        Schema::dropIfExists('student_inventory_records');
    }
};
