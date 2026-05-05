<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\StudentFee;
use App\Models\StudentInventory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeeService
{
    /**
     * Generate unique invoice number with race condition protection.
     * Uses DB lock to prevent concurrent generation.
     * Format: CAMPUS{campus_id}-INV-{sequence}
     */
    public function generateInvoiceNumber(int $campusId, int $sessionId): string
    {
        return DB::transaction(function () use ($campusId, $sessionId) {
            // PRODUCTION FIX: Use lock to prevent race conditions
            $lastInvoice = Invoice::where('campus_id', $campusId)
                ->where('session_id', $sessionId)
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $sequence = $lastInvoice ? (intval(substr($lastInvoice->invoice_number, -6)) + 1) : 1;

            return sprintf('CAMPUS%d-INV-%06d', $campusId, $sequence);
        });
    }

    /**
     * Create invoice from unpaid fees and inventory.
     */
    public function createInvoice(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            // PRODUCTION FIX: Validate student belongs to campus
            $student = User::where('id', $data['student_id'])
                ->where('campus_id', $data['campus_id'])
                ->firstOrFail();

            // PRODUCTION FIX: Get unpaid fees only (invoice_id IS NULL, status = pending)
            $feeQuery = StudentFee::where('student_id', $data['student_id'])
                ->where('session_id', $data['session_id'])
                ->where('status', 'pending')
                ->whereNull('invoice_id');

            if (! empty($data['fee_ids'])) {
                $feeQuery->whereIn('id', $data['fee_ids']);
            }

            $unpaidFees = $feeQuery->get();

            // PRODUCTION FIX: Get uninvoiced inventory only (invoice_id IS NULL, status != returned)
            $inventoryQuery = StudentInventory::where('student_id', $data['student_id'])
                ->whereNull('invoice_id')
                ->whereIn('status', ['assigned', 'partial_return']);

            if (! empty($data['inventory_ids'])) {
                $inventoryQuery->whereIn('id', $data['inventory_ids']);
            }

            $uninvoicedInventory = $inventoryQuery->get();

            if ($unpaidFees->isEmpty() && $uninvoicedInventory->isEmpty()) {
                throw new \Exception('No unpaid fees or uninvoiced inventory found');
            }

            // PRODUCTION FIX: Calculate totals using snapshot/final prices
            $totalAmount = $unpaidFees->sum('total_amount') + $uninvoicedInventory->sum('remainingValue');
            $discountAmount = $data['discount_amount'] ?? 0;
            $finalTotal = max(0, $totalAmount - $discountAmount);

            // Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber($data['campus_id'], $data['session_id']);

            // Create invoice
            $invoice = Invoice::create([
                'campus_id' => $data['campus_id'],
                'student_id' => $data['student_id'],
                'session_id' => $data['session_id'],
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $data['invoice_date'],
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'paid_amount' => 0,
                'status' => 'pending',
            ]);

            // PRODUCTION FIX: Create invoice items and link records
            foreach ($unpaidFees as $fee) {
                // Create snapshot for reporting
                $discountSnapshot = [
                    'amount' => $fee->discount_amount,
                    'percentage' => $fee->discount_percentage,
                    'final_amount' => $fee->total_amount,
                ];

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => 'fee',
                    'item_id' => $fee->id,
                    'description' => $fee->feeType->name ?? 'Fee',
                    'quantity' => 1,
                    'unit_price' => $fee->amount,
                    'discount_amount' => $fee->discount_amount,
                    'discount_percentage' => $fee->discount_percentage,
                    'total_amount' => $fee->total_amount,
                    'discount_snapshot' => json_encode($discountSnapshot),
                ]);

                // PRODUCTION FIX: Link fee to invoice
                $fee->update(['invoice_id' => $invoice->id]);
            }

            foreach ($uninvoicedInventory as $inventory) {
                // PRODUCTION FIX: Calculate only remaining value for partial returns
                $remainingValue = $inventory->remainingValue();

                $discountSnapshot = [
                    'amount' => $inventory->discount_amount,
                    'percentage' => $inventory->discount_percentage,
                    'per_unit' => $inventory->getDiscountPerUnit(),
                    'final_unit_price' => $inventory->getFinalUnitPrice(),
                ];

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => 'inventory',
                    'item_id' => $inventory->id,
                    'description' => $inventory->item_name_snapshot ?? 'Inventory Item',
                    'quantity' => $inventory->remainingQuantity(),
                    'unit_price' => $inventory->getFinalUnitPrice(),
                    'discount_amount' => $inventory->getTotalDiscountAmount(),
                    'discount_percentage' => $inventory->discount_percentage,
                    'total_amount' => $remainingValue,
                    'discount_snapshot' => json_encode($discountSnapshot),
                ]);

                // PRODUCTION FIX: Link inventory to invoice
                $inventory->update(['invoice_id' => $invoice->id]);
            }

            Log::info('Invoice created', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoiceNumber,
                'total_amount' => $finalTotal,
                'fees_count' => $unpaidFees->count(),
                'inventory_count' => $uninvoicedInventory->count(),
            ]);

            return $invoice->fresh(['campus', 'session', 'student', 'invoiceItems']);
        });
    }

    /**
     * Cancel invoice safely.
     */
    public function cancelInvoice(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            // PRODUCTION FIX: Prevent cancellation if already paid
            if ($invoice->status === 'paid') {
                throw new \Exception('Cannot cancel a fully paid invoice');
            }

            // PRODUCTION FIX: Prevent cancellation if partially paid (optional safety)
            if ($invoice->status === 'partial') {
                throw new \Exception('Cannot cancel a partially paid invoice. Please refund payment first.');
            }

            // PRODUCTION FIX: Unlink fees
            StudentFee::where('invoice_id', $invoice->id)
                ->update(['invoice_id' => null, 'status' => 'pending']);

            // PRODUCTION FIX: Unlink inventory
            StudentInventory::where('invoice_id', $invoice->id)
                ->update(['invoice_id' => null]);

            // PRODUCTION FIX: Soft delete invoice items (not hard delete)
            InvoiceItem::where('invoice_id', $invoice->id)->delete();

            // Update invoice status
            $invoice->update(['status' => 'cancelled']);

            Log::info('Invoice cancelled', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ]);

            return $invoice->fresh();
        });
    }

    /**
     * Process payment with concurrency safety.
     */
    public function processPayment(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // PRODUCTION FIX: Lock invoice row for update
            $invoice = Invoice::lockForUpdate()->findOrFail($data['invoice_id']);

            // Prevent payment on cancelled invoice
            if ($invoice->status === 'cancelled') {
                throw new \Exception('Cannot accept payment for cancelled invoice');
            }

            // PRODUCTION FIX: Validate payment amount
            $remainingBalance = $invoice->total_amount - $invoice->paid_amount;

            if ($data['amount'] > $remainingBalance) {
                throw new \Exception("Payment amount exceeds remaining balance. Remaining: {$remainingBalance}");
            }

            // Create payment
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'payment_date' => $data['payment_date'],
                'payment_mode' => $data['payment_mode'],
                'amount' => $data['amount'],
                'reference_number' => $data['reference_number'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            // PRODUCTION FIX: Update invoice totals
            $newPaidAmount = $invoice->paid_amount + $data['amount'];
            $newStatus = 'partial';

            if ($newPaidAmount >= $invoice->total_amount) {
                $newStatus = 'paid';
            }

            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'status' => $newStatus,
            ]);

            // PRODUCTION FIX: Update linked student fees status
            if ($newStatus === 'paid') {
                StudentFee::where('invoice_id', $invoice->id)
                    ->update(['status' => 'paid']);
            }

            Log::info('Payment processed', [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $data['amount'],
                'new_status' => $newStatus,
            ]);

            return [
                'payment' => $payment->fresh(),
                'invoice' => $invoice->fresh(),
            ];
        });
    }

    /**
     * Get invoice preview with all pending items.
     */
    public function getInvoicePreview(int $campusId, int $studentId, int $sessionId): array
    {
        // PRODUCTION FIX: Validate student belongs to campus
        $student = User::where('id', $studentId)
            ->where('campus_id', $campusId)
            ->firstOrFail();

        // Get unpaid fees
        $unpaidFees = StudentFee::with('feeType')
            ->where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->where('status', 'pending')
            ->whereNull('invoice_id')
            ->get();

        // PRODUCTION FIX: Get uninvoiced inventory (not returned)
        $uninvoicedInventory = StudentInventory::with('inventoryItem')
            ->where('student_id', $studentId)
            ->whereNull('invoice_id')
            ->whereIn('status', ['assigned', 'partial_return'])
            ->get();

        $totalFees = $unpaidFees->sum('total_amount');
        $totalInventory = $uninvoicedInventory->sum('remainingValue');
        $grandTotal = $totalFees + $totalInventory;

        return [
            'fees' => $unpaidFees,
            'inventory' => $uninvoicedInventory,
            'summary' => [
                'fees_count' => $unpaidFees->count(),
                'fees_total' => $totalFees,
                'inventory_count' => $uninvoicedInventory->count(),
                'inventory_total' => $totalInventory,
                'grand_total' => $grandTotal,
            ],
        ];
    }
}
