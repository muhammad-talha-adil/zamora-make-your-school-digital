<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Month;
use Inertia\Response;

class MonthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return inertia('Settings/School', [
            'months' => Month::orderBy('month_number')->get(),
        ]);
    }

    /**
     * Get all months with optional search
     * Used by Vue components for modal display
     */
    public function getAll()
    {
        $query = trim(request()->get('q', ''));

        $months = Month::when($query, function ($q) use ($query) {
            $q->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($query).'%']);
        })
            ->orderBy('month_number')
            ->get()
            ->map(function ($month) {
                return [
                    'id' => $month->id,
                    'name' => $month->name,
                    'month_number' => $month->month_number,
                ];
            });

        return response()->json($months);
    }
}
