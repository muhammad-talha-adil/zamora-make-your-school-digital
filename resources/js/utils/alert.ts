import Swal from 'sweetalert2';

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

    confirm: (message: string, title = 'Are you sure?') => {
        return Swal.fire({
            title,
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
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
