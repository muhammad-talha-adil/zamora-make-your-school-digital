<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\InventoryItem;
use App\Models\InventoryType;
use Illuminate\Database\Seeder;

class InventoryItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $campuses = Campus::all();

        if ($campuses->isEmpty()) {
            return;
        }

        foreach ($campuses as $campus) {
            $inventoryTypes = InventoryType::where('campus_id', $campus->id)->get();
            
            foreach ($inventoryTypes as $index => $type) {
                $items = $this->getItemsForType($type->name, $index + 1);
                
                foreach ($items as $item) {
                    InventoryItem::firstOrCreate(
                        [
                            'campus_id' => $campus->id,
                            'inventory_type_id' => $type->id,
                            'name' => $item['name'],
                        ],
                        [
                            'description' => $item['description'],
                            'is_active' => $item['is_active'],
                        ]
                    );
                }
            }
        }
    }

    private function getItemsForType(string $type, int $index): array
    {
        $items = [
            'Uniform' => [
                [
                    'name' => 'School Shirt (White)',
                    'description' => 'White cotton shirt for summer',
                    'is_active' => true,
                ],
                [
                    'name' => 'School Shirt (Blue)',
                    'description' => 'Blue cotton shirt for winter',
                    'is_active' => true,
                ],
                [
                    'name' => 'School Trousers (Grey)',
                    'description' => 'Grey trousers for boys',
                    'is_active' => true,
                ],
                [
                    'name' => 'School Skirt (Plaid)',
                    'description' => 'Plaid skirt for girls',
                    'is_active' => true,
                ],
                [
                    'name' => 'School Tie',
                    'description' => 'School tie with logo',
                    'is_active' => true,
                ],
            ],
            'Books' => [
                [
                    'name' => 'Mathematics Textbook Grade 6',
                    'description' => 'NCERT Mathematics textbook for grade 6',
                    'is_active' => true,
                ],
                [
                    'name' => 'English Textbook Grade 6',
                    'description' => 'NCERT English textbook for grade 6',
                    'is_active' => true,
                ],
                [
                    'name' => 'Science Textbook Grade 6',
                    'description' => 'NCERT Science textbook for grade 6',
                    'is_active' => true,
                ],
                [
                    'name' => 'Urdu Textbook Grade 6',
                    'description' => 'NCERT Urdu textbook for grade 6',
                    'is_active' => true,
                ],
                [
                    'name' => 'Social Studies Textbook Grade 6',
                    'description' => 'NCERT Social Studies textbook for grade 6',
                    'is_active' => true,
                ],
            ],
            'Stationery' => [
                [
                    'name' => 'Notebook (100 Pages)',
                    'description' => 'Ruled notebook with 100 pages',
                    'is_active' => true,
                ],
                [
                    'name' => 'Pen (Blue)',
                    'description' => 'Ball point pen blue',
                    'is_active' => true,
                ],
                [
                    'name' => 'Pencil',
                    'description' => 'HB pencil',
                    'is_active' => true,
                ],
                [
                    'name' => 'Eraser',
                    'description' => 'Soft eraser',
                    'is_active' => true,
                ],
                [
                    'name' => 'Sharpener',
                    'description' => 'Metal sharpener',
                    'is_active' => true,
                ],
                [
                    'name' => 'Geometry Box',
                    'description' => 'Complete geometry box with compass',
                    'is_active' => true,
                ],
            ],
            'Sports Equipment' => [
                [
                    'name' => 'Cricket Bat',
                    'description' => 'Willow cricket bat for students',
                    'is_active' => true,
                ],
                [
                    'name' => 'Football',
                    'description' => 'Size 5 football',
                    'is_active' => true,
                ],
                [
                    'name' => 'Badminton Racket',
                    'description' => 'Aluminum badminton racket',
                    'is_active' => true,
                ],
                [
                    'name' => 'Shuttle Cock (Pack of 6)',
                    'description' => 'Feather shuttle cock pack',
                    'is_active' => true,
                ],
                [
                    'name' => 'Jump Rope',
                    'description' => 'Adjustable jump rope',
                    'is_active' => true,
                ],
            ],
            'Lab Equipment' => [
                [
                    'name' => 'Beaker (500ml)',
                    'description' => 'Glass beaker 500ml',
                    'is_active' => true,
                ],
                [
                    'name' => 'Test Tube Set',
                    'description' => 'Set of 6 test tubes',
                    'is_active' => true,
                ],
                [
                    'name' => 'Magnifying Glass',
                    'description' => '10x magnifying glass',
                    'is_active' => true,
                ],
                [
                    'name' => 'Microscope',
                    'description' => 'Basic laboratory microscope',
                    'is_active' => true,
                ],
                [
                    'name' => 'Bunsen Burner',
                    'description' => 'Laboratory Bunsen burner',
                    'is_active' => true,
                ],
            ],
            'Electronics' => [
                [
                    'name' => 'Calculator (Scientific)',
                    'description' => 'Scientific calculator',
                    'is_active' => true,
                ],
                [
                    'name' => 'Projector',
                    'description' => 'LED projector for classroom',
                    'is_active' => true,
                ],
                [
                    'name' => 'Smart Board',
                    'description' => 'Interactive smart board',
                    'is_active' => true,
                ],
                [
                    'name' => 'Speaker',
                    'description' => 'Classroom speaker system',
                    'is_active' => true,
                ],
            ],
            'Furniture' => [
                [
                    'name' => 'Student Desk',
                    'description' => 'Single student desk',
                    'is_active' => true,
                ],
                [
                    'name' => 'Chair',
                    'description' => 'Stackable chair',
                    'is_active' => true,
                ],
                [
                    'name' => 'Whiteboard',
                    'description' => '4x6 feet whiteboard',
                    'is_active' => true,
                ],
                [
                    'name' => 'Bookshelf',
                    'description' => '5-tier bookshelf',
                    'is_active' => true,
                ],
            ],
            'Cleaning Supplies' => [
                [
                    'name' => 'Hand Sanitizer (500ml)',
                    'description' => 'Alcohol based sanitizer',
                    'is_active' => true,
                ],
                [
                    'name' => 'Disinfectant Spray',
                    'description' => '1 liter disinfectant spray',
                    'is_active' => true,
                ],
                [
                    'name' => 'Cleaning Cloth',
                    'description' => 'Microfiber cleaning cloth',
                    'is_active' => true,
                ],
                [
                    'name' => 'Broom',
                    'description' => 'Heavy duty broom',
                    'is_active' => true,
                ],
                [
                    'name' => 'Mop',
                    'description' => 'Spin mop with bucket',
                    'is_active' => true,
                ],
            ],
            'First Aid' => [
                [
                    'name' => 'First Aid Kit',
                    'description' => 'Complete first aid kit',
                    'is_active' => true,
                ],
                [
                    'name' => 'Bandages (Pack)',
                    'description' => 'Pack of 10 bandages',
                    'is_active' => true,
                ],
                [
                    'name' => 'Antiseptic Cream',
                    'description' => 'Soothing antiseptic cream',
                    'is_active' => true,
                ],
                [
                    'name' => 'Thermometer',
                    'description' => 'Digital thermometer',
                    'is_active' => true,
                ],
            ],
            'Miscellaneous' => [
                [
                    'name' => 'School Bag',
                    'description' => 'Waterproof school bag',
                    'is_active' => true,
                ],
                [
                    'name' => 'ID Card Lanyard',
                    'description' => 'School ID card lanyard',
                    'is_active' => true,
                ],
                [
                    'name' => 'Water Bottle',
                    'description' => 'Stainless steel water bottle',
                    'is_active' => true,
                ],
                [
                    'name' => 'Cap',
                    'description' => 'School cap with logo',
                    'is_active' => true,
                ],
            ],
        ];

        return $items[$type] ?? [
            [
                'name' => "{$type} Item " . $index,
                'description' => "Sample {$type} item",
                'is_active' => true,
            ],
        ];
    }
}
