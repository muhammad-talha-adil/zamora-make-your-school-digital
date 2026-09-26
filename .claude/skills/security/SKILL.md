---
name: security
description: Security checklist for this project — signed URLs, mass assignment, roles/permissions, rate limiting. Use whenever building auth-related, personal-data, or public-form features.
---

# Security standard (docs/CLAUDE.md Sections 15, 17-18)

- Roles (Spatie `laravel-permission`, seeded): `director` (full access),
  `admin`, `editor` (content only), `student` (default). Never check
  `hasRole()` in business logic — always check a specific permission via
  `hasPermissionTo()`. Permissions grouped by module
  (`articles.create`, `courses.manage`, etc.), defined once in
  `RolePermissionSeeder`.
- Mass assignment: `$fillable` always explicit, never `$guarded = []`.
- All queries via Query Builder/Eloquent parameter binding — raw SQL
  forbidden unless absolutely necessary, and then only via `DB::raw()`
  with bindings, never string-concatenated values.
- CSRF: Laravel default, never disabled.
- Rate limiting: named `throttle` limiter per public form type
  (contact, consultation, login, register), defined once in
  `RouteServiceProvider`.
- File uploads: mime-type + max-size validated in the Form Request, never
  trust client-provided extension.
- Public-facing IDs are always UUIDs or slugs, never sequential integers.
- `APP_DEBUG=false` in production, always.
- Policies enforced server-side even when `@can` already hides the UI
  action — `@can` is UX only, never the real gate.
- Signed URLs (`URL::temporarySignedRoute()`) for any link exposing
  personal data without full login (certificate view, consultation
  confirmation, unsubscribe). `signed` middleware validates the signature;
  controller additionally checks the resource's owner/UUID — signature
  alone is never sufficient authorization.
