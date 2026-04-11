<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class FeeSettingsController extends Controller
{
    /**
     * Display settings dashboard.
     */
    public function index()
    {
        return Inertia::render('Fee/Settings/Index');
    }
}
