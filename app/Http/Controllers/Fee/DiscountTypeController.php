<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Controller;
use App\Models\Fee\DiscountType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiscountTypeController extends Controller
{
    /**
     * Display a listing of discount types.
     */
    public function index()
    {
        $discountTypes = DiscountType::latest()->get();

        return Inertia::render('Fee/DiscountTypes/Index', [
            'discountTypes' => $discountTypes,
        ]);
    }

    /**
     * Show the form for creating a new discount type.
     */
    public function create(): Response
    {
        return Inertia::render('Fee/DiscountTypes/Create');
    }

    /**
     * Store a newly created discount type.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:discount_types,code',
            'default_value_type' => 'required|in:fixed,percent',
            'default_value' => 'required|numeric|min:0',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // Map default_value_type to value_type for the database
        $data = $validated;
        $data['value_type'] = $data['default_value_type'];
        unset($data['default_value_type']);

        DiscountType::create($data);

        return redirect()->route('fee.discount-types.index')
            ->with('success', 'Discount type created successfully.');
    }

    /**
     * Show the form for editing the specified discount type.
     */
    public function edit(DiscountType $discountType): Response
    {
        return Inertia::render('Fee/DiscountTypes/Edit', [
            'discountType' => $discountType,
        ]);
    }

    /**
     * Update the specified discount type.
     */
    public function update(Request $request, DiscountType $discountType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:discount_types,code,' . $discountType->id,
            'default_value_type' => 'required|in:fixed,percent',
            'default_value' => 'required|numeric|min:0',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // Map default_value_type to value_type for the database
        $data = $validated;
        $data['value_type'] = $data['default_value_type'];
        unset($data['default_value_type']);

        $discountType->update($data);

        return redirect()->route('fee.discount-types.index')
            ->with('success', 'Discount type updated successfully.');
    }

    /**
     * Remove the specified discount type.
     */
    public function destroy(DiscountType $discountType)
    {
        $discountType->delete();

        return redirect()->route('fee.discount-types.index')
            ->with('success', 'Discount type deleted successfully.');
    }

    /**
     * Get all discount types for dropdown (API)
     */
    public function getAll()
    {
        $discountTypes = DiscountType::active()->get();

        return response()->json([
            'success' => true,
            'data' => $discountTypes,
        ]);
    }
}
