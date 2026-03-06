<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\InventoryItem;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentInventory;
use App\Models\StudentInventoryItem;
use Illuminate\Database\Seeder;

class StudentInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $campuses = Campus::all();
        $students = Student::all();

        if ($campuses->isEmpty() || $students->isEmpty()) {
            return;
        }

        $classes = SchoolClass::all();
        $sections = Section::all();

        foreach ($campuses as $campus) {
            $campusStudents = $students->where('campus_id', $campus->id);
            
            if ($campusStudents->isEmpty()) {
                continue;
            }

            $numAssignments = rand(5, 8);
            
            $assignedStudents = $campusStudents->random(min($numAssignments, $campusStudents->count()));

            foreach ($assignedStudents as $student) {
                $numItems = rand(1, 3);
                
                $items = InventoryItem::where('campus_id', $campus->id)
                    ->inRandomOrder()
                    ->limit($numItems)
                    ->get();

                if ($items->isEmpty()) {
                    continue;
                }

                // Calculate totals
                $totalAmount = 0;
                $totalDiscount = 0;

                // Create inventory items first
                $inventoryItemsData = [];
                
                foreach ($items as $item) {
                    $quantity = rand(1, 3);
                    $unitPrice = rand(200, 1000); // Random unit price
                    $discountPercentage = rand(0, 15); // 0-15% discount
                    $discountAmount = ($unitPrice * $discountPercentage) / 100;
                    $finalUnitPrice = $unitPrice - $discountAmount;
                    $itemTotal = $quantity * $finalUnitPrice;
                    $itemDiscount = $quantity * $discountAmount;

                    $totalAmount += $quantity * $unitPrice;
                    $totalDiscount += $itemDiscount;

                    $inventoryItemsData[] = [
                        'inventory_item_id' => $item->id,
                        'quantity' => $quantity,
                        'returned_quantity' => 0,
                        'unit_price_snapshot' => $unitPrice,
                        'purchase_rate_snapshot' => $unitPrice * 0.7, // 70% of sale rate
                        'item_name_snapshot' => $item->name,
                        'description_snapshot' => $item->description,
                        'discount_amount' => $discountAmount,
                        'discount_percentage' => $discountPercentage,
                        'status' => 'assigned',
                    ];
                }

                // Get student's current class and section
                $studentClass = $classes->isNotEmpty() ? $classes->random() : null;
                $studentSection = $sections->isNotEmpty() ? $sections->random() : null;

                // Create the student inventory record
                $studentInventory = StudentInventory::create([
                    'campus_id' => $campus->id,
                    'student_id' => $student->id,
                    'total_amount' => $totalAmount,
                    'total_discount' => $totalDiscount,
                    'final_amount' => $totalAmount - $totalDiscount,
                    'assigned_date' => now()->subDays(rand(1, 30)),
                    'status' => 'assigned',
                    'student_class_id' => $studentClass?->id,
                    'student_section_id' => $studentSection?->id,
                    'note' => 'Inventory items assigned to student',
                ]);

                // Create student inventory items
                foreach ($inventoryItemsData as $itemData) {
                    StudentInventoryItem::create([
                        'student_inventory_record_id' => $studentInventory->id,
                        'campus_id' => $campus->id,
                        'student_id' => $student->id,
                        'inventory_item_id' => $itemData['inventory_item_id'],
                        'quantity' => $itemData['quantity'],
                        'returned_quantity' => $itemData['returned_quantity'],
                        'unit_price_snapshot' => $itemData['unit_price_snapshot'],
                        'purchase_rate_snapshot' => $itemData['purchase_rate_snapshot'],
                        'item_name_snapshot' => $itemData['item_name_snapshot'],
                        'description_snapshot' => $itemData['description_snapshot'],
                        'discount_amount' => $itemData['discount_amount'],
                        'discount_percentage' => $itemData['discount_percentage'],
                        'assigned_date' => $studentInventory->assigned_date,
                        'status' => $itemData['status'],
                    ]);
                }
            }
        }
    }
}
