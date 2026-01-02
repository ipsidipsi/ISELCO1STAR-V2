import { Capacitor } from '@capacitor/core'
import { LocalNotifications, type ScheduleOptions } from '@capacitor/local-notifications'
import type { Router } from 'vue-router'
import type { Notification } from '@/stores/notifications'

export function useLocalNotifications() {
    const isNative = Capacitor.isNativePlatform()

    /**
     * Request permission to show local notifications
     */
    async function requestPermission(): Promise<boolean> {
        if (!isNative) {
            console.log('[LocalNotifications] Not a native platform')
            return false
        }

        try {
            const result = await LocalNotifications.requestPermissions()
            console.log('[LocalNotifications] Permission result:', result.display)
            return result.display === 'granted'
        } catch (error) {
            console.error('[LocalNotifications] Permission request failed', error)
            return false
        }
    }

    /**
     * Schedule a local notification for native apps
     */
    async function scheduleNotification(notification: Notification): Promise<void> {
        if (!isNative) return

        try {
            await LocalNotifications.schedule({
                notifications: [
                    {
                        id: Date.now(),
                        title: `Ticket ${notification.data.ticket_number}`,
                        body: notification.data.message,
                        extra: {
                            ticket_id: notification.data.ticket_id,
                            ticket_number: notification.data.ticket_number,
                            action_type: notification.data.action_type
                        },
                        smallIcon: 'ic_notification',
                        sound: 'default',
                        channelId: 'ticket-notifications'
                    }
                ]
            })
            console.log('[LocalNotifications] Notification scheduled', notification.data.ticket_number)
        } catch (error) {
            console.error('[LocalNotifications] Failed to schedule notification', error)
        }
    }

    /**
     * Setup click handler for notifications
     */
    async function setupClickHandler(router: Router): Promise<void> {
        if (!isNative) return

        try {
            await LocalNotifications.addListener('localNotificationActionPerformed', (notification) => {
                console.log('[LocalNotifications] Notification clicked', notification)
                const ticketId = notification.notification.extra?.ticket_id
                if (ticketId) {
                    router.push(`/tickets/${ticketId}`)
                }
            })
            console.log('[LocalNotifications] Click handler registered')
        } catch (error) {
            console.error('[LocalNotifications] Failed to setup click handler', error)
        }
    }

    /**
     * Create notification channel for Android
     */
    async function createChannel(): Promise<void> {
        if (!isNative || Capacitor.getPlatform() !== 'android') return

        try {
            await LocalNotifications.createChannel({
                id: 'ticket-notifications',
                name: 'Ticket Notifications',
                description: 'Notifications for ticket updates',
                importance: 4, // High importance
                sound: 'default',
                vibration: true
            })
            console.log('[LocalNotifications] Channel created')
        } catch (error) {
            console.error('[LocalNotifications] Failed to create channel', error)
        }
    }

    return {
        isNative,
        requestPermission,
        scheduleNotification,
        setupClickHandler,
        createChannel
    }
}
