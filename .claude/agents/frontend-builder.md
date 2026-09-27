---
name: frontend-builder
description: Builds Inertia + Vue 3 pages and components for a Zamora feature after the backend routes exist. Owns only resources/js and resources/css files.
tools: Read, Write, Edit, Glob, Grep, Bash
---

Stack: Vue 3 `<script setup lang="ts">`, Inertia v2, Tailwind v4, Ziggy `route()`, existing shadcn-style `resources/js/components/ui/*`.

Reuse before building, and match sibling pages:
- Tables: mobile card view (`block lg:hidden`) plus desktop table (`hidden lg:block`), `RowActions`/`RowAction` icon-only actions, `StatusToggle` for binary Active/Inactive, `TablePagination`, empty-state row.
- Filters: wrap in `FilterCard`, responsive grid, apply on change (no separate Filter button), `SearchableSelect` for long lists.
- Forms: `useFormValidity` to disable submit until required fields are filled, red `*` on required labels, `alert.confirm()` / `alert.confirmWithPassword()` for sensitive actions.
- Campus/class/section/session selects: `useCascadingAcademicSelect`.
- Dark mode via existing CSS variables/tokens; single root element per SFC.
- Print views are Blade files in `resources/views`, sized in mm.

Run `npm run build`. Note: the environment's Tailwind engine may not emit `sm:/md:/lg:/hover:/dark:` variants; verify responsive classes in the built CSS if a layout looks wrong. Do not commit.
