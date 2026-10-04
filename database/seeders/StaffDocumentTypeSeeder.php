<?php

namespace Database\Seeders;

use App\Models\Staff\StaffDocumentType;
use Illuminate\Database\Seeder;

/**
 * The starting set implied by the code comment on `staff_documents.kind`
 * since the module's foundation: cnic | degree | contract |
 * police_verification | medical | other.
 */
// Dev/demo data only — not part of the default production seed list.
class StaffDocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $required = ['CNIC', 'Contract'];
        $types = ['CNIC', 'Degree', 'Contract', 'Police Verification', 'Medical', 'Other'];

        foreach ($types as $name) {
            StaffDocumentType::updateOrCreate(
                ['name' => $name],
                ['is_active' => true, 'is_required' => in_array($name, $required, true)]
            );
        }

        // A soft-deleted document type, to exercise reusing a soft-deleted name.
        $retired = StaffDocumentType::firstOrCreate(
            ['name' => 'Retired Document Type'],
            ['is_active' => false]
        );
        $retired->delete();
    }
}
