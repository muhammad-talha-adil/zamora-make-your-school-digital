<?php

namespace App\Http\Controllers;

use App\Models\Campus;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TransportController extends Controller
{
    public function __construct(
        protected StudentBillingService $studentBillingService,
        protected UnifiedAccountingService $accountingService
    ) {}

    public function index()
    {
        $vehicles = TransportVehicle::with('campus')->latest()->get();
        $routes = TransportRoute::with(['campus', 'vehicle', 'stops'])->latest()->get();
        $stops = TransportStop::with('campus')->orderBy('name')->get();
        $assignments = TransportStudentAssignment::with(['student.user', 'route', 'stop', 'campus'])
            ->latest()
            ->get();
        $expenses = TransportVehicleExpense::with(['vehicle', 'campus'])->latest()->take(20)->get();

        return Inertia::render('Transport/Index', [
            'vehicles' => $vehicles,
            'routes' => $routes,
            'stops' => $stops,
            'assignments' => $assignments,
            'expenses' => $expenses,
            'campuses' => Campus::select('id', 'name')->orderBy('name')->get(),
            'students' => Student::with(['user', 'currentEnrollment.class', 'currentEnrollment.section'])
                ->orderByDesc('id')
                ->take(200)
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
            'months' => Month::select('id', 'name')->orderBy('id')->get(),
            'summary' => [
                'active_vehicles' => $vehicles->where('is_active', true)->count(),
                'active_routes' => $routes->where('is_active', true)->count(),
                'active_assignments' => $assignments->where('status', 'active')->count(),
                'this_month_expense' => (float) $expenses
                    ->whereBetween('expense_date', [now()->startOfMonth(), now()->endOfMonth()])
                    ->sum('amount'),
            ],
        ]);
    }

    public function storeVehicle(Request $request)
    {
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
        $data = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
            'transport_vehicle_id' => 'nullable|exists:transport_vehicles,id',
            'name' => 'required|string|max:150',
            'monthly_fee' => 'required|numeric|min:0',
            'stop_ids' => 'array',
            'stop_ids.*' => 'exists:transport_stops,id',
            'notes' => 'nullable|string',
        ]);

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

    public function storeAssignment(Request $request)
    {
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

        $assignment = TransportStudentAssignment::create($data + ['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'Student transport assignment created successfully.',
            'assignment' => $assignment->load(['student.user', 'route', 'stop', 'campus']),
        ]);
    }

    public function updateAssignment(Request $request, TransportStudentAssignment $assignment)
    {
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

        $assignment->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Student transport assignment updated successfully.',
            'assignment' => $assignment->fresh(['student.user', 'route', 'stop', 'campus']),
        ]);
    }

    public function storeExpense(Request $request)
    {
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
        $data = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
            'month_id' => 'required|exists:months,id',
            'year' => 'required|integer|min:2000|max:2100',
            'due_date' => 'nullable|date',
        ]);

        $assignments = TransportStudentAssignment::with(['student', 'enrollmentRecord'])
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

        foreach ($assignments as $assignment) {
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
        }

        return response()->json([
            'success' => true,
            'message' => $generated > 0
                ? "Generated {$generated} transport dues successfully."
                : 'No new transport dues were generated for the selected period.',
            'generated_count' => $generated,
            'generated_amount' => $totalAmount,
        ]);
    }
}
