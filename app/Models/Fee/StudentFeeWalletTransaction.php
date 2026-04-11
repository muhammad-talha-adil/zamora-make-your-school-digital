<?php

namespace App\Models\Fee;

use App\Enums\Fee\WalletDirection;
use App\Enums\Fee\WalletTransactionType;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Student Fee Wallet Transaction Model
 * 
 * Advance fee / credit wallet ledger for students.
 */
class StudentFeeWalletTransaction extends Model
{
    protected $fillable = [
        'student_id',
        'transaction_date',
        'transaction_type',
        'direction',
        'amount',
        'reference_type',
        'reference_id',
        'description',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'transaction_type' => WalletTransactionType::class,
        'direction' => WalletDirection::class,
        'amount' => 'decimal:2',
        'reference_id' => 'integer',
    ];

    /**
     * Get the student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Credits only
     */
    public function scopeCredits($query)
    {
        return $query->where('direction', WalletDirection::CREDIT);
    }

    /**
     * Scope: Debits only
     */
    public function scopeDebits($query)
    {
        return $query->where('direction', WalletDirection::DEBIT);
    }

    /**
     * Scope: By transaction type
     */
    public function scopeByType($query, WalletTransactionType $type)
    {
        return $query->where('transaction_type', $type);
    }
}
