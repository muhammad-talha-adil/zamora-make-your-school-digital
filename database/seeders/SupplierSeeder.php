<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
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

        $suppliersData = $this->getSuppliersData();

        foreach ($campuses as $campus) {
            foreach ($suppliersData as $supplier) {
                Supplier::firstOrCreate(
                    [
                        'campus_id' => $campus->id,
                        'name' => $supplier['name'],
                    ],
                    [
                        'contact_person' => $supplier['contact_person'],
                        'phone' => $supplier['phone'],
                        'email' => $supplier['email'],
                        'address' => $supplier['address'],
                        'tax_number' => $supplier['tax_number'] ?? null,
                        'opening_balance' => $supplier['opening_balance'] ?? 0,
                        'is_active' => $supplier['is_active'],
                    ]
                );
            }
        }
    }

    private function getSuppliersData(): array
    {
        return [
            [
                'name' => 'ABC Supplies Ltd.',
                'contact_person' => 'Ahmed Khan',
                'phone' => '+92-300-1234567',
                'email' => 'ahmed@abcsupplies.com',
                'address' => '123 Trade Center, Karachi',
                'tax_number' => 'TAX-0012345',
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'XYZ Trading Co.',
                'contact_person' => 'Muhammad Ali',
                'phone' => '+92-321-7654321',
                'email' => 'ali@xyztrading.com',
                'address' => '456 Business Avenue, Lahore',
                'tax_number' => 'TAX-0012346',
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'School Essentials Inc.',
                'contact_person' => 'Fatima Siddiqui',
                'phone' => '+92-333-9876543',
                'email' => 'fatima@schoolessentials.com',
                'address' => '789 Education Lane, Islamabad',
                'tax_number' => 'TAX-0012347',
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Education Supplies Pakistan',
                'contact_person' => 'Bilal Ahmed',
                'phone' => '+92-342-5566778',
                'email' => 'bilal@edusupplypk.com',
                'address' => '321 Knowledge Road, Karachi',
                'tax_number' => 'TAX-0012348',
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Global Stationery House',
                'contact_person' => 'Sana Ullah',
                'phone' => '+92-345-1122334',
                'email' => 'sana@globalstationery.com',
                'address' => '654 Writing Street, Lahore',
                'tax_number' => 'TAX-0012349',
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Sports Equipment Direct',
                'contact_person' => 'Khalid Hussain',
                'phone' => '+92-346-9988776',
                'email' => 'khalid@sportsdirect.com',
                'address' => '987 Athletic Avenue, Karachi',
                'tax_number' => 'TAX-0012350',
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Lab Supplies International',
                'contact_person' => 'Dr. Ayesha Malik',
                'phone' => '+92-347-4455667',
                'email' => 'ayesha@labsupplyint.com',
                'address' => '147 Science Park, Islamabad',
                'tax_number' => 'TAX-0012351',
                'opening_balance' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Uniform Manufacturers Association',
                'contact_person' => 'Rashid Mehmood',
                'phone' => '+92-348-6677889',
                'email' => 'rashid@uma-pk.com',
                'address' => '258 Textile Market, Faisalabad',
                'tax_number' => 'TAX-0012352',
                'opening_balance' => 0,
                'is_active' => true,
            ],
        ];
    }
}
