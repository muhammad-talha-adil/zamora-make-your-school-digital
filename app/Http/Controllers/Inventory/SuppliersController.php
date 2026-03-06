<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class SuppliersController extends Controller
{
    /**
     * Display suppliers listing.
     */
    public function index(Request $request): Response
    {
        $campusId = $request->get('campus_id');

        return inertia('inventory/Suppliers/Index', [
            'suppliers' => Supplier::with(['campus:id,name'])
                ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
                ->when($request->get('search'), fn($q) => $q->search($request->get('search')))
                ->when($request->get('active_only'), fn($q) => $q->where('is_active', true))
                ->orderBy('name')
                ->paginate(20),
            'campuses' => Campus::orderBy('name')->get(),
            'filters' => [
                'campus_id' => $campusId,
                'search' => $request->get('search'),
                'active_only' => $request->get('active_only'),
            ],
        ]);
    }

    /**
     * Get all suppliers with optional filters.
     */
    public function getAll(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');
        $perPage = $request->get('per_page', 25);
        $page = $request->get('page', 1);

        // Validate per_page
        $perPage = in_array($perPage, [25, 50, 75, 100]) ? $perPage : 25;

        $suppliersQuery = Supplier::with(['campus:id,name'])
            ->select(['id', 'name', 'contact_person', 'phone', 'email', 'campus_id', 'is_active'])
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->when($request->get('search'), fn($q) => $q->search($request->get('search')))
            ->when($request->get('active_only') !== 'false', fn($q) => $q->where('is_active', true))
            ->orderBy('name');

        // Use pagination
        $paginator = $suppliersQuery->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ]
        ]);
    }

    /**
     * Show form for creating a new supplier.
     *
     * REDIRECTED: Now uses modal on dashboard instead of separate page.
     */
    public function create(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->to("/inventory?modal=inventory-supplier-form&action=create");
    }

    /**
     * Store a new supplier.
     */
    public function store(Request $request)
    {
        $request->validate([
            'campus_id' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'tax_number' => 'nullable|string|max:50',
            'opening_balance' => 'nullable|numeric',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            // Convert 'all' to null for "all campuses" functionality
            $campusId = $request->campus_id;
            if ($campusId === 'all' || $campusId === null || $campusId === '') {
                $campusId = null;
            } elseif (is_numeric($campusId)) {
                // Verify the campus exists if it's a numeric ID
                if (!Campus::where('id', $campusId)->exists()) {
                    if ($request->expectsJson()) {
                        return response()->json(['error' => 'Invalid campus selected'], 422);
                    }
                    return back()->with('error', 'Invalid campus selected');
                }
            }

            // Check for "All Campuses" conflict
            if ($campusId !== null) {
                // Check if there's an "All Campuses" supplier with the same name
                $allCampusExists = Supplier::whereNull('campus_id')
                    ->whereRaw('LOWER(name) = LOWER(?)', [$request->name])
                    ->exists();

                if ($allCampusExists) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'error' => 'A supplier with this name already exists for All Campuses. You cannot create a specific campus supplier with the same name.'
                        ], 422);
                    }
                    return back()->with('error', 'A supplier with this name already exists for All Campuses. You cannot create a specific campus supplier with the same name.')
                        ->withInput();
                }
            }

            $supplier = Supplier::create([
                'campus_id' => $campusId,
                'name' => $request->name,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'tax_number' => $request->tax_number,
                'opening_balance' => $request->opening_balance ?? 0,
                'is_active' => $request->is_active ?? true,
            ]);

            // Return JSON for AJAX requests, redirect for regular requests
            if ($request->expectsJson()) {
                return response()->json([
                    'supplier' => [
                        'id' => $supplier->id,
                        'name' => $supplier->name,
                    ]
                ]);
            }

            return redirect()->route('inventory.suppliers.index')
                ->with('success', 'Supplier created successfully.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to create supplier: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to create supplier: ' . $e->getMessage());
        }
    }

    /**
     * Show form for editing a supplier.
     *
     * REDIRECTED: Now uses modal on dashboard instead of separate page.
     */
    public function edit(Request $request, Supplier $supplier): \Illuminate\Http\RedirectResponse
    {
        $supplier = Supplier::where('id', $supplier->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        return redirect()->to("/inventory?modal=inventory-supplier-form&action=edit&id={$supplier->id}");
    }

    /**
     * Update a supplier.
     */
    public function update(Request $request, Supplier $supplier)
    {
        // Convert 'all' to null for "all campuses" functionality
        $campusId = $request->get('campus_id');
        if ($campusId === 'all' || $campusId === null || $campusId === '') {
            $campusId = null;
        }

        /** @var Supplier $supplier */
        $supplier = Supplier::where('id', $supplier->id)
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'tax_number' => 'nullable|string|max:50',
            'opening_balance' => 'nullable|numeric',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            // Get the new campus_id
            $newCampusId = $request->get('campus_id', $supplier->campus_id);
            if ($newCampusId === 'all' || $newCampusId === null || $newCampusId === '') {
                $newCampusId = null;
            }

            // Check for "All Campuses" conflict (only if campus is changing to specific campus)
            if ($newCampusId !== null && $supplier->campus_id === null) {
                // Supplier is being changed from All Campuses to specific campus
                // Check if another supplier already exists with this name for this campus
                $existsInCampus = Supplier::where('campus_id', $newCampusId)
                    ->whereRaw('LOWER(name) = LOWER(?)', [$request->name])
                    ->where('id', '!=', $supplier->id)
                    ->exists();

                if ($existsInCampus) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'errors' => ['name' => ['A supplier with this name already exists for this campus']]
                        ], 422);
                    }
                    return back()->with('error', 'A supplier with this name already exists for this campus')
                        ->withInput();
                }
            }

            // Check if there's an "All Campuses" supplier with the same name (for specific campus updates)
            if ($newCampusId !== null) {
                $allCampusExists = Supplier::whereNull('campus_id')
                    ->whereRaw('LOWER(name) = LOWER(?)', [$request->name])
                    ->where('id', '!=', $supplier->id)
                    ->exists();

                if ($allCampusExists) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'errors' => ['name' => ['A supplier with this name already exists for All Campuses. You cannot create a specific campus supplier with the same name.']]
                        ], 422);
                    }
                    return back()->with('error', 'A supplier with this name already exists for All Campuses. You cannot create a specific campus supplier with the same name.')
                        ->withInput();
                }
            }

            $supplier->update([
                'campus_id' => $newCampusId,
                'name' => $request->name,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'tax_number' => $request->tax_number,
                'opening_balance' => $request->opening_balance ?? 0,
                'is_active' => $request->is_active ?? true,
            ]);

            // Return JSON for AJAX requests, redirect for regular requests
            if ($request->expectsJson()) {
                return response()->json([
                    'supplier' => [
                        'id' => $supplier->id,
                        'name' => $supplier->name,
                    ]
                ]);
            }

            return redirect()->route('inventory.suppliers.index')
                ->with('success', 'Supplier updated successfully.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to update supplier: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to update supplier: ' . $e->getMessage());
        }
    }

    /**
     * Delete a supplier.
     */
    public function destroy(Request $request, Supplier $supplier)
    {
        /** @var Supplier $supplier */
        $supplier = Supplier::where('id', $supplier->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        if ($supplier->purchases()->exists()) {
            return back()->with('warning', 'Cannot delete supplier with associated purchases. Consider inactivating instead.');
        }

        $supplier->delete();

        return back()->with('success', 'Supplier deleted successfully.');
    }

    /**
     * Inactivate a supplier.
     */
    public function inactivate(Request $request, Supplier $supplier)
    {
        /** @var Supplier $supplier */
        $supplier = Supplier::where('id', $supplier->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        $supplier->update(['is_active' => false]);

        return back()->with('success', 'Supplier inactivated successfully.');
    }

    /**
     * Activate a supplier.
     */
    public function activate(Request $request, Supplier $supplier)
    {
        /** @var Supplier $supplier */
        $supplier = Supplier::where('id', $supplier->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        $supplier->update(['is_active' => true]);

        return back()->with('success', 'Supplier activated successfully.');
    }

    /**
     * Get supplier details with purchases.
     */
    public function show(Request $request, Supplier $supplier): Response
    {
        /** @var Supplier $supplier */
        $supplier = Supplier::where('id', $supplier->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        return inertia('inventory/Suppliers/Show', [
            'supplier' => $supplier->load([
                'campus',
                'purchases' => fn($q) => $q->orderBy('purchase_date', 'desc')->limit(10)
            ]),
            'summary' => [
                'total_purchases' => $supplier->purchases()->count(),
                'total_amount' => $supplier->purchases()->sum('total_amount'),
            ],
        ]);
    }

    /**
     * Check if supplier name exists for campus.
     */
    public function checkNameExists(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'exclude_id' => 'nullable|exists:suppliers,id',
        ]);

        $campusId = $request->get('campus_id');
        $name = $request->name;
        $excludeId = $request->exclude_id;
        $isAllCampus = ($campusId === null || $campusId === 'all' || $campusId === '');

        if ($isAllCampus) {
            // Check if name exists in ANY campus (excluding current supplier)
            $exists = Supplier::whereRaw('LOWER(name) = LOWER(?)', [$name])
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->exists();

            return response()->json([
                'exists' => $exists,
                'message' => $exists ? 'A supplier with this name already exists' : null,
                'is_all_campus_conflict' => false
            ]);
        } else {
            // Check if name exists in the specific campus
            if (!is_numeric($campusId) || !Campus::where('id', $campusId)->exists()) {
                return response()->json(['exists' => false, 'message' => 'Invalid campus']);
            }

            // Check if name exists in the specific campus
            $existsInCampus = Supplier::where('campus_id', $campusId)
                ->whereRaw('LOWER(name) = LOWER(?)', [$name])
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->exists();

            // Also check if there's an "All Campuses" supplier with the same name
            $existsInAllCampuses = Supplier::whereNull('campus_id')
                ->whereRaw('LOWER(name) = LOWER(?)', [$name])
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->exists();

            return response()->json([
                'exists' => $existsInCampus || $existsInAllCampuses,
                'message' => $existsInAllCampuses 
                    ? 'A supplier with this name already exists for All Campuses. You cannot create a specific campus supplier with the same name.'
                    : ($existsInCampus ? 'A supplier with this name already exists for this campus' : null),
                'is_all_campus_conflict' => $existsInAllCampuses
            ]);
        }
    }
}
