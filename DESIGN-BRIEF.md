# Design Brief: MJS School Management System - Public Website

## Design Read
Trust-first B2B SaaS landing page for education sector. Calm, professional, academic vibe with restrained motion. Clean, structured visual language (VARIANCE 5) using Tailwind v4 + shadcn/ui patterns + CSS variable theming.

## Dial Settings
VARIANCE: 5 / MOTION: 4 / DENSITY: 4

## Visual Language

### Color Approach
**Primary Accent**: `--primary` (#0f172a light / #e2e8f0 dark) — deep slate for primary actions, buttons, key UI elements. Not the blue from context (#3B82F6) — the design system uses slate primary.

**Neutrals (Zinc/Slate)**:
- Background: `--background` (#f8fafc light / #070b12 dark)
- Foreground: `--foreground` (#0b1220 light / #e5e7eb dark)
- Card: `--card` (#ffffff light / #0f172a dark)
- Muted: `--muted` (#f1f5f9 light / #1e293b dark)
- Muted Foreground: `--muted-foreground` (#64748b light / #94a3b8 dark)
- Border: `--border` (#e2e8f0 light / #1e293b dark)
- Input: `--input` (#e2e8f0 light / #1e293b dark)
- Ring: `--ring` (#94a3b8 light / #475569 dark)

**Semantic Colors** (available for status, alerts, charts):
- Destructive: #dc2626 / #ef4444
- Success: #16a34a / #22c55e
- Warning: #d97706 / #f59e0b
- Info: #0891b2 / #06b6d4
- Chart 1–5: #2563eb/#60a5fa, #0d9488/#2dd4bf, #f59e0b/#fbbf24, #7c3aed/#a78bfa, #db2777/#f472b6

**Dark Mode**: Full support via `.dark` class toggle on `<html>`. All tokens defined as CSS variables on `:root` and `.dark` in `resources/css/app.css`. Runtime overrides applied inline via `useAppearance.ts` (localStorage + cookie persistence, system preference listener).

### Typography
**Font Families**:
- Sans (all headings + body): `Instrument Sans`, ui-sans-serif, system-ui, sans-serif
- Serif (display, currently AuthLayout only): `Instrument Serif` — **decision**: restrict to Auth pages only; public marketing pages use Instrument Sans exclusively for consistency and trust signal.

**Type Scale** (Tailwind classes from design system):
- H1: `text-4xl lg:text-5xl font-bold` — Page heroes
- H2: `text-3xl font-bold` — School name, major section headers
- H3: `text-2xl font-semibold` — Card titles, form headers
- H4: `text-xl font-semibold` — Section headings (Heading.vue)
- H5: `text-lg font-semibold` — Sub-sections
- H6: `text-base font-medium` — Small headings (HeadingSmall.vue)
- Body Large: `text-lg` — Lead paragraphs
- Body: `text-base` / `text-sm` — Default body
- Body Small: `text-sm` — Form labels, table cells
- Caption: `text-xs` — Table headers, metadata, badges

**Weights**: Normal (400), Medium (500), Semibold (600), Bold (700)

### Materiality
- **Cards**: `bg-card text-card-foreground rounded-xl border py-6 shadow-sm` (Card.vue) — 12px radius, subtle border, minimal shadow
- **Glass Morphism**: AuthLayout only — `backdrop-blur-lg` with semi-transparent card backgrounds. Public pages use solid cards.
- **Borders**: `--border` token, 1px default. `border-border` utility.
- **Shadows**: `shadow-sm` default on cards. No heavy elevation. Focus rings: `ring-ring/50` (light) / `ring-ring/40` (dark) at 3px.
- **Inputs**: `bg-transparent border-input focus-visible:ring-ring/50` — transparent bg, border from token, visible focus.

### Shape Consistency
**Radius Scale** (CSS variables in app.css):
- `--radius`: 0.5rem (8px) — base
- `--radius-lg`: 8px
- `--radius-md`: 6px (calc(var(--radius) - 2px))
- `--radius-sm`: 4px (calc(var(--radius) - 4px))
- `--radius-xl`: 12px (Card.vue uses `rounded-xl`)
- `--radius-full`: 9999px (Badge, Avatar)

**Usage**:
- Buttons: `rounded-md` (6px)
- Inputs: `rounded-md` (6px) — standardize to h-10 (40px) height
- Cards: `rounded-xl` (12px)
- Badges/Pills: `rounded-full`
- Dialogs/Sheets: inherit from Card or use `rounded-lg` (8px)

## Page Architecture

### Home Page Sections (in order)

1. **Hero** — Split-screen (left: value prop + CTA, right: school illustration/dashboard preview)
   - Viewport-height min, CTA visible without scroll
   - H1: `text-4xl lg:text-5xl font-bold` (Instrument Sans)
   - Subtext: `text-lg text-muted-foreground`
   - Primary CTA: Button `default` (bg-primary text-primary-foreground h-10 px-6)
   - Secondary CTA: Button `ghost` (same height)
   - Entrance stagger: headline ? subtext ? CTAs ? illustration (150ms base, 60ms stagger)

2. **Trust/Logo Wall** — Real school logos (monochrome, max-height 40px, grayscale filter, opacity-60 hover:opacity-100)
   - Horizontal scroll on mobile, grid on desktop
   - `max-w-7xl` container, `gap-8 md:gap-12`

3. **Core Modules** — 6-card grid (Attendance, Exams, Fees, Staff, Transport, Inventory)
   - Card.vue base, `h-full`, hover: `shadow-md transition-shadow duration-200`
   - Icon (lucide, size-6) + H4 title + 2-line description + "Learn more" ghost link
   - Stagger scale-in: 60ms delay per card (tw-animate-css `zoom-in-95`)

4. **Feature Deep-dive** — Alternating split layouts (1 per module, 6 total)
   - Left: illustration/screenshot (rounded-xl, aspect-video), Right: H3 + body + bullet list + CTA
   - Reverse order on alternate sections
   - Scroll-reveal: `whileInView` trigger, fade-up 300ms

5. **Social Proof** — Testimonials (3 max, 3 lines each)
   - Card with avatar (initials fallback), name, role, school name
   - Quote text: `text-base text-foreground/90`
   - Carousel on mobile, 3-up grid desktop

6. **Pricing/Plans** — 3 tiers (Starter, Professional, Enterprise) or "Contact for pricing"
   - Card.vue, highlighted tier: `ring-2 ring-primary`
   - Price: `text-4xl font-bold`, period: `text-muted-foreground`
   - Feature list: check icons (lucide Check, size-4 text-success)
   - CTA per tier: Button `default` (primary) for highlighted, `outline` for others

7. **FAQ** — Accordion (Collapsible.vue base)
   - H4 questions, body answers
   - Chevron icon rotates on open (transition-transform duration-200)
   - One open at a time (exclusive)

8. **Footer CTA** — Newsletter + Login link
   - Dark background (`bg-primary` / `bg-background` dark), white/foreground text
   - Email input + Button `default` inline
   - Links: Product, Company, Resources, Legal (4 columns)
   - Copyright + social icons

### About Page
- **Mission/Vision/Values** — 3-column grid (Card.vue, text-center), H3 + body each
- **Leadership Team** — 4–6 cards: Avatar (size-24) + name (H5) + role (muted) + bio (2 lines)
- **Accreditations/Stats** — 4 counter cards (StatCounter component): `text-4xl lg:text-5xl font-bold` numbers, label below
- **History Timeline** — Vertical timeline (Timeline component): dot + line connector, alternating left/right content cards on desktop, stacked mobile

### Contact Page
- **Contact Form** — FormField wrapper (Label + Input/Textarea + InputError), fields: Name, Email, Subject (Select), Message (Textarea), reCAPTCHA/honeypot
  - Submit: Button `default` full-width mobile, auto desktop
  - States: loading (Spinner), success (Toast), error (Alert destructive)
- **Info Cards** — 4 cards (Phone, Email, Address, Hours): Icon (size-5) + label (caption) + value (body)
- **Map Embed** — iframe (Google Maps/OpenStreetMap), aspect-video, rounded-xl, border-border
- **FAQ Accordion** — Same as Home, contact-specific questions

### Auth Pages (Login, Register, Forgot, Reset, Verify, 2FA)
- **Layout**: Refactored AuthLayout ? `AuthSplitLayout` component
- **Left Panel** (lg:w-1/2): Glass morphism preserved — `bg-white/10 dark:bg-primary/10 backdrop-blur-lg border-r border-border/50`
  - School branding: Logo (h-12 w-12 rounded-full), Name (H2), Slogan (text-lg muted)
  - Dynamic illustration per page (SVG, aspect-square, opacity-80)
  - Page-specific micro-copy (e.g., "Welcome back" / "Create your account")
- **Right Panel** (lg:w-1/2): Form in Card (`p-8`, `max-w-md mx-auto`)
  - Full state cycle: empty ? loading (Spinner in button) ? error (Alert) ? success (Toast + redirect)
  - Consistent CTA labels: "Sign in" / "Create account" / "Reset password" / "Verify email" / "Enable 2FA"
  - Link to alternate auth page: "Don't have an account? Sign up" (Button `link`)
- **Reduced Motion**: `@media (prefers-reduced-motion: reduce)` — collapse all transitions to `duration-0`, disable stagger, instant panel switch

## Component Needs (New)

| Component | Base | Notes |
|-----------|------|-------|
| HeroSection | — | Composable: headline, subtext, primaryCTA, secondaryCTA, illustration slot |
| TrustLogos | — | Array of {src, alt, href?}, grayscale filter, horizontal scroll snap |
| FeatureGrid | Card | 6-up responsive (1/2/3/6 cols), stagger entrance |
| SplitFeature | Card | Image slot + content slot, reverse prop, scroll-reveal |
| TestimonialCard | Card | Avatar, quote, attribution, carousel integration |
| PricingCard | Card | Highlighted variant, feature list slot, CTA slot |
| Accordion | Collapsible | Exclusive mode, chevron rotation, keyboard (Enter/Space) |
| StatCounter | — | Number animation on scroll (IntersectionObserver), reduced motion: instant |
| Timeline | — | Vertical line (border-l border-border), dot (w-3 h-3 rounded-full bg-primary), alternating cards |
| ContactForm | FormField | Validation via Zod schema, honeypot, Toast on submit |
| MapEmbed | — | iframe wrapper, aspect-video, rounded-xl, lazy-load |
| AuthSplitLayout | AuthLayout | Props: leftSlot, rightSlot, schoolBrand, illustration; glass panel preserved |

## Motion Plan

| Trigger | Animation | Duration | Easing | Token |
|---------|-----------|----------|--------|-------|
| Page transition (Inertia) | Fade + slide-up | 200ms | ease-in-out | `duration-200 ease-in-out` |
| Hero entrance | Stagger (4 items) | 150ms base + 60ms/item | ease-out | `animate-in fade-in-0 slide-in-from-bottom-4` |
| Card grids (modules, testimonials) | Stagger scale-in | 60ms delay/item | ease-out | `animate-in zoom-in-95` |
| Section scroll-reveal | Fade-up on `whileInView` | 300ms | ease-out | `animate-in fade-in-0 slide-in-from-bottom-6` |
| Form focus | Ring expand | 150ms | ease-in-out | `focus-visible:ring-3 focus-visible:ring-ring/50` |
| Label float (FormField) | Translate Y + scale | 150ms | ease-out | CSS `:focus-within` + `peer-focus` |
| Button press | Scale 0.98 | 75ms | ease-in-out | `active:scale-[0.98]` |
| Auth panel switch | Slide horizontal | 300ms | ease-in-out | `slide-in-from-right` / `slide-out-to-left` |
| Accordion open | Height auto + chevron rotate | 200ms | ease-in-out | `animate-in slide-in-from-top-2` + `transition-transform` |
| Counter number | Count-up | 1000ms | ease-out | JS animation, respect reduced-motion |

**Reduced Motion**: All `animate-in`/`animate-out` classes wrapped in `@media (prefers-reduced-motion: no-preference)`. Collapsible/Accordion use CSS `transition: none` when reduced.

## Accessibility

- **Contrast**: All text meets WCAG AA (token pairs: foreground/background 12.6:1, muted-foreground/muted 4.5:1, primary-foreground/primary 15.8:1). Verify with Lighthouse.
- **Focus Visible**: Global `focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]` on all interactive elements (Button, Input, Link, Card actions).
- **ARIA**:
  - Dialog/Sheet: Reka-ui provides `role=dialog`, `aria-modal`, `aria-labelledby`
  - Accordion: `aria-expanded`, `aria-controls` on triggers, `id` on content
  - Tabs (future): `role=tablist`, `tab`, `tabpanel`, `aria-selected`
  - Tooltip: Reka-ui `role=tooltip`
  - Form errors: `aria-invalid`, `aria-describedby` ? InputError id
  - Carousel: `aria-roledescription=slide`, `aria-label` per slide
- **Semantic HTML**: `<header>`, `<main>`, `<section aria-labelledby>`, `<footer>`, `<nav>`, hierarchical headings (one H1 per page)
- **Forms**: Reka-ui Label association, InputError linked via `aria-describedby`, required = `aria-required`
- **Reduced Motion**: `@media (prefers-reduced-motion: reduce)` disables all `animate-*`, `transition-*` set to `0ms`, stagger removed, counters instant.

## Pre-Flight Checklist

- [ ] Hero fits viewport (min-h-[calc(100dvh-4rem)]), CTA visible without scroll
- [ ] Nav single line desktop (max-w-7xl, flex items-center justify-between, gap-4)
- [ ] No duplicate CTA intent (one "Get Started" per page, one "Sign In" in header)
- [ ] Eyebrow max 1 per 3 sections (category label above H2, e.g., "MODULES")
- [ ] No zigzag > 2 consecutive (alternating split layouts max 2 in a row, then full-width section)
- [ ] All images real (generated SVGs for illustrations, sourced school photos for testimonials)
- [ ] Copy audit: no AI tells ("seamless", "revolutionize", "unlock", "empower", "streamline")
- [ ] Test light + dark mode (all pages, all components, focus rings visible in both)
- [ ] Lighthouse > 90 (Performance, Accessibility, Best Practices, SEO)
- [ ] Keyboard navigation: Tab order logical, focus visible, Escape closes dialogs/sheets
- [ ] Screen reader: NVDA/VoiceOver test — landmarks, headings, form labels, live regions for toasts