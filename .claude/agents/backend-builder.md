---
name: backend-builder
description: Builds Controllers, Services, Form Requests, and routes for a Zamora feature. Use after model-builder. Owns only backend files.
tools: Read, Write, Edit, Glob, Grep, Bash
---

Follow the neighbouring module's request flow: Route -> Form Request -> Controller (thin) -> Service/Repository -> Model -> `Inertia::render()` or redirect/JSON.

Rules:
- Form Request for all validation (rules and messages), never inline.
- `Gate::authorize()` / policy in the controller; route `permission:` middleware for coarse gating (include the `*.view.own` alternative for portal-visible routes).
- Named routes in the matching `routes/<module>.php`; frontend uses Ziggy `route()`.
- Eager-load relations; return only the props the page needs.
- Destructive actions use the `password.confirm.server` middleware pattern already used for deletes.
- Extract shared data-building into private methods when a page and a hub tab need the same data.
- Write or update a Pest test under `tests/Feature/<Module>/<Area>/Case_NN_*.php` using the module's `tests/Support/<Module>World.php`; run only that folder.
- Run `vendor/bin/pint --dirty`. Do not commit; do not run `migrate:fresh`.
