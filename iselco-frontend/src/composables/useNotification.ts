import Swal from 'sweetalert2'

/**
 * Notification Composable using SweetAlert2
 * 
 * Provides beautiful, prominent notifications for user feedback
 */
export function useNotification() {

    /**
     * Show success notification
     */
    function showSuccess(title: string, message?: string) {
        return Swal.fire({
            icon: 'success',
            title: title,
            text: message,
            confirmButtonText: 'OK',
            confirmButtonColor: '#14B8A6', // Teal color matching app theme
            timer: 3000,
            timerProgressBar: true,
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            }
        })
    }

    /**
     * Show error notification
     */
    function showError(title: string, message?: string) {
        return Swal.fire({
            icon: 'error',
            title: title,
            text: message,
            confirmButtonText: 'OK',
            confirmButtonColor: '#EF4444', // Red color
            showClass: {
                popup: 'animate__animated animate__shakeX'
            }
        })
    }

    /**
     * Show warning notification
     */
    function showWarning(title: string, message?: string) {
        return Swal.fire({
            icon: 'warning',
            title: title,
            text: message,
            confirmButtonText: 'OK',
            confirmButtonColor: '#F59E0B', // Yellow/Orange color
        })
    }

    /**
     * Show info notification
     */
    function showInfo(title: string, message?: string) {
        return Swal.fire({
            icon: 'info',
            title: title,
            text: message,
            confirmButtonText: 'Got it',
            confirmButtonColor: '#3B82F6', // Blue color
        })
    }

    /**
     * Show confirmation dialog
     */
    function showConfirm(title: string, message?: string, confirmText = 'Yes', cancelText = 'No') {
        return Swal.fire({
            icon: 'question',
            title: title,
            text: message,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            confirmButtonColor: '#14B8A6',
            cancelButtonColor: '#6B7280',
            reverseButtons: true,
        })
    }

    /**
     * Show loading notification
     */
    function showLoading(title: string, message?: string) {
        Swal.fire({
            title: title,
            text: message,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading()
            }
        })
    }

    /**
     * Close any open notification
     */
    function close() {
        Swal.close()
    }

    return {
        showSuccess,
        showError,
        showWarning,
        showInfo,
        showConfirm,
        showLoading,
        close,
    }
}
