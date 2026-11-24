/**
 * Global guard: number inputs never accept a minus sign or negative value,
 * unless the input opts in with data-allow-negative or has a negative min.
 */
const isGuarded = (el: EventTarget | null): el is HTMLInputElement => {
    if (!(el instanceof HTMLInputElement) || el.type !== 'number') {
        return false;
    }

    if (el.hasAttribute('data-allow-negative')) {
        return false;
    }

    return el.min === '' || Number(el.min) >= 0;
};

export function blockNegativeNumbers(): void {
    document.addEventListener('keydown', (event) => {
        if (isGuarded(event.target) && (event.key === '-' || event.key === 'Subtract')) {
            event.preventDefault();
        }
    });

    document.addEventListener('paste', (event) => {
        if (isGuarded(event.target) && (event.clipboardData?.getData('text') ?? '').includes('-')) {
            event.preventDefault();
        }
    });

    document.addEventListener('input', (event) => {
        const el = event.target;

        if (isGuarded(el) && el.value.startsWith('-')) {
            el.value = el.value.replace(/-/g, '');
            el.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });

    const setMin = (): void => {
        document.querySelectorAll<HTMLInputElement>('input[type="number"]:not([min]):not([data-allow-negative])').forEach((el) => {
            el.min = '0';
        });
    };

    new MutationObserver(setMin).observe(document.body, { childList: true, subtree: true });
    setMin();
}
