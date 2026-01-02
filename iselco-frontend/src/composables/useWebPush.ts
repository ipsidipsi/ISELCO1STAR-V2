import { ref, computed } from 'vue'
import api from '@/services/api'

export function useWebPush() {
    const isSupported = ref('serviceWorker' in navigator && 'PushManager' in window)
    const permission = ref(Notification.permission)
    const subscription = ref<PushSubscription | null>(null)
    const isSubscribed = computed(() => subscription.value !== null)

    /**
     * Request notification permission from the browser
     */
    async function requestPermission(): Promise<boolean> {
        if (!isSupported.value) {
            console.warn('[WebPush] Web Push is not supported in this browser')
            return false
        }

        const result = await Notification.requestPermission()
        permission.value = result
        return result === 'granted'
    }

    /**
     * Subscribe to Web Push notifications
     */
    async function subscribe(): Promise<boolean> {
        if (!isSupported.value) {
            console.warn('[WebPush] Web Push not supported')
            return false
        }

        if (permission.value !== 'granted') {
            console.warn('[WebPush] Permission not granted')
            return false
        }

        try {
            const registration = await navigator.serviceWorker.ready

            const vapidPublicKey = import.meta.env.VITE_VAPID_PUBLIC_KEY
            if (!vapidPublicKey) {
                console.error('[WebPush] VAPID public key not configured')
                return false
            }

            subscription.value = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: vapidPublicKey
            })

            // Send subscription to backend
            await api.post('/notifications/web-push/subscribe', {
                subscription: subscription.value.toJSON()
            })

            console.log('[WebPush] Successfully subscribed')
            return true
        } catch (error) {
            console.error('[WebPush] Subscription failed', error)
            return false
        }
    }

    /**
     * Unsubscribe from Web Push notifications
     */
    async function unsubscribe(): Promise<boolean> {
        if (subscription.value) {
            try {
                await subscription.value.unsubscribe()
                subscription.value = null
                console.log('[WebPush] Successfully unsubscribed')
                return true
            } catch (error) {
                console.error('[WebPush] Unsubscribe failed', error)
                return false
            }
        }
        return false
    }

    /**
     * Check current subscription status
     */
    async function checkSubscription(): Promise<void> {
        if (!isSupported.value) return

        try {
            const registration = await navigator.serviceWorker.ready
            subscription.value = await registration.pushManager.getSubscription()
        } catch (error) {
            console.error('[WebPush] Failed to check subscription', error)
        }
    }

    return {
        isSupported,
        permission,
        subscription,
        isSubscribed,
        requestPermission,
        subscribe,
        unsubscribe,
        checkSubscription
    }
}
