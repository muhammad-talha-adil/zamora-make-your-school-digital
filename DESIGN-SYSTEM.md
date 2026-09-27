# Design System Specification

## 1. Color Palette

### Light Mode (CSS Variables on :root)

| Token | Hex | Usage |
|-------|-----|-------|
| --background | #f8fafc | Page background |
| --foreground | #0b1220 | Primary text |
| --card | #ffffff | Card/surface background |
| --card-foreground | #0b1220 | Card text |
| --popover | #ffffff | Popover background |
| --popover-foreground | #0b1220 | Popover text |
| --primary | #0f172a | Primary actions, buttons |
| --primary-foreground | #f8fafc | Text on primary |
| --secondary | #f1f5f9 | Secondary backgrounds |
| --secondary-foreground | #0f172a | Secondary text |
| --muted | #f1f5f9 | Muted backgrounds |
| --muted-foreground | #64748b | Muted text |
| --accent | #f1f5f9 | Accent backgrounds |
| --accent-foreground | #0f172a | Accent text |
| --destructive | #dc2626 | Destructive actions |
| --destructive-foreground | #ffffff | Text on destructive |
| --success | #16a34a | Success states |
| --success-foreground | #ffffff | Text on success |
| --warning | #d97706 | Warning states |
| --warning-foreground | #ffffff | Text on warning |
| --info | #0891b2 | Info states |
| --info-foreground | #ffffff | Text on info |
| --border | #e2e8f0 | Borders |
| --input | #e2e8f0 | Input borders |
| --ring | #94a3b8 | Focus rings |

### Dark Mode (CSS Variables on .dark)

| Token | Hex | Usage |
|-------|-----|-------|
| --background | #070b12 | Page background |
| --foreground | #e5e7eb | Primary text |
| --card | #0f172a | Card/surface background |
| --card-foreground | #e5e7eb | Card text |
| --popover | #0f172a | Popover background |
| --popover-foreground | #e5e7eb | Popover text |
| --primary | #e2e8f0 | Primary actions |
| --primary-foreground | #0f172a | Text on primary |
| --secondary | #1e293b | Secondary backgrounds |
| --secondary-foreground | #e5e7eb | Secondary text |
| --muted | #1e293b | Muted backgrounds |
| --muted-foreground | #94a3b8 | Muted text |
| --accent | #1e293b | Accent backgrounds |
| --accent-foreground | #e5e7eb | Accent text |
| --destructive | #ef4444 | Destructive actions |
| --destructive-foreground | #ffffff | Text on destructive |
| --success | #22c55e | Success states |
| --success-foreground | #0b1220 | Text on success |
| --warning | #f59e0b | Warning states |
| --warning-foreground | #0b1220 | Text on warning |
| --info | #06b6d4 | Info states |
| --info-foreground | #0b1220 | Text on info |
| --border | #1e293b | Borders |
| --input | #1e293b | Input borders |
| --ring | #475569 | Focus rings |

### CSS Variable Names (Tailwind v4 @theme in resources/css/app.css)

@theme inline {
    --color-background: var(--background);
    --color-foreground: var(--foreground);
    --color-card: var(--card);
    --color-card-foreground: var(--card-foreground);
    --color-popover: var(--popover);
    --color-popover-foreground: var(--popover-foreground);
    --color-primary: var(--primary);
    --color-primary-foreground: var(--primary-foreground);
    --color-secondary: var(--secondary);
    --color-secondary-foreground: var(--secondary-foreground);
    --color-muted: var(--muted);
    --color-muted-foreground: var(--muted-foreground);
    --color-accent: var(--accent);
    --color-accent-foreground: var(--accent-foreground);
    --color-destructive: var(--destructive);
    --color-destructive-foreground: var(--destructive-foreground);
    --color-success: var(--success);
    --color-success-foreground: var(--success-foreground);
    --color-warning: var(--warning);
    --color-warning-foreground: var(--warning-foreground);
    --color-info: var(--info);
    --color-info-foreground: var(--info-foreground);
    --color-border: var(--border);
    --color-input: var(--input);
    --color-ring: var(--ring);
    --color-chart-1: var(--chart-1);
    --color-chart-2: var(--chart-2);
    --color-chart-3: var(--chart-3);
    --color-chart-4: var(--chart-4);
    --color-chart-5: var(--chart-5);
    --color-sidebar: var(--sidebar-background);
    --color-sidebar-foreground: var(--sidebar-foreground);
    --color-sidebar-primary: var(--sidebar-primary);
    --color-sidebar-primary-foreground: var(--sidebar-primary-foreground);
    --color-sidebar-accent: var(--sidebar-accent);
    --color-sidebar-accent-foreground: var(--sidebar-accent-foreground);
    --color-sidebar-border: var(--sidebar-border);
    --color-sidebar-ring: var(--sidebar-ring);
}

### Chart Colors (Light/Dark)

| Chart | Light | Dark |
|-------|-------|------|
| --chart-1 | #2563eb | #60a5fa |
| --chart-2 | #0d9488 | #2dd4bf |
| --chart-3 | #f59e0b | #fbbf24 |
| --chart-4 | #7c3aed | #a78bfa |
| --chart-5 | #db2777 | #f472b6 |

---

## 2. Typography

### Font Families

| Role | Font Stack |
|------|------------|
| Sans (Heading/Body) | Instrument Sans, ui-sans-serif, system-ui, sans-serif, Apple Color Emoji, Segoe UI Emoji, Segoe UI Symbol, Noto Color Emoji |
| Mono | Not explicitly defined (falls back to system monospace) |

Defined in resources/css/app.css.

### Type Scale

| Size | Class | Usage |
|------|-------|-------|
| h1 | text-4xl lg:text-5xl font-bold | Page heroes (AuthLayout) |
| h2 | text-3xl font-bold | School name (SchoolBrandHeader) |
| h3 | text-2xl font-semibold | Card titles, form headers |
| h4 | text-xl font-semibold | Section headings (Heading.vue) |
| h5 | text-lg font-semibold | Sub-sections |
| h6 | text-base font-medium | Small headings (HeadingSmall.vue) |
| body-lg | text-lg | Large body text |
| body | text-base / text-sm | Default body (varies by component) |
| body-sm | text-sm | Form labels, table cells |
| caption | text-xs | Table headers, metadata, badges |

### Font Weights

| Weight | Class | Value |
|--------|-------|-------|
| Normal | font-normal | 400 |
| Medium | font-medium | 500 |
| Semibold | font-semibold | 600 |
| Bold | font-bold | 700 |

---

## 3. Spacing & Layout

### Spacing Scale
Standard Tailwind v4 spacing scale (rem-based):
- Base unit: 0.25rem (4px)
- Scale: 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 5, 6, 7, 8, 9, 10, 11, 12, 14, 16, 20, 24, 28, 32, 36, 40, 44, 48, 52, 56, 60, 64, 72, 80, 96

### Container Max-Widths

| Container | Class | Max-Width |
|-----------|-------|-----------|
| Settings | max-w-7xl | 80rem (1280px) |
| Auth Form | max-w-6xl | 72rem (1152px) |
| Dialog | sm:max-w-lg | 32rem (512px) |
| Sheet | sm:max-w-sm | 24rem (384px) |
| Content | w-full | Full width inside shell |

### Breakpoints

| Breakpoint | Class | Min-Width |
|------------|-------|-----------|
| sm | sm: | 640px |
| md | md: | 768px |
| lg | lg: | 1024px |
| xl | xl: | 1280px |
| 2xl | 2xl: | 1536px |

### Grid System
- Sidebar: Fixed width w-64 (16rem/256px) expanded, w-16 (4rem/64px) collapsed
- Mobile sidebar: SIDEBAR_WIDTH_MOBILE = 280px
- Content area: flex-1 min-w-0 with p-4 sm:p-6
- Auth layout: Two-column lg:grid-cols-2 with vertical divider

### Border Radius
--radius: 0.5rem;           /* 8px base */
--radius-lg: var(--radius); /* 8px */
--radius-md: calc(var(--radius) - 2px); /* 6px */
--radius-sm: calc(var(--radius) - 4px); /* 4px */

---

## 4. Component Inventory

| Component | Location | Status | Notes |
|-----------|----------|--------|-------|
| Button | ui/button/Button.vue | OK | Variants: default, destructive, outline, secondary, ghost, link. Sizes: default, sm, lg, icon, icon-sm, icon-lg. |
| Input | ui/input/Input.vue | OK | Standard form input with border-input, bg-transparent, focus ring. |
| Label | ui/label/Label.vue | OK | Reka-ui Label wrapper, text-sm font-medium, disabled state handling. |
| Card | ui/card/Card.vue | OK | bg-card text-card-foreground rounded-xl border py-6 shadow-sm. Sub-components: CardHeader, CardTitle, CardDescription, CardContent, CardFooter, CardAction. |
| Badge | ui/badge/Badge.vue | OK | Variants: default, secondary, destructive, outline. rounded-full px-2 py-0.5 text-xs. |
| Table | tables/*.vue | OK | Composable pattern: RowAction, RowActions, StatusToggle, TablePagination. |
| Modal/Dialog | ui/dialog/Dialog.vue | OK | Reka-ui based. DialogContent centered, max-h-[calc(100dvh-2rem)], scrollable. |
| Sheet | ui/sheet/Sheet.vue | OK | Side panels (right/left/top/bottom). Slide animations. Width w-3/4 sm:max-w-sm. |
| Dropdown | ui/dropdown-menu/*.vue | OK | Reka-ui DropdownMenu. Trigger, Content, Item, Sub, Separator, CheckboxItem, RadioItem, Shortcut. |
| Tabs | — | MISSING | Not found. Use custom tab implementations or AppearanceTabs pattern. |
| Tooltip | ui/tooltip/*.vue | OK | Reka-ui Tooltip. Provider, Trigger, Content. Delay configurable. |
| Switch | ui/switch/Switch.vue | OK | Reka-ui Switch. h-5 w-9, thumb size-4, uses --primary for checked state. |
| Checkbox | ui/checkbox/Checkbox.vue | OK | Reka-ui Checkbox. size-4 rounded-[4px], check icon size-3.5. |
| Select/Combobox | ui/combobox/ComboboxInput.vue, ui/searchable-select/SearchableSelect.vue | OK | Two implementations: custom ComboboxInput (with server search) and Reka-ui based SearchableSelect. |
| Avatar | ui/avatar/Avatar.vue | OK | size-8 rounded-full overflow-hidden. Sub-components: AvatarImage, AvatarFallback. |
| Form Components | forms/*.vue | OK | Domain-specific forms: StudentForm, SchoolForm, CampusForm, SubjectForm, etc. |
| Data Display | tables/*.vue, dashboard/*.vue | OK | StatsCard, RecentActivity, AttendanceTable, various *Table components. |
| Navigation | layout/Sidebar.vue, layout/HeaderBar.vue, ui/navigation-menu/*.vue, ui/breadcrumb/*.vue, AppShell.vue | OK | Collapsible sidebar with nested menus. NavigationMenu for top nav. Breadcrumb with separator, ellipsis. |
| Skeleton | ui/skeleton/Skeleton.vue | OK | animate-pulse rounded-md bg-primary/10. |
| Spinner | ui/spinner/Spinner.vue | OK | Loader2Icon with animate-spin size-4. |
| Separator | ui/separator/Separator.vue | OK | Reka-ui Separator. bg-border, horizontal/vertical. |
| Alert | ui/alert/Alert.vue | OK | Variants: default, destructive. Grid layout with icon. role=alert. |
| Input OTP | ui/input-otp/*.vue | OK | vue-input-otp wrapper. Slots: InputOTPGroup, InputOTPSlot, InputOTPSeparator. |
| Collapsible | ui/collapsible/*.vue | OK | Reka-ui Collapsible. Trigger, Content. |
| Date Range Picker | ui/date-range-picker/DateRangePicker.vue | OK | Custom implementation. |

---

## 5. Motion Tokens

### Durations
| Token | Duration | Usage |
|-------|----------|-------|
| instant | duration-75 | Not explicitly used |
| fast | duration-150 / duration-200 | Sidebar transitions, tooltip delay (150ms), hover transitions |
| normal | duration-200 / duration-300 | Dialog fade/zoom, sheet slide, sidebar width |
| slow | duration-500 | Sheet open (duration-500), dialog open |

### Easings
| Token | Easing | Usage |
|-------|--------|-------|
| default | ease-in-out | Dialog, sheet, sidebar |
| linear | ease-linear | Sidebar width transition |
| spring | Not used | — |
| bounce | Not used | — |

### Animation Classes (from tw-animate-css import)
Available via tw-animate-css (imported in app.css):
- animate-in, animate-out
- fade-in-0, fade-out-0
- zoom-in-95, zoom-out-95
- slide-in-from-right, slide-out-to-right
- slide-in-from-left, slide-out-to-left
- slide-in-from-top, slide-out-to-top
- slide-in-from-bottom, slide-out-to-bottom

Used in DialogContent, SheetContent, DialogOverlay.

---

## 6. Dark Mode Strategy

### Current Implementation
File: resources/js/composables/useAppearance.ts

1. Three modes: light, dark, system
2. Persistence: localStorage + cookie (max-age=365 days)
3. Application: Toggles .dark class on document.documentElement, applies palette tokens as inline styles via applyTheme()
4. Initialization: initializeTheme() sets up media query listener
5. Palette Source: Server-provided themes prop (from Theme Settings) with per-mode color JSON

### CSS Variable Approach
- Base tokens defined in app.css on :root and .dark
- Runtime overrides applied as inline styles on documentElement.style
- MANAGED_TOKENS array controls which tokens can be overridden
- Unset palette slots fall back to app.css defaults

### Toggle Mechanism
updateAppearance(value: light | dark | system)
- Updates appearance ref
- Persists to localStorage + cookie
- Calls updateTheme() which toggles .dark class and applies palette
- UI: AppearanceTabs.vue (Light/Dark/System buttons), sidebar theme toggle button

---

## 7. Accessibility Baseline

### Focus Ring Strategy
- Global: focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]
- Destructive: aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive
- Buttons: Built into buttonVariants base class
- Inputs: Built into Input.vue class
- Dialog/Sheet close: focus:ring-2 focus:ring-offset-2 focus:outline-hidden
- Pagination: focus-visible:ring-2 via Button variants

### ARIA Patterns in Use
| Pattern | Implementation |
|---------|----------------|
| Dialog | role=dialog via Reka-ui, aria-modal=true, aria-labelledby/aria-describedby |
| Sheet | role=dialog via Reka-ui, side positioning |
| Tooltip | role=tooltip via Reka-ui, TooltipProvider |
| Dropdown | role=menu via Reka-ui, keyboard navigation |
| Combobox | role=combobox via Reka-ui, aria-expanded, aria-controls |
| Switch | role=switch via Reka-ui, aria-checked |
| Checkbox | role=checkbox via Reka-ui, aria-checked |
| Alert | role=alert on Alert component |
| Breadcrumb | aria-label=breadcrumb on nav |
| Tooltip on RowAction | TooltipTrigger wraps button/link, TooltipContent provides label |
| Table headers | th scope=col with text-xs font-semibold tracking-wider text-muted-foreground uppercase |

### Semantic HTML Compliance
- Landmarks: header, main, aside, nav, footer (FooterBar)
- Headings: Hierarchical h1-h3 usage (Heading, HeadingSmall, page titles)
- Forms: label associated via Reka-ui Label, InputError for validation messages
- Tables: thead, tbody, th scope=col, td
- Buttons: button for actions, Link as=button for Inertia form submissions
- Links: Link for navigation, a for external

### Color Contrast
- Token pairs ensure contrast: foreground/background, card-foreground/card, primary-foreground/primary, destructive-foreground/destructive, success-foreground/success, etc.
- Muted foreground: color-mix(in srgb, onSurface 60%, surface) — ~60% opacity
- Border: color-mix(in srgb, onSurface 14%, surface) — subtle

---

## 8. School Branding Tokens

### Available from School Model (app/Models/School.php)

protected  = [
    name,           // School name
    slogan,         // Tagline/slogan
    address,        // Physical address
    phone,          // Contact phone
    bank_name,      // Bank details for fees
    bank_account_title,
    bank_account_no,
    bank_branch,
    logo_path,      // Logo image path
    is_active,      // Boolean
    website_enabled, // Boolean
];

### Exposed to Frontend

Via Inertia props (in AuthLayout.vue props):
defineProps<{
    school?: {
        name: string;
        logo_path?: string;
        tagline?: string;  // maps to slogan
    };
}>();

Usage in components:
- SchoolBrandHeader.vue — displays logo (16x16 rounded-full), name (text-3xl), tagline (text-lg muted)
- AppShell.vue — schoolName computed from pageProps.value.name
- Theme Settings — palette colors per school (stored in themes prop)

### How to Expose More Fields
1. Add to  in School model
2. Include in resource/transformer for Inertia responses
3. Access via pageProps.value.school?.field

---

## 9. Gaps & Recommendations

### Missing Components Needed for Redesign

| Component | Priority | Notes |
|-----------|----------|-------|
| Tabs | High | No reusable Tabs component. AppearanceTabs is a one-off. Need Tabs, TabsList, TabsTrigger, TabsContent with keyboard navigation. |
| Textarea | High | No ui/textarea component. Forms use raw textarea or custom. |
| Radio Group | Medium | No RadioGroup. Checkbox exists. |
| Select (native) | Medium | Only Combobox/SearchableSelect. Native select wrapper missing. |
| Form Field Wrapper | High | Repeated pattern: Label + Input + InputError. Create FormField component. |
| Data Table | Medium | Tables are copy-pasted with min-w-full divide-y divide-border. Extract DataTable with columns, sorting, selection. |
| Toast/Notification | High | No toast system. Alerts used inline only. |
| Command Palette | Low | No Cmd+K search. |
| Progress | Medium | No progress bar (linear/circular). Spinner only. |
| Slider/Range | Low | Not needed currently. |
| Calendar/Date Picker | Medium | DateRangePicker exists but no single date picker. |

### Inconsistencies to Fix

| Issue | Location | Fix |
|-------|----------|-----|
| Input heights | Input.vue uses h-9, ComboboxInput uses h-10, SearchableSelect anchor uses h-10 | Standardize on h-10 (40px) for all form controls |
| Focus rings | Button: ring-[3px], Input: ring-[3px], Dialog close: ring-2 | Standardize on ring-2 (2px) or ring-[3px] consistently |
| Border radius | Card: rounded-xl (12px), Input: rounded-md (6px), Button: rounded-md (6px), Badge: rounded-full | Document radius scale: --radius-sm (4px), --radius-md (6px), --radius-lg (8px), --radius-xl (12px), --radius-full |
| Sidebar tokens | AppShell.vue uses inline --sidebar-bg etc., but ui/sidebar/Sidebar.vue uses bg-sidebar Tailwind classes | Unify: prefer Tailwind classes from @theme (bg-sidebar, text-sidebar-foreground) over inline styles |
| Heading sizes | Heading.vue: text-xl, HeadingSmall.vue: text-base, AuthLayout: text-4xl lg:text-5xl | Create H1–H6 components or standardize heading scale |
| Container padding | AppShell main: p-4 sm:p-6, SettingsLayout: px-4 sm:px-6 lg:px-8 | Standardize page container padding |

### New Components to Build

1. FormField — Label + Input/Textarea/Select + InputError + description slot
2. Tabs — Keyboard-navigable, animated indicator (like NavigationMenu)
3. Toast — Portal-based, auto-dismiss, action support, Promise API
4. DataTable — Column defs, sorting, filtering, pagination, row selection, virtualized
5. Textarea — Match Input styling, auto-resize option
6. RadioGroup — Match Checkbox styling
7. Select — Native select wrapper with consistent styling
8. Progress — Linear and circular, indeterminate support
9. DropdownMenu — Already has Reka-ui base; create Dropdown wrapper with common patterns
10. Popover — Reka-ui Popover wrapper for complex triggers

### Architecture Recommendations

1. Consolidate Sidebar Implementations - Two sidebar systems: ui/sidebar/* (Reka-ui based, modern) and layout/AppShell.vue (custom, legacy). Migrate AppShell to use ui/sidebar components.
2. Extract Design Tokens to JSON - Move paletteTokens() derivation to shared config. Generate CSS variables and Tailwind config from single source.
3. Add Component Documentation - Storybook or Vue component preview pages. Prop tables, variant examples, accessibility notes.
4. Standardize Animation Patterns - Create useTransition composable for consistent enter/leave. Define motion tokens as CSS custom properties.
5. Theme Settings Integration - Document how themes prop maps to CSS variables. Add TypeScript types for palette slots.

---

*Generated from codebase analysis on 2026-09-27. Run npm run build after any CSS changes to regenerate Tailwind utilities.*