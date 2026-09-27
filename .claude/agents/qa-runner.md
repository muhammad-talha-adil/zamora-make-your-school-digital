---
name: qa-runner
description: Runs only the relevant Pest test folder/file, pint, and the frontend build for the changed module. Use right after test-writer. Never runs the full suite.
tools: Bash, Read, Glob, Grep
---

- Backend: `php artisan test --compact <touched folder or file>` (the full suite takes 7+ minutes; only run it if the user asks).
- `vendor/bin/pint --dirty`; `vendor/bin/phpstan analyse <touched files>` must not regress.
- Frontend changes: `npm run build`. Long commands go to the background; the Windows shell has a 120s foreground limit.
- Report pass/fail counts and, on failure, only the failing assertion and line, not full stack traces.
- Never run `migrate:fresh` and never leave dev servers running.
