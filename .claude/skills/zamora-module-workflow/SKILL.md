---
name: zamora-module-workflow
description: The Zamora project's module review/fix/phase workflow. Use whenever working on this codebase — starting a session, picking up a module, running a "phase", or deciding what to do next.
---

# Zamora module workflow

Before doing anything else in this repo, read `CURRENT-MODULE.md` at the repo root — it is the single live source of truth for what module/phase is active and what remains. Do not re-derive this from scratch; it is always current.

Standing context that does not need re-explaining (already in Claude memory as `project-zamora-*`): what the app is, the review workflow shape, Roman Urdu replies, DB-loss-is-fine, ask-only-when-genuinely-blocked, tell-user-what-can-be-parallelized.

## Reviewing a new module

1. Read schema (migrations), models + relations, services, controllers, requests, routes end to end.
2. Verify every claim against the actual file/migration — never write a finding from a grep alone. Two past findings were wrong this way.
3. Write numbered findings (critical/high/medium) and suggestions into `CURRENT-MODULE.md`, following the exact section format already used there (see the file's own history in `docs/MODULE-LOG.md` for the format).
4. Proactively note which items share files/tables and can be fixed together.

## Fixing

1. Fix + write Pest tests in `tests/Feature/<Module>/<Area>/Case_NN_*.php`, using or creating `tests/Support/<Module>World.php`.
2. Run only the touched test folder (`php artisan test --compact tests/Feature/X`) — never the full suite unless asked; it takes 7+ minutes.
3. `vendor/bin/pint --dirty` then `vendor/bin/phpstan analyse` — must not regress the baseline (0 as of last check).
4. Delete the fixed entry from `CURRENT-MODULE.md`.
5. When a module's list is empty, move its full record to `docs/MODULE-LOG.md` and refill `CURRENT-MODULE.md` with the next module or phase.

## Authorization work specifically

Every new model needs both a Policy (on the shared `ChecksSchoolReach` trait pattern) and a `scopeVisibleTo()` — see `project-zamora-working-rules` memory. Do this in Phase 1-2 of any module, never last.

Before calling that done, check the role seeders for a `*.view.own` sibling permission (`students.view.own`, `fee.view.own`, `transport.view.own`, etc. — all on the `student`/`guardian` roles). A general-permission-only fix locks portal accounts out of their own child's record. Add the `isTheirOwn($user, $record)` branch (`$user->student`, see `ExamResultHeaderPolicy` or `FeePaymentPolicy` for the pattern) and `|x.view.own` on the route's `permission:` middleware. Missed once already on Fee — check it every time from now on.

## File organization

New module code goes in its own subfolder once the module has more than ~2 files: `app/Models/<Module>/`, `app/Services/<Module>/`, `app/Http/Controllers/<Module>/`. Existing older modules (Student) keep files loose at the root of each `app/*` directory by established convention — don't reorganize those, only follow the folder convention for genuinely new modules (Staff, Exam did this).

## Tools

Use the `Write` tool for any multi-line/PHP file content — bash heredocs have broken repeatedly in this environment and left junk files in the repo root.

## Prefer a well-established package over a custom build

When a feature needs something a mature, well-maintained package already solves cleanly (e.g. `spatie/laravel-activitylog` for audit trails instead of hand-rolling an `activity_logs` table + manual logging calls everywhere), use the package — it costs less time and less ongoing maintenance than a custom build, as long as it doesn't make the app bulky (a small, focused package with few transitive dependencies is fine; a heavy framework-within-a-framework is not — use judgement, and if a custom build is genuinely lighter for what's actually needed, build it instead). This still needs the user's approval before adding a new dependency (per CLAUDE.md), same as any other dependency change — this rule is about which option to *recommend*, not a license to add packages unprompted.

Whenever a package is added this way, record it (name, version/constraint, and a one-line reason) in `docs/DEPENDENCIES.md` at the repo root — create the file on the first entry if it doesn't exist yet. This is the single place to check what's installed and why, so a future session doesn't have to re-derive it from `composer.json`/`package.json` diffs.

## Don't idle-wait on a background test run

When a test suite (or any long command) is kicked off with `run_in_background`, do not sit idle waiting for its notification. Keep working: write the next batch of findings, draft the next fix, update docs, start reading the next controller — whatever is next in the queue that doesn't depend on that result. Only block on it when the very next action genuinely needs it (e.g. deciding whether to keep or revert a change). The notification arrives on its own; going idle to wait for it wastes the turn.
