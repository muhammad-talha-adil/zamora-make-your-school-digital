<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Ledger\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentMethod::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('code', 'like', '%'.$request->search.'%');
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
            'is_active' => 'boolean',
        ]);

        $validated['code'] = $this->generateCode($validated['name']);

        PaymentMethod::create($validated);

        return back()->with('success', 'Payment method created successfully!');
    }

    public function update(Request $request, PaymentMethod $method)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'is_active' => 'boolean',
        ]);

        $method->update($validated);

        return back()->with('success', 'Payment method updated successfully!');
    }

    /**
     * Derive a unique lowercase code from the name — nobody types a code by
     * hand, matching the convention used across Class/Section/Subject/Fee
     * Head/Discount Type.
     */
    private function generateCode(string $name): string
    {
        $base = str_replace('-', '_', Str::slug($name, '_'));
        $code = $base;
        $suffix = 1;

        while (PaymentMethod::where('code', $code)->exists()) {
            $suffix++;
            $code = substr($base, 0, 20 - strlen('_'.$suffix)).'_'.$suffix;
        }

        return $code;
    }
}
