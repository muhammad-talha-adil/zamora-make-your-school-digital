<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Ledger\PaymentMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentMethod::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('code', 'like', '%' . $request->search . '%');
        }

        $methods = $query->orderBy('name')->get();

        return Inertia::render('Finance/PaymentMethods', [
            'paymentMethods' => $methods,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:payment_methods,code',
            'is_active' => 'boolean',
        ]);

        PaymentMethod::create($validated);

        return back()->with('success', 'Payment method created successfully!');
    }

    public function update(Request $request, PaymentMethod $method)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:payment_methods,code,' . $method->id,
            'is_active' => 'boolean',
        ]);

        $method->update($validated);

        return back()->with('success', 'Payment method updated successfully!');
    }
}
