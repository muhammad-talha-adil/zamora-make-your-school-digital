---
name: migrations
description: How to write a Laravel migration for this project. Use whenever creating or altering a table.
---

# Migration standards (docs/CLAUDE.md Section 5)

- File name: `{timestamp}_create_{table}_table.php` or
  `{timestamp}_add_{column}_to_{table}_table.php`.
- Every table: `id()` (internal PK, never exposed) + `uuid('uuid')->unique()`
  (used for all public-facing/URL references).
- Every table: `softDeletes()` unless it's a pure pivot/log table.
- Every table: `timestamps()`.
- Foreign keys: `foreignId('x_id')->constrained()->cascadeOnDelete()` or
  `->nullOnDelete()` — choose per relationship, name the column clearly
  instead of adding a comment.
- Publicly browsable content: `string('slug')->unique()`.
- Translatable fields (title, description, body): `json` column, no
  locale suffix (e.g. `title`, not `title_en`) — paired with Spatie
  Translatable on the model.
- Enum-like state: `string` column + PHP backed enum cast — never native
  MySQL `ENUM`.
- Pivot tables: singular_singular, alphabetical order (`article_tag`,
  `course_user`).

Exact schema for every table is in `docs/PROJECT-BLUEPRINT.md` Part A —
follow it literally, do not invent columns.
