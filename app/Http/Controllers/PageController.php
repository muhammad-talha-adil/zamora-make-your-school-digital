<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Home');
    }

    public function about(): Response
    {
        return Inertia::render('About');
    }

    public function contact(): Response
    {
        return Inertia::render('Contact');
    }

    public function dashboard(): Response
    {
        return Inertia::render('Dashboard');
    }

    public function inventoryIndex(): Response
    {
        return Inertia::render('inventory/Index');
    }
}
