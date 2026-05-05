<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Session;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Generate a unique invoice number.
     *
     * Format: INV-{CAMPUS_CODE}-{YEAR}{MONTH}-{SEQUENCE:04}
     * Example: INV-MAIN-202501-0001
     */
    public function generateInvoiceNumber(Campus $campus, Session $session): string
    {
        $campusCode = $this->getCampusCode($campus);
        $year = now()->year;
        $month = now()->format('m');

        // Get the last invoice number for this campus and session in current month
        $prefix = "INV-{$campusCode}-{$year}{$month}-";

        $lastInvoice = Invoice::where('campus_id', $campus->id)
            ->where('session_id', $session->id)
            ->where('invoice_number', 'LIKE', $prefix.'%')
            ->max('invoice_number');

        if ($lastInvoice) {
            // Extract sequence and increment
            $sequence = (int) substr($lastInvoice, -4) + 1;
        } else {
            $sequence = 1;
        }

        return $prefix.str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a receipt number.
     *
     * Format: RCP-{CAMPUS_CODE}-{YEAR}{MONTH}-{SEQUENCE:04}
     * Example: RCP-MAIN-202501-0001
     */
    public function generateReceiptNumber(Campus $campus, Session $session): string
    {
        $campusCode = $this->getCampusCode($campus);
        $year = now()->year;
        $month = now()->format('m');

        $prefix = "RCP-{$campusCode}-{$year}{$month}-";

        // Get max receipt number (assuming receipts table has invoice_number field)
        $lastReceipt = Invoice::where('campus_id', $campus->id)
            ->where('session_id', $session->id)
            ->where('invoice_number', 'LIKE', $prefix.'%')
            ->max('invoice_number');

        if ($lastReceipt) {
            $sequence = (int) substr($lastReceipt, -4) + 1;
        } else {
            $sequence = 1;
        }

        return $prefix.str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get campus code for invoice numbering.
     */
    protected function getCampusCode(Campus $campus): string
    {
        if ($campus->campusType) {
            // Use campus type abbreviation if available
            return strtoupper(substr($campus->campusType->name, 0, 4));
        }

        // Use first 4 characters of campus name
        return strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $campus->name), 0, 4));
    }

    /**
     * Create an invoice with items atomically.
     */
    public function createInvoice(array $invoiceData, array $items, Session $session): Invoice
    {
        return DB::transaction(function () use ($invoiceData, $items, $session) {
            $campus = Campus::findOrFail($invoiceData['campus_id']);

            // Generate invoice number
            $invoiceData['invoice_number'] = $this->generateInvoiceNumber($campus, $session);
            $invoiceData['session_id'] = $session->id;

            // Calculate totals
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += ($item['unit_price'] * $item['quantity']) - ($item['discount_amount'] ?? 0);
            }

            $invoiceData['total_amount'] = $totalAmount;
            $invoiceData['discount_amount'] = array_sum(array_column($items, 'discount_amount'));

            // Create invoice
            $invoice = Invoice::create($invoiceData);

            // Create invoice items
            foreach ($items as $item) {
                $invoice->invoiceItems()->create([
                    'item_type' => $item['item_type'] ?? 'inventory',
                    'item_id' => $item['item_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'discount_percentage' => $item['discount_percentage'] ?? 0,
                    'total_amount' => ($item['unit_price'] * $item['quantity']) - ($item['discount_amount'] ?? 0),
                    'discount_snapshot' => [
                        'discount_amount' => $item['discount_amount'] ?? 0,
                        'discount_percentage' => $item['discount_percentage'] ?? 0,
                        'captured_at' => now()->toIso8601String(),
                    ],
                ]);
            }

            return $invoice;
        });
    }

    /**
     * Record a payment and update invoice status.
     */
    public function recordPayment(Invoice $invoice, array $paymentData): Payment
    {
        return DB::transaction(function () use ($invoice, $paymentData) {
            $campus = $invoice->campus;
            $session = $invoice->session;

            // Generate receipt number if not provided
            if (! isset($paymentData['receipt_number'])) {
                $paymentData['receipt_number'] = $this->generateReceiptNumber($campus, $session);
            }

            // Create payment
            $payment = $invoice->payments()->create([
                'payment_date' => $paymentData['payment_date'] ?? now()->toDateString(),
                'payment_mode' => $paymentData['payment_mode'],
                'amount' => $paymentData['amount'],
                'reference_number' => $paymentData['reference_number'] ?? null,
                'note' => $paymentData['note'] ?? null,
            ]);

            // Update invoice paid amount and status
            $invoice->paid_amount += $paymentData['amount'];

            if ($invoice->paid_amount >= $invoice->total_amount) {
                $invoice->status = 'paid';
            } elseif ($invoice->paid_amount > 0) {
                $invoice->status = 'partial';
            }

            $invoice->save();

            return $payment;
        });
    }

    /**
     * Get invoice by number.
     */
    public function getByInvoiceNumber(string $invoiceNumber, ?int $campusId = null): ?Invoice
    {
        $query = Invoice::where('invoice_number', $invoiceNumber);

        if ($campusId) {
            $query->where('campus_id', $campusId);
        }

        return $query->first();
    }

    /**
     * Calculate outstanding balance for an invoice.
     */
    public function getOutstandingBalance(Invoice $invoice): float
    {
        return max(0, $invoice->total_amount - $invoice->paid_amount);
    }

    /**
     * Check if invoice is fully paid.
     */
    public function isFullyPaid(Invoice $invoice): bool
    {
        return $invoice->paid_amount >= $invoice->total_amount;
    }
}
