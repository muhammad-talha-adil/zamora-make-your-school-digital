---
name: controllers
description: How to write controllers, services, repositories, and form requests for this project. Use whenever building the backend request-flow layer for a feature.
---

# Request-flow standard (docs/CLAUDE.md Section 7)

Universal flow per feature:

```
routes/modules/{module}.php
  Route::middleware(['auth','role:director,admin,editor'])->group(...)
  + public routes outside that group
```

- **Controller**: thin — (1) authorize, (2) call Service, (3) return
  view/redirect. No business logic, no direct DB queries.
- **Form Request**: validates + authorizes (`authorize()` checks Policy).
  Named `{Action}{Model}Request` (`StoreArticleRequest`).
- **Service**: business logic, calls Repository. Verb-named methods
  (`ArticleService::publish(Article $article)`).
- **Repository**: only place raw Eloquent query-building happens besides
  simple model relationship calls. Interface + implementation, bound in
  `RepositoryServiceProvider`.
- **Policy**: gate for every action — checked in Controller via
  `$this->authorize()`, mirrored in Blade with `@can` (UX only, never the
  real gate).
- **Resource** (if JSON shape needed): hides internal `id`, exposes
  `uuid`/`slug`.

Standard controller method set: `index, create, store, show, edit, update,
destroy` (+ custom actions like `publish`/`unpublish` as separate
route+method, never overload `update`).

Everything lives under `app/Modules/{Module}/...`.
