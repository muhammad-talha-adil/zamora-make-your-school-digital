import Lenis from 'lenis';

let lenis: Lenis | null = null;
let rafId: number | null = null;

/**
 * Lazily starts a single shared Lenis instance for smooth-scrolling the
 * public marketing pages. Scoped to wherever this is called from (the
 * public Home page), not initialized globally in app.ts, so the dashboard's
 * data-table-heavy screens keep native scroll behavior.
 */
export function useLenis() {
    if (typeof window === 'undefined') {
        return { scrollTo: () => {} };
    }

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!lenis && !prefersReducedMotion) {
        lenis = new Lenis({
            duration: 1.1,
            easing: (t: number) => 1 - Math.pow(1 - t, 3),
            smoothWheel: true,
        });

        const raf = (time: number) => {
            lenis?.raf(time);
            rafId = requestAnimationFrame(raf);
        };
        rafId = requestAnimationFrame(raf);
    }

    const scrollTo = (target: string | HTMLElement) => {
        if (lenis) {
            lenis.scrollTo(target, { offset: -72, duration: 1.1 });
        } else {
            const el = typeof target === 'string' ? document.querySelector(target) : target;
            el?.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', block: 'start' });
        }
    };

    return { scrollTo };
}

export function destroyLenis() {
    if (rafId) {
        cancelAnimationFrame(rafId);
        rafId = null;
    }
    lenis?.destroy();
    lenis = null;
}
