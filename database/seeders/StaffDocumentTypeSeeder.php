<?php

namespace Database\Seeders;

use App\Models\Staff\StaffDocumentType;
use Illuminate\Database\Seeder;

/**
 * The starting set implied by the code comment on `staff_documents.kind`
 * since the module's foundation: cnic | degree | contract |
 * police_verification | medical | other.
 */
class StaffDocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['CNIC', 'Degree', 'Contract', 'Police Verification', 'Medical', 'Other'];

        foreach ($types as $name) {
            StaffDocumentType::updateOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }
    }
}
