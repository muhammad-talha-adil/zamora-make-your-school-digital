import { onMounted, onBeforeUnmount, ref, type Ref } from 'vue';

/**
 * Reveals an element (fade + slide-up) the first time it enters the viewport,
 * then stops observing it. Returns a ref to attach to the target element plus
 * a boolean ref reflecting whether it has been revealed, so templates can
 * toggle reveal classes without fighting Tailwind's JIT over dynamic strings.
 */
export function useScrollReveal(threshold = 0.15) {
    const target: Ref<HTMLElement | null> = ref(null);
    const revealed = ref(false);
    let observer: IntersectionObserver | null = null;

    onMounted(() => {
        if (!target.value) {
            return;
        }

        if (typeof IntersectionObserver === 'undefined') {
            revealed.value = true;
            return;
        }

        observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    revealed.value = true;
                    observer?.unobserve(entry.target);
                }
            });
        }, { threshold });

        observer.observe(target.value);
    });

    onBeforeUnmount(() => {
        observer?.disconnect();
    });

    return { target, revealed };
}
