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
};
