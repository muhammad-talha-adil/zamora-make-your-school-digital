import { ref } from 'vue';

/**
 * Animates a number from 0 to `target` over `duration`ms using
 * requestAnimationFrame, driven externally (call `start()` once, e.g. when a
 * `useScrollReveal` target becomes visible) rather than on mount, so it plays
 * when the stat actually enters view instead of before the page is scrolled.
 */
export function useCountUp(target: number, duration = 1200) {
    const value = ref(0);
    let frame: number | null = null;
    let started = false;

    const start = () => {
        if (started) {
            return;
        }
        started = true;

        if (typeof window === 'undefined' || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            value.value = target;
            return;
        }

        const startTime = performance.now();

        const tick = (now: number) => {
            const progress = Math.min((now - startTime) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            value.value = Math.round(target * eased);

            if (progress < 1) {
                frame = requestAnimationFrame(tick);
            }
        };

        frame = requestAnimationFrame(tick);
    };

    return { value, start, cancel: () => frame && cancelAnimationFrame(frame) };
}
