<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreSessionRequest;
use App\Http\Requests\Settings\UpdateSessionRequest;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $sessions = Session::orderBy('id', 'desc')
            ->paginate(10);

        return Inertia::render('settings/Sessions/Index', [
            'tableSessions' => $sessions,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('settings/Sessions/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSessionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Session::create($validated);

        return redirect()->route('sessions.index')->with('success', 'Session created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Session $session): Response
    {
        return Inertia::render('settings/Sessions/Edit', [
            'session' => $session,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSessionRequest $request, Session $session): RedirectResponse
    {
        $validated = $request->validated();

        $session->update($validated);

        return redirect()->route('sessions.index')->with('success', 'Session updated successfully.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Session $session): RedirectResponse
    {
        $session->delete();

        return redirect()->route('sessions.index')->with('success', 'Session deleted successfully.');
    }

    /**
     * Inactivate the specified resource.
     */
    public function inactivate(Session $session): RedirectResponse
    {
        $session->update(['is_active' => false]);

        return redirect()->route('sessions.index')->with('success', 'Session inactivated successfully.');
    }

    /**
     * Activate the specified resource.
     */
    public function activate(Session $session): RedirectResponse
    {
        // Deactivate all other sessions first
        Session::where('id', '!=', $session->id)->update(['is_active' => false]);

        $session->update(['is_active' => true]);

        return redirect()->route('sessions.index')->with('success', 'Session activated successfully.');
    }

    /**
     * Restore the specified resource from trash.
     */
    public function restore(int $id): RedirectResponse
    {
        $session = Session::onlyTrashed()->findOrFail($id);
        $session->restore();

        return redirect()->route('sessions.index')->with('success', 'Session restored successfully.');
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $session = Session::onlyTrashed()->findOrFail($id);
        $session->forceDelete();

        return redirect()->route('sessions.index')->with('success', 'Session permanently deleted.');
    }
}
