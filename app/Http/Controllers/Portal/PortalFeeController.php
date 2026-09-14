<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Concerns\ResolvesOwnStudent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Fee\FeeVoucherController;
use App\Models\Fee\FeeVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A family's own fee vouchers.
 *
 * `FeeVoucherPolicy::viewAny()` now accepts `fee.view.own`, which only opens
 * the door to this list — it does not widen what any query returns. Every
 * query here is scoped to the caller's own resolved student, never to the
 * whole `fee_vouchers` table.
 */
class PortalFeeController extends Controller
{
    use ResolvesOwnStudent;

    public function __construct(private FeeVoucherController $feeVoucherController) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', FeeVoucher::class);

        $user = $request->user();
        $students = $this->ownStudents($user);
        $student = $this->resolveOwnStudent($user, $request->integer('student_id') ?: null);

        $vouchers = FeeVoucher::where('student_id', $student->id)
            ->with(['voucherMonth', 'campus'])
            ->orderByDesc('voucher_year')
            ->orderByDesc('issue_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Portal/Fees/Index', [
            'student' => ['id' => $student->id, 'name' => $student->user?->name],
            'students' => $students->map(fn ($s) => ['id' => $s->id, 'name' => $s->user?->name])->values(),
            'vouchers' => $vouchers,
        ]);
    }

    /**
     * Reuses `FeeVoucherController::show()` as-is — the ownership check
     * already lives on `FeeVoucherPolicy::view()` and now recognises both the
     * child themselves and a linked guardian.
     */
    public function show(FeeVoucher $voucher): Response
    {
        return $this->feeVoucherController->show($voucher);
    }
}
