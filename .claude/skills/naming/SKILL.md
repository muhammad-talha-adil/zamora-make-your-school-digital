---
name: naming
description: Naming conventions for every kind of file/class in this project. Use whenever creating a new file to confirm its name and location.
---

# Naming standards (docs/CLAUDE.md Section 3-4)

- Classes: PascalCase (`ArticleController`).
- Migration files: snake_case with timestamp
  (`2025_01_01_000000_create_articles_table.php`).
- Blade/Livewire view files: kebab-case (`article-card.blade.php`).
- Livewire components: PascalCase class, kebab-case tag
  (`<livewire:articles.article-card />`).
- DB tables: plural snake_case (`articles`, `course_lessons`).
- DB columns: snake_case, booleans prefixed `is_`/`has_`.
- Routes: kebab-case URIs, dot-notation names matching folder structure
  (`articles.show`).
- Enums: PascalCase class, UPPER_SNAKE cases, string-backed.
- Form Requests: `{Action}{Model}Request`.
- Services: `{Model}Service`, single-responsibility public methods.
- Repositories: `{Model}Repository` implementing `{Model}RepositoryInterface`.
- Policies: `{Model}Policy`, standard Laravel method names only.
- Jobs: `{Verb}{Noun}Job`.
- Events: `{Noun}{PastTenseVerb}`; Listeners: `{Verb}{Noun}`.
- Traits: `Has{Capability}` (`HasUuid`, `HasActivityLog`, `HasTranslations`).
- Helpers: grouped by domain in `app/Support/{Domain}Helper.php` as static
  methods — never global functions in `helpers.php` unless truly generic,
  and even then defined once in `app/Support/helpers.php`.

Every feature lives inside `app/Modules/{Module}/` with standard Laravel
sub-folders (`Models/`, `Http/Controllers/`, `Http/Requests/`,
`Http/Resources/`, `Policies/`, `Services/`, `Repositories/`, `Livewire/`).
Only truly shared, cross-module code goes in `app/Support/`. No loose files
in root-level generic folders.
