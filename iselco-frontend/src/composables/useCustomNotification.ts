import { ref } from 'vue'

export interface NotificationConfig {
    type?: 'toast' | 'modal' | 'loading'
    variant?: 'success' | 'error' | 'warning' | 'info'
    title?: string
    message?: string
    position?: 'top-right' | 'top-center' | 'bottom-right' | 'bottom-center'
    timer?: number
    showClose?: boolean
    showCancel?: boolean
    confirmText?: string
    cancelText?: string
    inputType?: 'textarea' | null
    inputPlaceholder?: string
}

interface NotificationState extends NotificationConfig {
    visible: boolean
    id: number
}

const notifications = ref<NotificationState[]>([])
let notificationId = 0

export function useCustomNotification() {
    /**
     * Show a toast notification
     */
    function showToast(config: NotificationConfig): Promise<void> {
        return new Promise((resolve) => {
            const id = ++notificationId
            const notification: NotificationState = {
                visible: true,
                id,
                type: 'toast',
                timer: 3000,
                showClose: true,
                ...config,
            }

            notifications.value.push(notification)

            // Auto-close after timer
            setTimeout(() => {
                closeNotification(id)
                resolve()
            }, notification.timer || 3000)
        })
    }

    /**
     * Show a modal notification
     */
    function showModal(config: NotificationConfig): Promise<{ isConfirmed: boolean; value?: string }> {
        return new Promise((resolve) => {
            const id = ++notificationId
            const notification: NotificationState = {
                visible: true,
                id,
                type: 'modal',
                showCancel: false,
                confirmText: 'OK',
                ...config,
            }

            notifications.value.push(notification)

            // Store resolve function for later use
            const notificationIndex = notifications.value.length - 1
                ; (notifications.value[notificationIndex] as any)._resolve = resolve
        })
    }

    /**
     * Show a loading overlay
     */
    function showLoading(title: string, message?: string): number {
        const id = ++notificationId
        const notification: NotificationState = {
            visible: true,
            id,
            type: 'loading',
            title,
            message,
        }

        notifications.value.push(notification)
        return id
    }

    /**
     * Close loading overlay
     */
    function closeLoading(id: number) {
        closeNotification(id)
    }

    /**
     * Close a notification by ID
     */
    function closeNotification(id: number) {
        const index = notifications.value.findIndex((n) => n.id === id)
        if (index !== -1) {
            notifications.value.splice(index, 1)
        }
    }

    /**
     * Handle notification close event
     */
    function handleClose(id: number) {
        const index = notifications.value.findIndex((n) => n.id === id)
        if (index !== -1) {
            const notification = notifications.value[index] as any
            if (notification._resolve) {
                notification._resolve({ isConfirmed: false })
            }
            closeNotification(id)
        }
    }

    /**
     * Handle notification confirm event
     */
    function handleConfirm(id: number, value?: string) {
        const index = notifications.value.findIndex((n) => n.id === id)
        if (index !== -1) {
            const notification = notifications.value[index] as any
            if (notification._resolve) {
                notification._resolve({ isConfirmed: true, value })
            }
            closeNotification(id)
        }
    }

    /**
     * Handle notification cancel event
     */
    function handleCancel(id: number) {
        const index = notifications.value.findIndex((n) => n.id === id)
        if (index !== -1) {
            const notification = notifications.value[index] as any
            if (notification._resolve) {
                notification._resolve({ isConfirmed: false })
            }
            closeNotification(id)
        }
    }

    /**
     * Helper: Show success toast
     */
    async function showSuccess(title: string, message?: string) {
        return showToast({
            variant: 'success',
            title,
            message,
        })
    }

    /**
     * Helper: Show error toast
     */
    async function showError(title: string, message?: string) {
        return showToast({
            variant: 'error',
            title,
            message,
            timer: 5000, // Errors stay longer
        })
    }

    /**
     * Helper: Show warning toast
     */
    async function showWarning(title: string, message?: string) {
        return showToast({
            variant: 'warning',
            title,
            message,
        })
    }

    /**
     * Helper: Show info toast
     */
    async function showInfo(title: string, message?: string) {
        return showToast({
            variant: 'info',
            title,
            message,
        })
    }

    /**
     * Helper: Show confirmation modal
     */
    async function showConfirm(
        title: string,
        message: string,
        confirmText: string = 'Confirm',
        cancelText: string = 'Cancel'
    ) {
        return showModal({
            variant: 'warning',
            title,
            message,
            showCancel: true,
            confirmText,
            cancelText,
        })
    }

    /**
     * Helper: Show input modal
     */
    async function showInput(
        title: string,
        message: string,
        placeholder: string = ''
    ) {
        return showModal({
            variant: 'info',
            title,
            message,
            showCancel: true,
            inputType: 'textarea',
            inputPlaceholder: placeholder,
        })
    }

    return {
        notifications,
        showToast,
        showModal,
        showLoading,
        closeLoading,
        handleClose,
        handleConfirm,
        handleCancel,
        // Helpers
        showSuccess,
        showError,
        showWarning,
        showInfo,
        showConfirm,
        showInput,
    }
}
