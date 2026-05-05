<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinanceController extends Controller
{
    protected $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    /**
     * Dashboard - Show financial overview
     */
    public function index(Request $request)
    {
        $campusId = $request->filled('campus_id')
            ? $request->campus_id
            : (auth()->user()->campus_id ?? Campus::first()?->id);

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $data = [
            'today_summary' => $this->financeService->getTodaySummary($campusId),
            'month_summary' => $this->financeService->getMonthSummary($campusId),
            'campuses' => Campus::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
            'selected_campus' => $campusId,
        ];

        return Inertia::render('Finance/Dashboard', $data);
    }
}
