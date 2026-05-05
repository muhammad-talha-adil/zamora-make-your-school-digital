<?php

namespace App\Models\Ledger;

use App\Models\Campus;
use App\Models\Student;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ledger extends Model
{
    use SoftDeletes;

    protected $table = 'ledgers';

    protected $fillable = [
        'ledger_number',
        'transaction_type',
        'transaction_date',
        'amount',
        'description',
        'reference_type',
        'reference_id',
        'category_id',
        'payment_method',
        'reference_number',
        'campus_id',
        'student_id',
        'supplier_id',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Polymorphic relationship - links to any table
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(LedgerCategory::class, 'category_id');
    }

    /**
     * Get the campus
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the student (for income)
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the supplier (for expense)
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Income transactions only
     */
    public function scopeIncome($query)
    {
        return $query->where('transaction_type', 'INCOME');
    }

    /**
     * Scope: Expense transactions only
     */
    public function scopeExpense($query)
    {
        return $query->where('transaction_type', 'EXPENSE');
    }

    /**
     * Scope: By date
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('transaction_date', $date);
    }

    /**
     * Scope: By date range
     */
    public function scopeDateRange($query, $fromDate, $toDate)
    {
        return $query->whereBetween('transaction_date', [$fromDate, $toDate]);
    }

    /**
     * Scope: By campus
     */
    public function scopeForCampus($query, $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    /**
     * Check if transaction is income
     */
    public function isIncome(): bool
    {
        return $this->transaction_type === 'INCOME';
    }

    /**
     * Check if transaction is expense
     */
    public function isExpense(): bool
    {
        return $this->transaction_type === 'EXPENSE';
    }
}
