import { useCustomNotification } from './useCustomNotification'

/**
 * Notification Composable
 * 
 * Provides consistent notification messages throughout the app
 * Uses custom notification system instead of SweetAlert2
 */
export function useNotification() {
    const {
        showSuccess: customShowSuccess,
        showError: customShowError,
        showWarning: customShowWarning,
        showInfo: customShowInfo,
        showConfirm: customShowConfirm,
        showInput: customShowInput,
        showLoading: customShowLoading,
        closeLoading: customCloseLoading,
    } = useCustomNotification()

    let currentLoadingId: number | null = null

    /**
     * Show success notification
     */
    async function showSuccess(title: string, message?: string) {
        return customShowSuccess(title, message)
    }

    /**
     * Show error notification
     */
    async function showError(title: string, message?: string) {
        return customShowError(title, message)
    }

    /**
     * Show warning notification
     */
    async function showWarning(title: string, message?: string) {
        return customShowWarning(title, message)
    }

    /**
     * Show info notification
     */
    async function showInfo(title: string, message?: string) {
        return customShowInfo(title, message)
    }

    /**
     * Show confirmation dialog
     */
    async function showConfirm(
        title: string,
        message: string,
        confirmText: string = 'Confirm',
        cancelText: string = 'Cancel'
    ) {
        return customShowConfirm(title, message, confirmText, cancelText)
    }

    /**
     * Show input dialog with textarea
     */
    async function showInput(
        title: string,
        message: string,
        placeholder: string = ''
    ) {
        return customShowInput(title, message, placeholder)
    }

    /**
     * Show loading overlay
     */
    function showLoading(title: string, message?: string) {
        currentLoadingId = customShowLoading(title, message)
    }

    /**
     * Close current loading overlay
     */
    function close() {
        if (currentLoadingId !== null) {
            customCloseLoading(currentLoadingId)
            currentLoadingId = null
        }
    }

    return {
        showSuccess,
        showError,
        showWarning,
        showInfo,
        showConfirm,
        showInput,
        showLoading,
        close,
    }
}
