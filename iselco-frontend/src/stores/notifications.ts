import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { Capacitor } from '@capacitor/core'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useLocalNotifications } from '@/composables/useLocalNotifications'
import { useRouter } from 'vue-router'

export interface Notification {
    id: string
    type: string
    notifiable_type: string
    notifiable_id: number
    data: {
        ticket_id: number
        ticket_number: string
        ticket_title: string
        actor_id: number
        actor_name: string
        action_type: string
        message: string
    }
    read_at: string | null
    created_at: string
}

interface NotificationPreferences {
    is_muted: boolean
    web_push_enabled: boolean
    browser_enabled: boolean
    sound_enabled: boolean
}

export const useNotificationStore = defineStore('notifications', () => {
    const notifications = ref<Notification[]>([])
    const unreadCount = ref(0)
    const page = ref(1)
    const hasMore = ref(true)
    const loading = ref(false)
    const preferences = ref<NotificationPreferences>({
        is_muted: false,
        web_push_enabled: true,
        browser_enabled: true,
        sound_enabled: true
    })

    const authStore = useAuthStore()
    const { scheduleNotification, isNative } = useLocalNotifications()

    // Actions
    async function fetchNotifications(refresh = false) {
        if (refresh) {
            page.value = 1
            notifications.value = []
            hasMore.value = true
        }

        if (!hasMore.value && !refresh) return

        loading.value = true
        try {
            const response = await api.get(`/notifications?page=${page.value}`)
            const newData = response.data.data

            if (refresh) {
                notifications.value = newData
            } else {
                notifications.value = [...notifications.value, ...newData]
            }

            if (response.data.next_page_url) {
                page.value++
            } else {
                hasMore.value = false
            }
        } catch (error) {
            console.error('Failed to fetch notifications', error)
        } finally {
            loading.value = false
        }
    }

    async function fetchUnreadCount() {
        try {
            const response = await api.get('/notifications/unread-count')
            unreadCount.value = response.data.count
        } catch (error) {
            console.error('Failed to fetch unread count', error)
        }
    }

    async function markAsRead(id: string) {
        // Optimistic update
        const notif = notifications.value.find(n => n.id === id)
        if (notif && !notif.read_at) {
            notif.read_at = new Date().toISOString()
            unreadCount.value = Math.max(0, unreadCount.value - 1)
        }

        try {
            await api.patch(`/notifications/${id}/read`)
        } catch (error) {
            console.error('Failed to mark as read', error)
        }
    }

    async function markAllAsRead() {
        // Optimistic
        notifications.value.forEach(n => {
            if (!n.read_at) n.read_at = new Date().toISOString()
        })
        unreadCount.value = 0

        try {
            await api.patch('/notifications/mark-all-read')
        } catch (error) {
            console.error('Failed to mark all as read', error)
        }
    }

    // Smart Read: Mark all for a ticket
    async function markTicketAsRead(ticketId: number) {
        // Optimistic
        let countRemoved = 0
        notifications.value.forEach(n => {
            if (n.data.ticket_id === ticketId && !n.read_at) {
                n.read_at = new Date().toISOString()
                countRemoved++
            }
        })
        unreadCount.value = Math.max(0, unreadCount.value - countRemoved)

        try {
            await api.post(`/notifications/mark-ticket-read/${ticketId}`)
            // Re-sync count just in case
            fetchUnreadCount()
        } catch (error) {
            console.error('Failed to mark ticket notifications as read', error)
        }
    }

    async function deleteNotification(id: string) {
        // Optimistic
        const index = notifications.value.findIndex(n => n.id === id)
        if (index !== -1) {
            const wasUnread = !notifications.value[index].read_at
            notifications.value.splice(index, 1)
            if (wasUnread) {
                unreadCount.value = Math.max(0, unreadCount.value - 1)
            }
        }

        try {
            await api.delete(`/notifications/${id}`)
        } catch (error) {
            console.error('Failed to delete notification', error)
        }
    }

    async function deleteAllNotifications() {
        // Optimistic
        notifications.value = []
        unreadCount.value = 0

        try {
            await api.delete('/notifications/delete-all')
        } catch (error) {
            console.error('Failed to delete all notifications', error)
            // Refresh to get real state
            fetchNotifications(true)
            fetchUnreadCount()
        }
    }

    async function fetchPreferences() {
        try {
            const response = await api.get('/notifications/preferences')
            preferences.value = response.data
        } catch (error) {
            console.error('Failed to fetch preferences', error)
        }
    }

    async function updatePreferences(updates: Partial<NotificationPreferences>) {
        // Optimistic
        Object.assign(preferences.value, updates)

        try {
            await api.patch('/notifications/preferences', updates)
        } catch (error) {
            console.error('Failed to update preferences', error)
            // Revert on error
            fetchPreferences()
        }
    }

    function showBrowserNotification(notif: Notification) {
        // Don't show if permission not granted or muted
        if (Notification.permission !== 'granted' || preferences.value.is_muted) {
            return
        }

        // Don't show if document visible (user is already looking at the app)
        if (!document.hidden && !preferences.value.browser_enabled) {
            return
        }

        try {
            const notification = new Notification(`Ticket ${notif.data.ticket_number}`, {
                body: notif.data.message,
                icon: '/icon-192.png',
                badge: '/icon-192.png',
                tag: `ticket-${notif.data.ticket_id}`,
                data: { ticket_id: notif.data.ticket_id },
                requireInteraction: false
            })

            notification.onclick = () => {
                window.focus()
                // Navigate to ticket (need router instance)
                window.location.hash = `#/tickets/${notif.data.ticket_id}`
                notification.close()
            }
        } catch (error) {
            console.error('[Notifications] Failed to show browser notification', error)
        }
    }

    function handleRealTimeNotification(notification: any) {
        // Shape to match our interface
        const newNotif: Notification = {
            id: notification.id,
            type: notification.type,
            notifiable_type: 'App\\Models\\User',
            notifiable_id: authStore.user?.id || 0,
            data: notification.data || notification,
            read_at: null,
            created_at: new Date().toISOString()
        }

        // Deduplicate: Check if notification with this ID already exists
        const exists = notifications.value.some(n => n.id === newNotif.id)
        if (exists) {
            console.log('[Notifications] Duplicate notification ignored:', newNotif.id)
            return
        }

        // Add to list
        notifications.value.unshift(newNotif)
        unreadCount.value++

        console.log('[Notifications] Real-time notification received (Standard):', newNotif)
        console.log('[Notifications] Updated unread count:', unreadCount.value)
        console.log('[Notifications] Total notifications:', notifications.value.length)

        // Don't trigger notifications if muted
        if (preferences.value.is_muted) {
            console.log('[Notifications] Notification muted', newNotif.data.ticket_number)
            return
        }

        // Trigger local notification for native apps
        if (isNative) {
            scheduleNotification(newNotif)
        }

        // Trigger browser notification for web
        if (!isNative && preferences.value.browser_enabled) {
            showBrowserNotification(newNotif)
        }

        // Play sound if enabled
        if (preferences.value.sound_enabled) {
            playNotificationSound()
        }
    }

    function playNotificationSound() {
        try {
            const audio = new Audio('/notification.wav')
            audio.volume = 0.5
            audio.play().catch((err: Error) => {
                console.warn('[Notifications] Sound play failed (user interaction may be required)', err)
            })
        } catch (error) {
            console.warn('[Notifications] Sound initialization failed', error)
        }
    }

    function initializeListener() {
        if (!authStore.user?.id) return

        // Listen to standard notification event
        // Channel: App.Models.User.{id}
        // Event: .Illuminate\Notifications\Events\BroadcastNotificationCreated
        const channelName = `App.Models.User.${authStore.user.id}`

        console.log(`Initializing Notification Listener on ${channelName}`)

        // Debug: Log all events on this channel to see what's coming
        // window.Echo.private(channelName).listenToAll((event, data) => {
        //     console.log('Echo Debug: Event received on channel', event, data);
        // });

        window.Echo.private(channelName)
            .notification((notification: any) => {
                console.log('Real-time notification received (Standard):', notification)
                handleRealTimeNotification(notification)
            })
            .listen('.App\\Notifications\\TicketUpdated', (e: any) => {
                console.log('Real-time notification received (Direct Class):', e);
                handleRealTimeNotification(e);
            })
            // Listen for the manual broadcast workaround event (NotificationCreated)
            // It broadcastsAs 'notification'
            .listen('.notification', (e: any) => {
                console.log('Real-time notification received (Manual Workaround):', e);
                handleRealTimeNotification(e);
            })
            .error((error: any) => {
                console.error('Echo Notification Error:', error);
            });
    }

    function stopListener() {
        if (!authStore.user?.id) return
        const channelName = `App.Models.User.${authStore.user.id}`
        window.Echo.leave(channelName)
    }

    return {
        notifications,
        unreadCount,
        loading,
        hasMore,
        preferences,
        fetchNotifications,
        fetchUnreadCount,
        fetchPreferences,
        updatePreferences,
        markAsRead,
        markAllAsRead,
        markTicketAsRead,
        deleteNotification,
        deleteAllNotifications,
        handleRealTimeNotification,
        initializeListener,
        stopListener
    }
}, {
    persist: false // Not persisting notifications list, strict real-time/fetch
})
