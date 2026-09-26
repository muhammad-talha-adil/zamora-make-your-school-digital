---
name: caching
description: How to cache Repository list/detail queries per docs/CLAUDE.md Section 13. Use whenever writing or reviewing a Repository's public-facing query methods.
---

# Cache standard (docs/CLAUDE.md Section 13)

- Every Repository method that serves a public list or detail page
  (`paginatePublished()`, `findPublishedBySlug()`, etc.) goes through
  `App\Support\CacheService` — never a raw uncached query for a
  public-facing read.
- Cache key pattern: `{module}:{type}:{identifier}` e.g.
  `articles:list:page-1`, `article:slug:how-brain-works`.
- TTL: 1 hour for lists, 24h for single published content.
- Invalidation: explicit, on save/publish/delete — via a model
  `saved`/`deleted` event calling `CacheService::forget()`, which is the
  one place that knows which keys a given model touches. Never rely on
  TTL expiry alone for content that was just edited.
- Driver: Redis in every environment (Section 13), one connection,
  separate DB indexes (0 cache, 1 sessions, 2 queues) — never the
  `database` cache driver for anything wrapped by `CacheService`.
- Eager-load relationships (`->with([...])`) inside the Repository layer
  only, regardless of caching — avoids N+1 whether or not the result
  ends up cached.

If `App\Support\CacheService` doesn't exist yet, that's a signal this
skill was never actually applied to any Repository built so far — build
the service first (register/forget/remember wrapping `Cache::` with the
key pattern above), then retrofit every existing Repository to use it in
the same pass, not module-by-module over time.
