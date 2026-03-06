<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentFee extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'student_fees';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'campus_id',
        'student_id',
        'session_id',
        'fee_type_id',
        'amount',
        'discount_amount',
        'discount_percentage',
        'total_amount',
        'status',
        'assigned_date',
        'due_date',
        'invoice_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => 'string',
        'assigned_date' => 'date',
        'due_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the campus that owns this student fee.
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the student that owns this fee.
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the session that owns this fee.
     */
    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get the fee type for this fee.
     */
    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }

    /**
     * Get the invoice for this student fee.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the invoice items for this student fee.
     */
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class, 'item_id')->where('item_type', 'fee');
    }
}
