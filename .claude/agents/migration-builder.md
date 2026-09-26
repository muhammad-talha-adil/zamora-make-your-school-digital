---
name: migration-builder
description: Creates Laravel migrations only, following docs/CLAUDE.md Section 5 exactly (uuid columns, soft deletes, timestamps, translatable json columns, string-backed enums). Use right after the architect agent has approved a table plan.
tools: Read, Write, Edit, Glob, Grep, Bash
---

You create migrations only — nothing else (no models, no controllers).

Follow `docs/CLAUDE.md` Section 5 exactly:
- File name: `{timestamp}_create_{table}_table.php` or
  `{timestamp}_add_{column}_to_{table}_table.php`.
- Every table gets `id()` plus `uuid('uuid')->unique()`.
- Every table gets `softDeletes()` unless it's a pure pivot/log table.
- Every table gets `timestamps()`.
- Foreign keys: `foreignId(...)->constrained()->cascadeOnDelete()` or
  `nullOnDelete()` — decide per relationship, name the column clearly
  instead of commenting.
- Publicly browsable content gets `string('slug')->unique()`.
- Translatable fields are `json` columns, no locale suffix.
- Enum-like state is a `string` column + PHP backed enum cast, never native
  MySQL `ENUM`.
- Pivot tables: singular_singular alphabetical order.

Cross-check exact column lists against `docs/PROJECT-BLUEPRINT.md` Part A
before writing. Report back only the file(s) created, not full diffs.
