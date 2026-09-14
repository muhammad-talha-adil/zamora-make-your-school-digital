<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureArtisanUiAccess;
use App\Http\Middleware\EnsureSubscriptionActive;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    /**
     * Vendor-only tooling: gated purely on the `developer` role, the same
     * reasoning as {@see EnsureArtisanUiAccess} - this
     * manages the installation's own access lock, so it deliberately has no
     * Spatie permission of its own.
     */
    public function index(Request $request): Response
    {
        $this->authorizeUser($request);

        return Inertia::render('settings/Subscription', [
            'subscription' => Subscription::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeUser($request);

        // The <select> posts an empty string for "None"; normalise it to
        // null so it passes the nullable `in:` rule below instead of failing
        // it as an unrecognised value.
        $request->merge([
            'block_reason' => $request->get('block_reason') ?: null,
        ]);

        $validated = $request->validate([
            'status' => 'required|in:demo,active,suspended,expired',
            'block_reason' => 'nullable|in:subscription_expired,domain_expiring,hosting_expiring,other',
            'block_reason_note' => 'nullable|string|max:2000',
            'demo_expires_at' => 'nullable|date',
            'subscription_expires_at' => 'nullable|date',
            'notes' => 'nullable|string|max:5000',
        ]);

        $subscription = Subscription::current();

        // The change itself is recorded by Subscription's own LogsActivity
        // options (causer, before/after) rather than a manual file log.
        $subscription->update([
            ...$validated,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Subscription updated successfully.');
    }

    private function authorizeUser(Request $request): void
    {
        if (! $request->user()?->isDeveloper()) {
            abort(403, 'Unauthorized action. You do not have permission to access this resource.');
        }
    }

    /**
     * The page {@see EnsureSubscriptionActive} redirects
     * everyone but a developer to once the subscription blocks access. It
     * shows no data and offers no login - just why access is locked and who
     * to contact.
     */
    public function locked(): Response
    {
        $subscription = Subscription::current();

        return Inertia::render('SubscriptionLocked', [
            'blockReason' => $subscription->isBlocking() ? $subscription->block_reason : null,
            'blockReasonNote' => $subscription->block_reason_note,
            'supportContact' => config('app.support_contact'),
        ]);
    }
}
