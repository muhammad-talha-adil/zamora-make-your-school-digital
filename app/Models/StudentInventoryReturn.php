<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentInventoryReturn extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'student_inventory_returns';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'return_id',
        'campus_id',
        'student_id',
        'record_id',
        'total_quantity',
        'total_amount',
        'status',
        'return_date',
        'note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_quantity' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'return_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Generate a unique return ID.
     */
    public static function generateReturnId(): string
    {
        $year = date('Y');
        
        // Get the last return for this year
        $lastReturn = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastReturn && preg_match('/SIR-\d{4}-(\d+)/', $lastReturn->return_id, $matches)) {
            $counter = intval($matches[1]) + 1;
        } else {
            $counter = 1;
        }

        return 'SIR-' . $year . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the campus that owns the return.
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the student that owns the return.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the student inventory record.
     */
    public function studentInventoryRecord(): BelongsTo
    {
        return $this->belongsTo(StudentInventory::class, 'record_id');
    }

    /**
     * Get the return items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(StudentInventoryReturnItem::class, 'return_id');
    }

    /**
     * Get status label.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'returned' => 'Returned',
            'partial' => 'Partial Return',
            default => 'Unknown',
        };
    }

    /**
     * Scope for campus.
     */
    public function scopeForCampus($query, int $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    /**
     * Scope for student.
     */
    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope for date range.
     */
    public function scopeDateRange($query, $fromDate, $toDate)
    {
        return $query->when($fromDate, fn($q) => $q->whereDate('return_date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('return_date', '<=', $toDate));
    }
}
