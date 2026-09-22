<?php

namespace App\Http\Controllers\Fee;

use App\Enums\Fee\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\Fee\StudentDiscount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The other half of the discount approval workflow.
 *
 * `StudentRepository::createDiscountsFromAdmission()` has always created a
 * `pending` `StudentDiscount` for a discount type that `requires_approval`,
 * but until now nothing let anyone move it out of that state. This is that
 * queue: Owner and Principal (`campus_admin`) sign off before a pending
 * discount counts toward a voucher.
 */
class DiscountApprovalController extends Controller
{
    /**
     * List every discount awaiting sign-off, scoped to the viewer's reach by
     * `StudentDiscountPolicy::viewAny()` via route middleware, and narrowed
     * further here to their own campus unless they are school-wide.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', StudentDiscount::class);

        $user = auth()->user();

        $pending = StudentDiscount::query()
            ->pending()
            ->with(['student.user', 'discountType', 'enrollmentRecord.campus'])
            ->when(! $user->isSuperAdmin(), function ($query) use ($user) {
                $query->whereHas('enrollmentRecord', function ($enrollment) use ($user) {
                    $enrollment->where('campus_id', $user->campusId());
                });
            })
            ->latest()
            ->get()
            ->map(fn (StudentDiscount $discount) => [
                'id' => $discount->id,
                'student_name' => $discount->student?->name,
                'discount_type' => $discount->discountType?->name,
                'value_type' => $discount->value_type->value,
                'value' => (float) $discount->value,
                'campus' => $discount->enrollmentRecord?->campus?->name,
                'effective_from' => $discount->effective_from?->toDateString(),
                'reason' => $discount->reason,
                'created_at' => $discount->created_at?->toDateString(),
            ]);

        return Inertia::render('Fee/DiscountApprovals/Index', [
            'pendingDiscounts' => $pending,
        ]);
    }

    /**
     * Approve a pending discount.
     */
    public function approve(StudentDiscount $studentDiscount): RedirectResponse
    {
        Gate::authorize('approve', $studentDiscount);

        $studentDiscount->update([
            'approval_status' => ApprovalStatus::APPROVED,
            'approved_by' => auth()->id(),
        ]);

        return redirect()->route('fee.discount-approvals.index')
            ->with('success', 'Discount approved successfully.');
    }

    /**
     * Reject a pending discount.
     */
    public function reject(StudentDiscount $studentDiscount): RedirectResponse
    {
        Gate::authorize('reject', $studentDiscount);

        $studentDiscount->update([
            'approval_status' => ApprovalStatus::REJECTED,
            'approved_by' => auth()->id(),
        ]);

        return redirect()->route('fee.discount-approvals.index')
            ->with('success', 'Discount rejected.');
    }
}
