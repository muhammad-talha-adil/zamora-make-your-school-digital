<?php

namespace App\Http\Controllers\Fee;

use App\Enums\Fee\FineType;
use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Fee\DiscountType;
use App\Models\Fee\FeeFineRule;
use App\Models\Fee\FeeHead;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Session;
use App\Services\Fee\FeeHeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FeeSettingsController extends Controller
{
    public function __construct(
        protected FeeHeadService $feeHeadService
    ) {}

    /**
     * Display the tabbed fee settings page (Fee Heads, Discount Types, Fine
     * Rules). Every tab is kept mounted with `v-show` rather than unmounted
     * with `v-if` (the same fix applied to School Profile's tabs for
     * #24/#56-58/#60/#62), so switching tabs never discards in-progress
     * filters or form state. Each tab's data is only fetched, and its tab
     * button only shown, when the current user has the matching permission.
     */
    public function index(Request $request): Response
    {
        $canManageFeeHeads = Gate::check('viewAny', FeeHead::class);
        $canManageDiscountTypes = $request->user()?->can('fee.discount.manage') ?? false;
        $canManageFineRules = $request->user()?->can('fee.fine.manage') ?? false;

        return Inertia::render('Fee/Settings/Index', [
            'feeHeadsData' => $canManageFeeHeads ? $this->feeHeadService->getIndexData($request) : null,
            'discountTypes' => $canManageDiscountTypes ? DiscountType::latest()->get() : null,
            'fineRulesData' => $canManageFineRules ? [
                'fineRules' => $this->getTransformedFineRules($request),
                'campuses' => Campus::select('id', 'name')->orderBy('name')->get(),
                'sessions' => Session::select('id', 'name')->orderBy('start_date', 'desc')->get(),
                'classes' => SchoolClass::select('id', 'name')->orderBy('name')->get(),
                'sections' => Section::select('id', 'name', 'class_id')->orderBy('name')->get(),
                'feeHeads' => FeeHead::active()->select('id', 'name')->orderBy('name')->get(),
                'filters' => $request->only(['campus_id', 'session_id', 'class_id', 'is_active', 'search']),
            ] : null,
        ]);
    }

    protected function getTransformedFineRules(Request $request)
    {
        $query = FeeFineRule::with(['campus', 'session', 'schoolClass', 'section', 'feeHead']);

        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('session_id')) {
            $query->where('session_id', $request->session_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $fineRules = $query->latest()->get();

        return $fineRules->map(function ($rule) {
            return [
                'id' => $rule->id,
                'name' => $rule->name,
                'campus_id' => $rule->campus_id,
                'session_id' => $rule->session_id,
                'class_id' => $rule->class_id,
                'section_id' => $rule->section_id,
                'fee_head_id' => $rule->fee_head_id,
                'grace_days' => $rule->grace_days,
                'fine_type' => $rule->fine_type instanceof FineType
                    ? $rule->fine_type->value
                    : $rule->fine_type,
                'fine_value' => (float) $rule->fine_value,
                'effective_from' => $rule->effective_from?->toDateString(),
                'effective_to' => $rule->effective_to?->toDateString(),
                'is_active' => $rule->is_active,
                'campus' => $rule->campus ? ['id' => $rule->campus->id, 'name' => $rule->campus->name] : null,
                'session' => $rule->session ? ['id' => $rule->session->id, 'name' => $rule->session->name] : null,
                'schoolClass' => $rule->schoolClass ? ['id' => $rule->schoolClass->id, 'name' => $rule->schoolClass->name] : null,
                'section' => $rule->section ? ['id' => $rule->section->id, 'name' => $rule->section->name] : null,
                'feeHead' => $rule->feeHead ? ['id' => $rule->feeHead->id, 'name' => $rule->feeHead->name] : null,
            ];
        });
    }
}
