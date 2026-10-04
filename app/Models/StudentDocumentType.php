<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A kind of paper a school files against a student (B-Form, CNIC copy,
 * birth certificate, previous school leaving certificate, and so on).
 *
 * Mirrors `App\Models\Staff\StaffDocumentType`. `is_required` flags the
 * kinds a school expects on every child's file, so the upload screen can
 * flag what is still missing.
 */
class StudentDocumentType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'is_required',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];
}
