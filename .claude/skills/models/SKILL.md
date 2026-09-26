---
name: models
description: How to write an Eloquent model for this project. Use whenever creating or editing a model class.
---

# Model standards (docs/CLAUDE.md Section 6)

- Every model uses `HasUuid` trait (`app/Support/Traits/HasUuid.php`) —
  auto-generates UUID on `creating()`.
- `getRouteKeyName()` returns `uuid` for transactional/private models
  (Consultation, Order-like); `slug` for public content models
  (Article, Course, Research, Resource).
- `$fillable` explicitly listed — never `$guarded = []`.
- Relationships are type-hinted: `public function author(): BelongsTo`.
- Models with admin-visible state changes use `HasActivityLog` trait, with
  a custom `getDescriptionForEvent()` producing human-readable log text
  ("Article 'X' was published by Dr. Arrabah", not "UPDATE articles SET...").
- Casts via the `casts()` method (Laravel 11+ style), not the `$casts`
  property.
- Translatable models implement Spatie `HasTranslations` and declare
  `public $translatable = [...]`.

Model file location: `app/Modules/{Module}/Models/{Model}.php`.
