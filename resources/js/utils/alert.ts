import Swal from 'sweetalert2';

/**
 * Reads a design token off the root element.
 *
 * SweetAlert takes button colours as plain strings rather than CSS, so the
 * current value has to be resolved at call time; hardcoding hexes here left
 * the dialogs on a fixed red/blue whatever palette the school had chosen.
 */
export const themeToken = (name: string, fallback: string): string => {
    if (typeof window === 'undefined') {
        return fallback;
    }

    const value = getComputedStyle(document.documentElement)
        .getPropertyValue(name)
        .trim();

    return value || fallback;
};

export const alert = {
    success: (message: string, title = 'Success') => {
        return Swal.fire({
            title,
            text: message,
            icon: 'success',
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end',
            customClass: {
                popup: 'swal2-toast',
            },
            didOpen: () => {
                const popup = Swal.getPopup();
                if (popup) {
                    popup.style.zIndex = '99999';
                }
            },
        });
    },

    error: (message: string, title = 'Error') => {
        return Swal.fire({
            title,
            text: message,
            icon: 'error',
            showConfirmButton: true,
            confirmButtonText: 'OK',
            allowOutsideClick: true,
            allowEscapeKey: true,
            backdrop: false,
            customClass: {
                popup: 'swal2-popup',
                confirmButton: 'swal2-confirm',
            },
            didOpen: () => {
                const popup = Swal.getPopup();
                if (popup) {
                    popup.style.zIndex = '999999';
                }
                const confirmBtn = Swal.getConfirmButton();
                if (confirmBtn) {
                    confirmBtn.style.zIndex = '999999';
                }
            },
        });
    },

    confirm: (message: string, title = 'Are you sure?', confirmText = 'Yes, delete it!') => {
        // `Swal.fire` always opens a brand-new dialog with its own promise —
        // nothing here is held between calls, so every invocation shows
        // fresh regardless of how many times the same or another action was
        // confirmed before it.
        return Swal.fire({
            title,
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: themeToken('--destructive', '#dc2626'),
            cancelButtonColor: themeToken('--primary', '#2563eb'),
            confirmButtonText: confirmText,
            customClass: {
                popup: 'swal2-popup',
                confirmButton: 'swal2-confirm',
                cancelButton: 'swal2-cancel',
            },
            didOpen: () => {
                const popup = Swal.getPopup();
                if (popup) {
                    popup.style.zIndex = '99999';
                }
            },
        });
    },

    /**
     * The same confirmation dialog as `confirm()`, plus a password field for
     * sensitive actions (Activate/Deactivate/Delete/status-change) that must
     * be re-verified server-side — see `RequiresPasswordConfirmation`.
     *
     * Resolves with `{ isConfirmed, password }` on confirm (SweetAlert2 only
     * closes the dialog once `preConfirm` validates a non-empty password),
     * or `{ isConfirmed: false }` when cancelled/dismissed. The caller sends
     * `password` along with the action request; a wrong password comes back
     * as a 422 with an error on the `password` field, which the caller
     * should surface via `alert.error(...)`.
     */
    confirmWithPassword: async (
        message: string,
        title = 'Are you sure?',
        confirmText = 'Yes, continue',
    ): Promise<{ isConfirmed: boolean; password: string }> => {
        const result = await Swal.fire<string>({
            title,
            html: `<p class="mb-3">${message}</p>`,
            icon: 'warning',
            input: 'password',
            inputPlaceholder: 'Enter your password to confirm',
            inputAttributes: {
                autocapitalize: 'off',
                autocomplete: 'current-password',
            },
            showCancelButton: true,
            confirmButtonColor: themeToken('--destructive', '#dc2626'),
            cancelButtonColor: themeToken('--primary', '#2563eb'),
            confirmButtonText: confirmText,
            customClass: {
                popup: 'swal2-popup',
                confirmButton: 'swal2-confirm',
                cancelButton: 'swal2-cancel',
            },
            preConfirm: (value) => {
                if (!value) {
                    Swal.showValidationMessage('Password is required to continue.');

                    return false;
                }

                return value;
            },
            didOpen: () => {
                const popup = Swal.getPopup();
                if (popup) {
                    popup.style.zIndex = '99999';
                }
            },
        });

        return {
            isConfirmed: result.isConfirmed,
            password: result.isConfirmed ? (result.value ?? '') : '',
        };
    },
};
