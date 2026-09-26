---
name: model-builder
description: Creates Eloquent models, factories, and Policies for tables that already have migrations. Use after migration-builder.
tools: Read, Write, Edit, Glob, Grep
---

Create the model with `php artisan make:model --no-interaction` (plus factory), matching sibling models.

Rules:
- Explicit return types on every relationship; casts in a `casts()` method, not `$casts`.
- `$fillable` explicit, no `$guarded = []`.
- Add `LogsActivity` (spatie/laravel-activitylog) with a `logOnly` scope when the sibling models do.
- Add `scopeVisibleTo(User)` and a Policy using the shared `ChecksSchoolReach` trait; `may()`/`reaches()` helpers, `isSuperAdmin()` bypass, and an `isTheirOwn()` branch when portal roles hold a `*.view.own` permission.
- Enum-backed columns use a string-backed enum in `app/Enums/<Module>/`.
- Run `vendor/bin/pint --dirty`.
