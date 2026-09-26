---
name: seeders
description: How to write seeders and factories for this project, and the essential-vs-demo split. Use whenever creating a seeder/factory or running php artisan migrate --seed.
---

# Seeders & Factories standard (docs/CLAUDE.md Section 22B)

- Seeder naming: `{Model}Seeder`, living in root `database/seeders/` —
  NOT inside a module folder. This is the one intentional deviation from
  the module-folder rule (Section 4), because module-splitting seeders
  breaks artisan's seeder auto-discovery/ordering.
- `DatabaseSeeder.php` call order (dependency order):
  ```
  RolePermissionSeeder -> UserSeeder -> DirectorSeeder -> CategorySeeder ->
  TagSeeder -> ArticleSeeder -> ResearchPaperSeeder -> ResourceSeeder ->
  CourseSeeder (creates lessons too) -> SettingSeeder
  ```
- Two kinds of seeder, never mixed in one class:
  - **Essential** — `RolePermissionSeeder`, `SettingSeeder`,
    `DirectorSeeder`, `UserSeeder` (director's own account). Run in every
    environment, including production.
  - **Demo** — `CategorySeeder`/`TagSeeder` sample rows, `ArticleSeeder`,
    `ResearchPaperSeeder`, `ResourceSeeder`, `CourseSeeder`,
    `EnrollmentSeeder`, `ConsultationSeeder`, `ContactMessageSeeder`. Fake
    content, local/staging only — `DatabaseSeeder` gates these behind
    `if (! app()->isProduction())`.
- Exact demo seed counts per table: see `docs/PROJECT-BLUEPRINT.md` Part F2.
- Factories: root `database/factories/` (Laravel default), never inside a
  module folder, so `Model::factory()` auto-discovery keeps working.
- Factory naming: `{Model}Factory`. Use realistic Urdu/English mixed fake
  data (not generic lorem ipsum) so dev content looks close to real.
- Goal: after `php artisan migrate --seed` on local/staging, every public
  list page and the dashboard show believable content — never an
  empty-state during development or client demos.
