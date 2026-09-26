<laravel-boost-guidelines>
Stack: PHP 8.4, Laravel 12, Inertia v2 + Vue 3, Tailwind v4, Pest 3, Ziggy, Pint, Larastan.

## Conventions
- Match existing sibling-file conventions (structure, naming, array vs string validation rules).
- Descriptive names (`isRegisteredForDiscounts`, not `discount()`).
- Reuse existing components before writing new ones.
- Don't create verification scripts/tinker calls when a Pest test already proves the behavior.
- Don't add new base folders or dependencies without approval.
- If a frontend change isn't showing, it likely needs `npm run build` (or dev/composer run dev) — ask if unsure.
- Only create documentation files when explicitly requested.
- Keep replies concise — skip obvious explanations.

## Laravel Boost MCP tools
- `list-artisan-commands` before an unfamiliar artisan call.
- `get-absolute-url` before sharing any project URL.
- `tinker`/`database-query` for ad-hoc PHP/DB inspection.
- `browser-logs` for recent browser errors only.
- `search-docs` before any other lookup for Laravel-ecosystem packages (Laravel, Inertia, Pest, Tailwind, etc.) — multiple short topic queries, no package names in the query text.

## PHP
- Curly braces always, even one-liners.
- Constructor property promotion; no empty zero-arg constructors unless private.
- Explicit return types + param type hints everywhere.
- PHPDoc over inline comments; only comment non-obvious WHY.
- Array shape PHPDoc where useful. Enum cases in TitleCase.

## Testing (required for every change)
- Pest only, `tests/Feature` or `tests/Unit`. Never delete a test file without approval.
- Run the narrowest filter that covers the change (`php artisan test --compact --filter=X` or a specific file) — not the full suite unless asked.
- Use `assertForbidden()`/`assertNotFound()` etc., not `assertStatus(403)`.
- Use model factories/states; `Pest\Laravel\mock` or `$this->mock()` for mocking; datasets for repetitive validation cases.

## Laravel/Inertia
- `Inertia::render()` for pages, not Blade views.
- `<Form>` component or `useForm()` for forms (check sibling convention); `resetOnError`/`resetOnSuccess`/`setDefaultsOnSuccess` available.
- Eloquent relationships over raw queries/`DB::`; eager-load to avoid N+1.
- Form Requests for all validation (rules + messages), never inline.
- `route()`/named routes for links. Config values via `config()`, never `env()` outside config files.
- Queued jobs (`ShouldQueue`) for slow work.
- Laravel 12: middleware lives in `bootstrap/app.php`, no `Kernel.php`; modifying a column migration must repeat all its prior attributes or they're dropped; casts via a `casts()` method, not the `$casts` property.
- Vite manifest error → `npm run build` / `npm run dev` / `composer run dev`.

## Pint / Pest commands
- `vendor/bin/pint --dirty` before finishing any PHP change (never `--test`).
- `php artisan test --compact <path>` — narrowest relevant scope.

## Vue / Tailwind v4
- Single root element per SFC; `router.visit()`/`<Link>` for navigation.
- Tailwind v4 is CSS-first (`@theme` in CSS, `@import "tailwindcss"`, no `tailwind.config.js`). No `corePlugins`.
- Deprecated → replacement: `bg/text/border/divide/ring/placeholder-opacity-*` → `*-black/*`; `flex-shrink-*`→`shrink-*`; `flex-grow-*`→`grow-*`; `overflow-ellipsis`→`text-ellipsis`; `decoration-slice/clone`→`box-decoration-slice/clone`.
- Gap utilities for list spacing, not margins. Match existing `dark:` usage on any touched component.

## Claude tooling (.claude/)
- Agents: architect, migration-builder, model-builder, backend-builder, frontend-builder, security-auditor, test-writer, qa-runner (Zamora stack, non-overlapping file ownership).
- Skills: zamora-module-workflow (start here), impeccable (/impeccable audit|polish|critique), taste-skill, redesign-skill, output-skill, emil-design-eng + animation skills, token-reducer (context_pipeline.py; its hooks are intentionally NOT installed).
- UI changes: run /impeccable audit then polish on the touched page; check dark mode and mobile width.
</laravel-boost-guidelines>
