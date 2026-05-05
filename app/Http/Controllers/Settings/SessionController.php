<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreSessionRequest;
use App\Http\Requests\Settings\UpdateSessionRequest;
use App\Models\Session;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $query = Session::query();

        $status = $request->get('status');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->get('trashed') === '1') {
            $query->onlyTrashed();
        }

        $sessions = $query->orderBy('id', 'desc')
            ->paginate((int) $request->get('per_page', 10), ['*'], 'page', (int) $request->get('page', 1));

        if ($request->expectsJson()) {
            return response()->json($sessions);
        }

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
    public function store(StoreSessionRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        Session::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Session created successfully.',
            ]);
        }

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
    public function update(UpdateSessionRequest $request, Session $session): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $session->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Session updated successfully.',
            ]);
        }

        return redirect()->route('sessions.index')->with('success', 'Session updated successfully.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Session $session): RedirectResponse|JsonResponse
    {
        $session->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('sessions.index')->with('success', 'Session deleted successfully.');
    }

    /**
     * Inactivate the specified resource.
     */
    public function inactivate(Session $session): RedirectResponse|JsonResponse
    {
        $session->update(['is_active' => false]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('sessions.index')->with('success', 'Session inactivated successfully.');
    }

    /**
     * Activate the specified resource.
     */
    public function activate(Session $session): RedirectResponse|JsonResponse
    {
        // Deactivate all other sessions first
        Session::where('id', '!=', $session->id)->update(['is_active' => false]);

        $session->update(['is_active' => true]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('sessions.index')->with('success', 'Session activated successfully.');
    }

    /**
     * Restore the specified resource from trash.
     */
    public function restore(int $id): RedirectResponse|JsonResponse
    {
        $session = Session::onlyTrashed()->findOrFail($id);
        $session->restore();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('sessions.index')->with('success', 'Session restored successfully.');
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(int $id): RedirectResponse|JsonResponse
    {
        $session = Session::onlyTrashed()->findOrFail($id);
        $session->forceDelete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('sessions.index')->with('success', 'Session permanently deleted.');
    }
}
