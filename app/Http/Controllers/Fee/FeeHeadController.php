<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fee\StoreFeeHeadRequest;
use App\Http\Requests\Fee\UpdateFeeHeadRequest;
use App\Models\Fee\FeeHead;
use App\Services\Fee\FeeHeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class FeeHeadController extends Controller
{
    public function __construct(
        protected FeeHeadService $service
    ) {}

    /**
     * Display listing of fee heads
     */
    public function index(Request $request): Response
    {
        // Gate::authorize('viewAny', FeeHead::class);

        $data = $this->service->getIndexData($request);

        return Inertia::render('Fee/FeeHeads/Index', $data);
    }

    /**
     * Show create form
     */
    public function create(): Response
    {
        // Gate::authorize('create', FeeHead::class);

        Log::info('FeeHeadController: Showing create form', [
            'user_id' => auth()->id(),
        ]);

        $data = $this->service->getCreateData();

        return Inertia::render('Fee/FeeHeads/Create', $data);
    }

    /**
     * Store new fee head
     */
    public function store(StoreFeeHeadRequest $request): RedirectResponse
    {
        // Gate::authorize('create', FeeHead::class);

        Log::info('FeeHeadController: Storing fee head', [
            'user_id' => auth()->id(),
            'code' => $request->code,
        ]);

        try {
            $this->service->create($request->validated());

            return redirect()->route('fee.heads.index')
                ->with('success', 'Fee head created successfully.');
        } catch (\Exception $e) {
            Log::error('FeeHeadController: Store failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to create fee head: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show edit form
     */
    public function edit(FeeHead $feeHead): Response
    {
        // Gate::authorize('update', $feeHead);

        Log::info('FeeHeadController: Showing edit form', [
            'user_id' => auth()->id(),
            'fee_head_id' => $feeHead->id,
        ]);

        $data = $this->service->getEditData($feeHead);

        return Inertia::render('Fee/FeeHeads/Edit', $data);
    }

    /**
     * Update fee head
     */
    public function update(UpdateFeeHeadRequest $request, FeeHead $feeHead): RedirectResponse
    {
        // Gate::authorize('update', $feeHead);

        Log::info('FeeHeadController: Updating fee head', [
            'user_id' => auth()->id(),
            'fee_head_id' => $feeHead->id,
        ]);

        try {
            $this->service->update($feeHead, $request->validated());

            return redirect()->route('fee.heads.index')
                ->with('success', 'Fee head updated successfully.');
        } catch (\Exception $e) {
            Log::error('FeeHeadController: Update failed', [
                'user_id' => auth()->id(),
                'fee_head_id' => $feeHead->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update fee head: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Toggle active status of fee head
     */
    public function toggleActive(Request $request, FeeHead $feeHead): RedirectResponse|JsonResponse
    {
        // Gate::authorize('update', $feeHead);

        Log::info('FeeHeadController: Toggling active status', [
            'user_id' => auth()->id(),
            'fee_head_id' => $feeHead->id,
            'current_status' => $feeHead->is_active,
        ]);

        try {
            $this->service->toggleActive($feeHead);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fee head status updated successfully.',
                    'feeHead' => $feeHead->fresh(),
                ]);
            }

            return redirect()->route('fee.heads.index')
                ->with('success', 'Fee head status updated successfully.');
        } catch (\Exception $e) {
            Log::error('FeeHeadController: Toggle active failed', [
                'user_id' => auth()->id(),
                'fee_head_id' => $feeHead->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update status: '.$e->getMessage());
        }
    }

    /**
     * Delete fee head
     */
    public function destroy(Request $request, FeeHead $feeHead): RedirectResponse|JsonResponse
    {
        // Gate::authorize('delete', $feeHead);

        Log::info('FeeHeadController: Deleting fee head', [
            'user_id' => auth()->id(),
            'fee_head_id' => $feeHead->id,
        ]);

        try {
            $this->service->delete($feeHead);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fee head deleted successfully.',
                ]);
            }

            return redirect()->route('fee.heads.index')
                ->with('success', 'Fee head deleted successfully.');
        } catch (\Exception $e) {
            Log::error('FeeHeadController: Delete failed', [
                'user_id' => auth()->id(),
                'fee_head_id' => $feeHead->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete fee head: '.$e->getMessage());
        }
    }

    /**
     * Get fee heads for dropdown (API)
     */
    public function getAll(Request $request): JsonResponse
    {
        try {
            $data = $this->service->getIndexData($request);

            return response()->json([
                'success' => true,
                'feeHeads' => $data['feeHeads'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
