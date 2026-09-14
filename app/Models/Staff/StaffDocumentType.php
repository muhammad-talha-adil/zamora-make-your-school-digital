<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A kind of paper a school files against a member of staff.
 *
 * `staff_documents.kind` stays a plain string — nothing here is a foreign key
 * to it — so a document filed under a kind that is later deactivated or
 * removed, or one of the original free-text values this table was seeded
 * from, is never orphaned.
 */
class StaffDocumentType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
