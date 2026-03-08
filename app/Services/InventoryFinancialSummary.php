<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\StudentInventory;
use App\Models\StudentInventoryItem;
use App\Models\InventoryStock;
use App\Models\InventoryValuation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Inventory Financial Summary Service
 * 
 * Provides comprehensive financial reports for inventory including:
 * - Purchase expenses
 * - Sales revenue
 * - Profit/Loss calculations
 * - Accounts payable/receivable
 */
class InventoryFinancialSummary
{
    /**
     * Get purchase expense report for a date range.
     */
    public function getPurchaseExpenseReport(int $campusId, ?Carbon $fromDate = null, ?Carbon $toDate = null): array
    {
        $query = Purchase::forCampus($campusId);

        if ($fromDate) {
            $query->whereDate('purchase_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('purchase_date', '<=', $toDate);
        }

        $purchases = $query->get();

        $totalPurchaseAmount = $purchases->sum('total_amount');
        $totalPaidAmount = $purchases->sum('paid_amount');
        $totalDueAmount = $totalPurchaseAmount - $totalPaidAmount;

        $byStatus = [
            'paid' => $purchases->where('payment_status', Purchase::PAYMENT_STATUS_PAID)->sum('total_amount'),
            'partial' => $purchases->where('payment_status', Purchase::PAYMENT_STATUS_PARTIAL)->sum('total_amount'),
            'unpaid' => $purchases->where('payment_status', Purchase::PAYMENT_STATUS_UNPAID)->sum('total_amount'),
        ];

        return [
            'total_purchases' => $purchases->count(),
            'total_purchase_amount' => $totalPurchaseAmount,
            'total_paid_amount' => $totalPaidAmount,
            'total_due_amount' => $totalDueAmount,
            'by_status' => $byStatus,
            'purchases' => $purchases->map(fn($p) => [
                'id' => $p->id,
                'purchase_id' => $p->purchase_id,
                'purchase_date' => $p->purchase_date?->format('Y-m-d'),
                'supplier_name' => $p->supplier?->name,
                'total_amount' => $p->total_amount,
                'paid_amount' => $p->paid_amount,
                'due_amount' => $p->due_amount,
                'payment_status' => $p->payment_status,
                'due_date' => $p->due_date?->format('Y-m-d'),
                'is_overdue' => $p->isOverdue(),
            ]),
        ];
    }

    /**
     * Get sales revenue report for a date range.
     */
    public function getSalesRevenueReport(int $campusId, ?Carbon $fromDate = null, ?Carbon $toDate = null): array
    {
        $query = StudentInventory::forCampus($campusId);

        if ($fromDate) {
            $query->whereDate('assigned_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('assigned_date', '<=', $toDate);
        }

        $sales = $query->with(['items', 'student', 'invoice'])->get();

        $totalRevenue = $sales->sum('final_amount');
        $totalDiscount = $sales->sum('total_discount');
        $totalOriginalAmount = $sales->sum('total_amount');

        $byStatus = [
            'assigned' => $sales->where('status', StudentInventory::STATUS_ASSIGNED)->count(),
            'partial_return' => $sales->where('status', StudentInventory::STATUS_PARTIAL_RETURN)->count(),
            'returned' => $sales->where('status', StudentInventory::STATUS_RETURNED)->count(),
        ];

        // Calculate profit
        $totalCostOfGoodsSold = 0;
        $totalProfit = 0;
        
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $purchaseRate = (float) ($item->purchase_rate_snapshot ?? 0);
                $salePrice = $item->getFinalUnitPrice();
                $quantity = $item->quantity;
                
                $totalCostOfGoodsSold += $purchaseRate * $quantity;
                $totalProfit += ($salePrice - $purchaseRate) * $quantity;
            }
        }

        return [
            'total_sales' => $sales->count(),
            'total_revenue' => $totalRevenue,
            'total_discount' => $totalDiscount,
            'total_original_amount' => $totalOriginalAmount,
            'total_cost_of_goods_sold' => $totalCostOfGoodsSold,
            'total_profit' => $totalProfit,
            'profit_margin' => $totalCostOfGoodsSold > 0 ? ($totalProfit / $totalCostOfGoodsSold) * 100 : 0,
            'by_status' => $byStatus,
        ];
    }

    /**
     * Get profit and loss report.
     */
    public function getProfitLossReport(int $campusId, ?Carbon $fromDate = null, ?Carbon $toDate = null): array
    {
        $purchaseData = $this->getPurchaseExpenseReport($campusId, $fromDate, $toDate);
        $salesData = $this->getSalesRevenueReport($campusId, $fromDate, $toDate);

        $totalExpenses = $purchaseData['total_purchase_amount'];
        $totalRevenue = $salesData['total_revenue'];
        $grossProfit = $totalRevenue - $purchaseData['total_cost_of_goods_sold'] ?? 0;
        $netProfit = $totalRevenue - $totalExpenses;

        return [
            'period' => [
                'from' => $fromDate?->format('Y-m-d'),
                'to' => $toDate?->format('Y-m-d'),
            ],
            'revenue' => [
                'total_sales_revenue' => $totalRevenue,
                'total_discount_given' => $salesData['total_discount'],
            ],
            'expenses' => [
                'total_purchase_expenses' => $totalExpenses,
                'amount_paid' => $purchaseData['total_paid_amount'],
                'amount_due' => $purchaseData['total_due_amount'],
            ],
            'cost_of_goods_sold' => $purchaseData['total_cost_of_goods_sold'] ?? 0,
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
            'profit_margin_percentage' => $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0,
            'is_profitable' => $netProfit > 0,
        ];
    }

    /**
     * Get accounts payable summary.
     */
    public function getAccountsPayableSummary(int $campusId): array
    {
        $unpaidPurchases = Purchase::forCampus($campusId)
            ->unpaid()
            ->get();

        $partialPurchases = Purchase::forCampus($campusId)
            ->partial()
            ->get();

        $overduePurchases = Purchase::forCampus($campusId)
            ->overdue()
            ->get();

        return [
            'total_unpaid' => $unpaidPurchases->sum('total_amount'),
            'total_partial' => $partialPurchases->sum('due_amount'),
            'total_due' => $unpaidPurchases->sum('total_amount') + $partialPurchases->sum('due_amount'),
            'total_overdue' => $overduePurchases->sum('due_amount'),
            'unpaid_count' => $unpaidPurchases->count(),
            'partial_count' => $partialPurchases->count(),
            'overdue_count' => $overduePurchases->count(),
            'unpaid_purchases' => $unpaidPurchases->map(fn($p) => [
                'purchase_id' => $p->purchase_id,
                'supplier_name' => $p->supplier?->name,
                'amount' => $p->total_amount,
                'due_date' => $p->due_date?->format('Y-m-d'),
            ]),
            'overdue_purchases' => $overduePurchases->map(fn($p) => [
                'purchase_id' => $p->purchase_id,
                'supplier_name' => $p->supplier?->name,
                'amount' => $p->due_amount,
                'due_date' => $p->due_date?->format('Y-m-d'),
                'days_overdue' => $p->due_date?->diffInDays(now()),
            ]),
        ];
    }

    /**
     * Get inventory valuation summary.
     */
    public function getInventoryValuationSummary(int $campusId): array
    {
        $stocks = InventoryStock::forCampus($campusId)->with('inventoryItem')->get();

        $totalQuantity = 0;
        $totalValue = 0;
        $items = [];

        foreach ($stocks as $stock) {
            $latestValuation = InventoryValuation::forCampus($campusId)
                ->forItem($stock->inventory_item_id)
                ->latest()
                ->first();

            $unitCost = $latestValuation?->unit_cost ?? 0;
            $itemValue = $stock->quantity * $unitCost;

            $totalQuantity += $stock->quantity;
            $totalValue += $itemValue;

            $items[] = [
                'item_id' => $stock->inventory_item_id,
                'item_name' => $stock->inventoryItem?->name,
                'quantity' => $stock->quantity,
                'available_quantity' => $stock->available_quantity,
                'unit_cost' => $unitCost,
                'total_value' => $itemValue,
                'is_low_stock' => $stock->isLowStock(),
            ];
        }

        return [
            'total_items' => $stocks->count(),
            'total_quantity' => $totalQuantity,
            'total_value' => $totalValue,
            'formatted_total_value' => number_format($totalValue, 2),
            'items' => $items,
        ];
    }

    /**
     * Get supplier-wise purchase summary.
     */
    public function getSupplierPurchaseSummary(int $campusId, ?Carbon $fromDate = null, ?Carbon $toDate = null): array
    {
        $query = Purchase::forCampus($campusId)
            ->with('supplier')
            ->select('supplier_id', DB::raw('COUNT(*) as total_purchases'), DB::raw('SUM(total_amount) as total_amount'), DB::raw('SUM(paid_amount) as total_paid'))
            ->groupBy('supplier_id');

        if ($fromDate) {
            $query->whereDate('purchase_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('purchase_date', '<=', $toDate);
        }

        $suppliers = $query->get();

        return $suppliers->map(function ($s) {
            return [
                'supplier_id' => $s->supplier_id,
                'supplier_name' => $s->supplier?->name ?? 'Unknown',
                'total_purchases' => $s->total_purchases,
                'total_amount' => $s->total_amount,
                'total_paid' => $s->total_paid,
                'total_due' => ($s->total_amount ?? 0) - ($s->total_paid ?? 0),
            ];
        });
    }

    /**
     * Get dashboard summary for inventory.
     */
    public function getDashboardSummary(int $campusId): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $thisYear = Carbon::now()->startOfYear();

        // Today's purchases
        $todayPurchases = Purchase::forCampus($campusId)
            ->whereDate('purchase_date', $today)
            ->get();

        // This month's purchases
        $monthPurchases = Purchase::forCampus($campusId)
            ->whereDate('purchase_date', '>=', $thisMonth)
            ->get();

        // Today's sales
        $todaySales = StudentInventory::forCampus($campusId)
            ->whereDate('assigned_date', $today)
            ->get();

        // This month's sales
        $monthSales = StudentInventory::forCampus($campusId)
            ->whereDate('assigned_date', '>=', $thisMonth)
            ->get();

        // Inventory valuation
        $valuation = $this->getInventoryValuationSummary($campusId);

        // Accounts payable
        $payable = $this->getAccountsPayableSummary($campusId);

        return [
            'today' => [
                'purchase_count' => $todayPurchases->count(),
                'purchase_amount' => $todayPurchases->sum('total_amount'),
                'sales_count' => $todaySales->count(),
                'sales_amount' => $todaySales->sum('final_amount'),
            ],
            'this_month' => [
                'purchase_count' => $monthPurchases->count(),
                'purchase_amount' => $monthPurchases->sum('total_amount'),
                'purchase_paid' => $monthPurchases->sum('paid_amount'),
                'purchase_due' => $monthPurchases->sum('due_amount'),
                'sales_count' => $monthSales->count(),
                'sales_amount' => $monthSales->sum('final_amount'),
                'sales_profit' => $monthSales->sum(fn($s) => $s->getTotalProfit()),
            ],
            'inventory' => [
                'total_items' => $valuation['total_items'],
                'total_quantity' => $valuation['total_quantity'],
                'total_value' => $valuation['total_value'],
            ],
            'accounts_payable' => [
                'total_due' => $payable['total_due'],
                'overdue_count' => $payable['overdue_count'],
                'overdue_amount' => $payable['total_overdue'],
            ],
        ];
    }
}
