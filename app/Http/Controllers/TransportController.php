<?php

namespace App\Http\Controllers;

use App\Models\Finance\StudentAccountCharge;
use App\Models\Month;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\TransportStudentAssignment;
use App\Models\TransportVehicle;
use App\Models\TransportVehicleExpense;
use App\Services\Finance\StudentBillingService;
use App\Services\Finance\UnifiedAccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TransportController extends Controller
{
    public function __construct(
        protected StudentBillingService $studentBillingService,
        protected UnifiedAccountingService $accountingService
    ) {}

    /**
     * Dashboard: summary cards only, no forms.
     */
    public function index(Request $request)
    {
        if ($redirect = $this->legacyTab($request)) {
            return $redirect;
        }

        $viewer = $request->user();
        $monthStart = now()->startOfMonth();

        return Inertia::render('Transport/Index', [
            'summary' => [
                'vehicles' => TransportVehicle::visibleTo($viewer)->where('is_active', true)->count(),
                'routes' => TransportRoute::visibleTo($viewer)->where('is_active', true)->count(),
                'assigned_students' => TransportStudentAssignment::visibleTo($viewer)->where('status', 'active')->count(),
                'month_dues' => (float) StudentAccountCharge::query()
                    ->where('source_module', 'transport')
                    ->where('billing_period_month_id', (int) now()->format('n'))
                    ->where('billing_period_year', (int) now()->format('Y'))
                    ->when($viewer?->campusId(), fn ($query, $campusId) => $query->where('campus_id', $campusId))
                    ->sum('net_amount'),
                'month_expenses' => (float) TransportVehicleExpense::visibleTo($viewer)
                    ->whereBetween('expense_date', [$monthStart->toDateString(), now()->endOfMonth()->toDateString()])
                    ->sum('amount'),
            ],
        ]);
    }

    public function vehicles(Request $request)
    {
        return Inertia::render('Transport/Vehicles', [
            'vehicles' => TransportVehicle::with('campus')->visibleTo($request->user())->latest()->get(),
        ]);
    }

    public function routes(Request $request)
    {
        $viewer = $request->user();

        return Inertia::render('Transport/Routes', [
            'routes' => TransportRoute::with(['campus', 'vehicle', 'stops'])->visibleTo($viewer)->latest()->get(),
            'stops' => TransportStop::visibleTo($viewer)->orderBy('name')->get(),
            'vehicles' => TransportVehicle::visibleTo($viewer)->where('is_active', true)->orderBy('vehicle_no')->get(['id', 'vehicle_no']),
        ]);
    }

    public function expenses(Request $request)
    {
        $viewer = $request->user();

        return Inertia::render('Transport/Expenses', [
            'expenses' => TransportVehicleExpense::with(['vehicle', 'campus'])->visibleTo($viewer)->latest('expense_date')->take(200)->get(),
            'vehicles' => TransportVehicle::visibleTo($viewer)->orderBy('vehicle_no')->get(['id', 'vehicle_no']),
        ]);
    }

    /**
     * Route-wise students, dues and expenses for the header campus.
     */
    public function reports(Request $request)
    {
        $viewer = $request->user();

        $routes = TransportRoute::visibleTo($viewer)->orderBy('name')->get();
        $assignments = TransportStudentAssignment::visibleTo($viewer)->where('status', 'active')->get();
        $dues = StudentAccountCharge::query()
            ->where('source_module', 'transport')
            ->when($viewer?->campusId(), fn ($query, $campusId) => $query->where('campus_id', $campusId))
            ->get(['source_id', 'net_amount', 'status', 'billing_period_month_id', 'billing_period_year'])
            ->groupBy('source_id');
        $assignmentRoute = $assignments->pluck('transport_route_id', 'id');
        $dueByRoute = [];

        foreach ($dues as $assignmentId => $charges) {
            $routeId = $assignmentRoute[$assignmentId] ?? null;

            if ($routeId !== null) {
                $dueByRoute[$routeId] = ($dueByRoute[$routeId] ?? 0) + (float) $charges->sum('net_amount');
            }
        }

        $expenseByVehicle = TransportVehicleExpense::visibleTo($viewer)
            ->selectRaw('transport_vehicle_id, SUM(amount) as total')
            ->groupBy('transport_vehicle_id')
            ->pluck('total', 'transport_vehicle_id');

        return Inertia::render('Transport/Reports', [
            'rows' => $routes->map(fn (TransportRoute $route) => [
                'id' => $route->id,
                'name' => $route->name,
                'vehicle_no' => $route->vehicle?->vehicle_no,
                'monthly_fee' => (float) $route->monthly_fee,
                'students' => $assignments->where('transport_route_id', $route->id)->count(),
                'dues' => (float) ($dueByRoute[$route->id] ?? 0),
                'expenses' => (float) ($route->transport_vehicle_id ? ($expenseByVehicle[$route->transport_vehicle_id] ?? 0) : 0),
            ])->values(),
            'unassigned_expenses' => (float) ($expenseByVehicle[''] ?? 0),
        ]);
    }

    /**
     * Student side: assignments list with the assign dialog.
     */
    public function studentAssignments(Request $request)
    {
        $viewer = $request->user();
        $campusId = $viewer?->campusId();

        return Inertia::render('Transport/StudentAssignments', [
            'assignments' => TransportStudentAssignment::with(['student.user', 'route', 'stop', 'campus'])
                ->visibleTo($viewer)
                ->latest()
                ->get(),
            'routes' => TransportRoute::with(['stops', 'vehicle'])
                ->visibleTo($viewer)
                ->where('is_active', true)
                ->when($campusId, fn ($query) => $query->where('campus_id', $campusId))
                ->orderBy('name')
                ->get(),
            'students' => Student::with(['user', 'currentEnrollment.class', 'currentEnrollment.section'])
                ->visibleTo($viewer)
                ->when($campusId, fn ($query) => $query->whereHas(
                    'currentEnrollment',
                    fn ($enrollment) => $enrollment->where('campus_id', $campusId)
                ))
                ->orderByDesc('id')
                ->take(500)
                ->get()
                ->map(fn (Student $student) => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'registration_no' => $student->registration_no,
                    'campus_id' => $student->currentEnrollment?->campus_id,
                    'enrollment_id' => $student->currentEnrollment?->id,
                    'display' => trim(collect([
                        $student->name,
                        $student->registration_no,
                        $student->currentEnrollment?->class?->name,
                        $student->currentEnrollment?->section?->name,
                    ])->filter()->implode(' | ')),
                ]),
            'preselectStudentId' => $request->integer('student') ?: null,
        ]);
    }

    public function studentDues(Request $request)
    {
        $viewer = $request->user();

        return Inertia::render('Transport/StudentDues', [
            'months' => Month::select('id', 'name')->orderBy('id')->get(),
            'charges' => StudentAccountCharge::with('student.user')
                ->where('source_module', 'transport')
                ->when($viewer?->campusId(), fn ($query, $campusId) => $query->where('campus_id', $campusId))
                ->latest('id')
                ->take(200)
                ->get()
                ->map(fn (StudentAccountCharge $charge) => [
                    'id' => $charge->id,
                    'student' => $charge->student?->name,
                    'registration_no' => $charge->student?->registration_no,
                    'title' => $charge->title,
                    'month_id' => $charge->billing_period_month_id,
                    'year' => $charge->billing_period_year,
                    'amount' => (float) $charge->net_amount,
                    'status' => $charge->billing_status,
                ]),
        ]);
    }

    /**
     * Old `/transport?tab=...` bookmarks land on the page that replaced the tab.
     */
    private function legacyTab(Request $request): ?RedirectResponse
    {
        $target = match ($request->query('tab')) {
            'vehicles' => route('transport.vehicles.index'),
            'routes', 'stops' => route('transport.routes.index'),
            'assignments' => route('students.transport.index'),
            'expenses' => route('transport.expenses.index'),
            'dues' => route('students.transport-dues.index'),
            'reports' => route('transport.reports.index'),
            default => null,
        };

        return $target ? redirect($target) : null;
    }

    public function storeVehicle(Request $request)
    {
        Gate::authorize('create', TransportVehicle::class);

        $this->applyHeaderCampus($request);

        $data = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
            'vehicle_no' => 'required|string|max:100|unique:transport_vehicles,vehicle_no',
            'vehicle_type' => 'required|string|max:50',
            'capacity' => 'required|integer|min:0',
            'driver_name' => 'nullable|string|max:150',
            'attendant_name' => 'nullable|string|max:150',
            'purchase_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $vehicle = TransportVehicle::create($data + ['status' => 'active', 'is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle created successfully.',
            'vehicle' => $vehicle->load('campus'),
        ]);
    }

    public function updateVehicle(Request $request, TransportVehicle $vehicle)
    {
        Gate::authorize('update', $vehicle);

        $this->applyHeaderCampus($request);

        $data = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
            'vehicle_no' => 'required|string|max:100|unique:transport_vehicles,vehicle_no,'.$vehicle->id,
            'vehicle_type' => 'required|string|max:50',
            'capacity' => 'required|integer|min:0',
            'driver_name' => 'nullable|string|max:150',
            'attendant_name' => 'nullable|string|max:150',
            'status' => 'required|string|max:50',
            'purchase_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $vehicle->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle updated successfully.',
            'vehicle' => $vehicle->fresh('campus'),
        ]);
    }

    public function storeStop(Request $request)
    {
        Gate::authorize('create', TransportStop::class);

        $this->applyHeaderCampus($request);

        $data = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
            'name' => 'required|string|max:150',
            'pickup_time' => 'nullable',
            'drop_time' => 'nullable',
        ]);

        $stop = TransportStop::create($data + ['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Stop created successfully.',
            'stop' => $stop->load('campus'),
        ]);
    }

    public function updateStop(Request $request, TransportStop $stop)
    {
        Gate::authorize('update', $stop);

        $this->applyHeaderCampus($request);

        $data = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
            'name' => 'required|string|max:150',
            'pickup_time' => 'nullable',
            'drop_time' => 'nullable',
            'is_active' => 'boolean',
        ]);

        $stop->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Stop updated successfully.',
            'stop' => $stop->fresh('campus'),
        ]);
    }

    public function storeRoute(Request $request)
    {
        Gate::authorize('create', TransportRoute::class);

        $this->applyHeaderCampus($request);

        $data = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
            'transport_vehicle_id' => 'nullable|exists:transport_vehicles,id',
            'name' => 'required|string|max:150',
            'monthly_fee' => 'required|numeric|min:0',
            'stop_ids' => 'array',
            'stop_ids.*' => 'exists:transport_stops,id',
            'notes' => 'nullable|string',
        ]);
        $this->assertRouteNameFree($data['name'], $data['campus_id'] ?? null);

        $route = DB::transaction(function () use ($data) {
            $route = TransportRoute::create([
                'campus_id' => $data['campus_id'] ?? null,
                'transport_vehicle_id' => $data['transport_vehicle_id'] ?? null,
                'name' => $data['name'],
                'monthly_fee' => $data['monthly_fee'],
                'notes' => $data['notes'] ?? null,
                'is_active' => true,
            ]);

            if (! empty($data['stop_ids'])) {
                $syncData = [];

                foreach (array_values($data['stop_ids']) as $index => $stopId) {
                    $syncData[$stopId] = ['sort_order' => $index + 1];
                }

                $route->stops()->sync($syncData);
            }

            return $route;
        });

        return response()->json([
            'success' => true,
            'message' => 'Route created successfully.',
            'route' => $route->load(['campus', 'vehicle', 'stops']),
        ]);
    }

    public function updateRoute(Request $request, TransportRoute $route)
    {
        Gate::authorize('update', $route);

        $this->applyHeaderCampus($request);

        $data = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
            'transport_vehicle_id' => 'nullable|exists:transport_vehicles,id',
            'name' => 'required|string|max:150',
            'monthly_fee' => 'required|numeric|min:0',
            'stop_ids' => 'array',
            'stop_ids.*' => 'exists:transport_stops,id',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $this->assertRouteNameFree($data['name'], $data['campus_id'] ?? null, $route->id);

        DB::transaction(function () use ($route, $data) {
            $route->update([
                'campus_id' => $data['campus_id'] ?? null,
                'transport_vehicle_id' => $data['transport_vehicle_id'] ?? null,
                'name' => $data['name'],
                'monthly_fee' => $data['monthly_fee'],
                'notes' => $data['notes'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $syncData = [];

            foreach (array_values($data['stop_ids'] ?? []) as $index => $stopId) {
                $syncData[$stopId] = ['sort_order' => $index + 1];
            }

            $route->stops()->sync($syncData);
        });

        return response()->json([
            'success' => true,
            'message' => 'Route updated successfully.',
            'route' => $route->fresh(['campus', 'vehicle', 'stops']),
        ]);
    }

    /**
     * Records always belong to the campus picked in the header switcher, so
     * forms never ask for a campus and a stale client value is ignored.
     */
    private function applyHeaderCampus(Request $request): void
    {
        $campusId = $request->user()?->campusId();

        if ($campusId !== null) {
            $request->merge(['campus_id' => $campusId]);
        }
    }

    private function assertRouteNameFree(string $name, ?int $campusId, ?int $ignoreId = null): void
    {
        $taken = TransportRoute::query()
            ->where('campus_id', $campusId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'name' => 'A route named "'.trim($name).'" already exists on this campus. Please use a different name.',
            ]);
        }
    }

    public function storeAssignment(Request $request)
    {
        Gate::authorize('create', TransportStudentAssignment::class);

        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'student_enrollment_record_id' => 'nullable|exists:student_enrollment_records,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'transport_route_id' => 'required|exists:transport_routes,id',
            'transport_stop_id' => 'nullable|exists:transport_stops,id',
            'monthly_fee' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'generate_dues' => 'boolean',
        ]);

        if (empty($data['student_enrollment_record_id'])) {
            $data['student_enrollment_record_id'] = StudentEnrollmentRecord::where('student_id', $data['student_id'])
                ->whereNull('leave_date')
                ->value('id');
        }

        $this->assertAssignmentIntegrity($data);

        $assignment = TransportStudentAssignment::create($data + ['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'Student transport assignment created successfully.',
            'assignment' => $assignment->load(['student.user', 'route', 'stop', 'campus']),
        ]);
    }

    public function updateAssignment(Request $request, TransportStudentAssignment $assignment)
    {
        Gate::authorize('update', $assignment);

        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'student_enrollment_record_id' => 'nullable|exists:student_enrollment_records,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'transport_route_id' => 'required|exists:transport_routes,id',
            'transport_stop_id' => 'nullable|exists:transport_stops,id',
            'monthly_fee' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'generate_dues' => 'boolean',
            'status' => 'required|string|max:50',
        ]);

        if (empty($data['student_enrollment_record_id'])) {
            $data['student_enrollment_record_id'] = StudentEnrollmentRecord::where('student_id', $data['student_id'])
                ->whereNull('leave_date')
                ->value('id');
        }

        $this->assertAssignmentIntegrity($data);

        $assignment->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Student transport assignment updated successfully.',
            'assignment' => $assignment->fresh(['student.user', 'route', 'stop', 'campus']),
        ]);
    }

    public function storeExpense(Request $request)
    {
        Gate::authorize('create', TransportVehicleExpense::class);

        $this->applyHeaderCampus($request);

        $data = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
            'transport_vehicle_id' => 'nullable|exists:transport_vehicles,id',
            'expense_type' => 'required|string|max:50',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'reference_no' => 'nullable|string|max:150',
            'description' => 'nullable|string',
        ]);

        $expense = TransportVehicleExpense::create($data + ['created_by' => auth()->id()]);
        $this->accountingService->postTransportExpenseJournal($expense->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Transport expense recorded successfully.',
            'expense' => $expense->load(['vehicle', 'campus']),
        ]);
    }

    public function updateExpense(Request $request, TransportVehicleExpense $expense)
    {
        Gate::authorize('update', $expense);

        $this->applyHeaderCampus($request);

        $data = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
            'transport_vehicle_id' => 'nullable|exists:transport_vehicles,id',
            'expense_type' => 'required|string|max:50',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'reference_no' => 'nullable|string|max:150',
            'description' => 'nullable|string',
        ]);

        $expense->update($data);
        $this->accountingService->postTransportExpenseJournal($expense->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Transport expense updated successfully.',
            'expense' => $expense->fresh(['vehicle', 'campus']),
        ]);
    }

    public function generateDues(Request $request)
    {
        Gate::authorize('generateDues', TransportStudentAssignment::class);

        $this->applyHeaderCampus($request);

        $data = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
            'month_id' => 'required|exists:months,id',
            'year' => 'required|integer|min:2000|max:2100',
            'due_date' => 'nullable|date',
        ]);

        $assignments = TransportStudentAssignment::with(['student', 'enrollmentRecord'])
            ->visibleTo($request->user())
            ->where('status', 'active')
            ->where('generate_dues', true)
            ->when($data['campus_id'] ?? null, fn ($query, $campusId) => $query->where('campus_id', $campusId))
            ->whereDate('effective_from', '<=', now())
            ->where(function ($query) {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', now());
            })
            ->get();

        $generated = 0;
        $totalAmount = 0;

        $mergedCount = 0;
        $mergedAmount = 0.0;
        $blockedCount = 0;
        $alreadyBilledCount = 0;

        foreach ($assignments as $assignment) {
            $voucher = $this->studentBillingService->voucherForPeriod(
                (int) $assignment->student_id,
                (int) $data['month_id'],
                (int) $data['year']
            );

            if ($voucher && ! $this->studentBillingService->voucherAcceptsNewLines($voucher)) {
                $blockedCount++;

                continue;
            }

            $charge = $this->studentBillingService->createTransportCharge(
                $assignment,
                (int) $data['month_id'],
                (int) $data['year'],
                $data['due_date'] ?? null
            );

            if ($charge->wasRecentlyCreated) {
                $this->accountingService->postTransportChargeJournal($charge);
                $generated++;
                $totalAmount += (float) $charge->net_amount;
            }

            if ($voucher && $this->studentBillingService->attachTransportChargeToVoucher($charge, $voucher)) {
                $mergedCount++;
                $mergedAmount += (float) $charge->net_amount;
            }

            if (! $charge->wasRecentlyCreated) {
                $alreadyBilledCount++;
            }
        }

        $skippedReasons = array_filter([
            'voucher already paid or closed' => $blockedCount,
            'already billed' => $alreadyBilledCount,
        ]);
        $skippedCount = array_sum($skippedReasons);
        $skippedText = $skippedCount === 0
            ? ''
            : ', skipped '.$skippedCount.' ('.collect($skippedReasons)->map(fn ($count, $reason) => $count.' '.$reason)->implode(', ').')';

        return response()->json([
            'success' => true,
            'message' => 'Added Rs '.number_format($mergedAmount).' transport to '.$mergedCount.' existing '
                .($mergedCount === 1 ? 'voucher' : 'vouchers').', created '.$generated.' new '
                .($generated === 1 ? 'due' : 'dues').$skippedText.'.',
            'skipped_reasons' => $skippedReasons,
            'merged_count' => $mergedCount,
            'blocked_count' => $blockedCount,
            'generated_count' => $generated,
            'generated_amount' => $totalAmount,
        ]);
    }

    /**
     * Guards the two ways a transport assignment can silently point somewhere
     * it shouldn't: a stop that was never added to the chosen route, or a
     * route that belongs to a different campus than the student it is being
     * assigned to.
     *
     * @param  array{student_id: int, student_enrollment_record_id: int|null, transport_route_id: int, transport_stop_id: int|null}  $data
     */
    private function assertAssignmentIntegrity(array $data): void
    {
        $route = TransportRoute::findOrFail($data['transport_route_id']);

        if (! empty($data['transport_stop_id'])
            && ! $route->stops()->where('transport_stops.id', $data['transport_stop_id'])->exists()) {
            throw ValidationException::withMessages([
                'transport_stop_id' => 'The selected stop is not one of the stops on the selected route.',
            ]);
        }

        $studentCampusId = $data['student_enrollment_record_id'] ?? null
            ? StudentEnrollmentRecord::whereKey($data['student_enrollment_record_id'])->value('campus_id')
            : StudentEnrollmentRecord::where('student_id', $data['student_id'])
                ->whereNull('leave_date')
                ->value('campus_id');

        if ($studentCampusId !== null && $route->campus_id !== null && (int) $studentCampusId !== (int) $route->campus_id) {
            throw ValidationException::withMessages([
                'transport_route_id' => 'The selected route does not belong to the student\'s campus.',
            ]);
        }
    }
}
