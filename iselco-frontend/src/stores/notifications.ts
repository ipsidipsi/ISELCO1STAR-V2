import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

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

export const useNotificationStore = defineStore('notifications', () => {
    const notifications = ref<Notification[]>([])
    const unreadCount = ref(0)
    const page = ref(1)
    const hasMore = ref(true)
    const loading = ref(false)

    const authStore = useAuthStore()

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

    function handleRealTimeNotification(notification: any) {
        // notification is the payload from Echo event "Illuminate\Notifications\Events\BroadcastNotificationCreated"
        // The structure is usually notification.id, notification.type, notification.data
        // But Laravel BroadcastNotificationCreated event wraps it.
        // Actually, Echo receives the raw notification data directly if formatted correctly locally.

        // We need to shape it to match our interface
        const newNotif: Notification = {
            id: notification.id,
            type: notification.type,
            notifiable_type: 'App\\Models\\User', // Assumed
            notifiable_id: authStore.user?.id || 0,
            data: notification.data || notification, // Depending on if it's wrapped
            read_at: null,
            created_at: new Date().toISOString()
        }

        // Add to list
        notifications.value.unshift(newNotif)

        // Add to count
        unreadCount.value++
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
        fetchNotifications,
        fetchUnreadCount,
        markAsRead,
        markAllAsRead,
        markTicketAsRead,
        deleteNotification,
        handleRealTimeNotification,
        initializeListener,
        stopListener
    }
}, {
    persist: false // Not persisting notifications list, strict real-time/fetch
})
