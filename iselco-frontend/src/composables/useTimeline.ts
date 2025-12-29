import { ref } from 'vue'
import api from '@/services/api'
import {
    documentTextOutline,
    checkmarkCircleOutline,
    personOutline,
    playCircleOutline,
    checkmarkDoneOutline,
    chatbubbleOutline,
    attachOutline,
    arrowUndoOutline,
    addCircleOutline,
    swapHorizontalOutline
} from 'ionicons/icons'

export interface Activity {
    id: number
    ticket_id: number
    user_id: number | null
    activity_type: string
    description: string
    metadata: any
    created_at: string
    user?: {
        id: number
        username: string
        employee_name: string
    }
}

export function useTimeline(ticketId: number) {
    const activities = ref<Activity[]>([])
    const loading = ref(false)
    const error = ref<string | null>(null)

    async function loadActivities() {
        loading.value = true
        error.value = null
        try {
            const response = await api.get(`/tickets/${ticketId}/activities`)
            activities.value = response.data
        } catch (err: any) {
            error.value = err.message || 'Failed to load timeline'
            console.error('Timeline load error:', err)
        } finally {
            loading.value = false
        }
    }

    function getActivityIcon(type: string) {
        const icons: Record<string, string> = {
            ticket_created: addCircleOutline,
            status_changed: swapHorizontalOutline,
            assigned: personOutline,
            reassigned: swapHorizontalOutline,
            started: playCircleOutline,
            resolved: checkmarkDoneOutline,
            verified: checkmarkCircleOutline,
            reopened: arrowUndoOutline,
            comment_added: chatbubbleOutline,
            attachment_uploaded: attachOutline,
        }
        return icons[type] || documentTextOutline
    }

    function getActivityColor(type: string) {
        const colors: Record<string, string> = {
            ticket_created: 'blue',
            status_changed: 'purple',
            assigned: 'teal',
            reassigned: 'orange',
            started: 'blue',
            resolved: 'green',
            verified: 'green',
            reopened: 'red',
            comment_added: 'gray',
            attachment_uploaded: 'emerald',
        }
        return colors[type] || 'gray'
    }

    function formatRelativeTime(dateString: string): string {
        const date = new Date(dateString)
        const now = new Date()
        const diffMs = now.getTime() - date.getTime()
        const diffSecs = Math.floor(diffMs / 1000)
        const diffMins = Math.floor(diffSecs / 60)
        const diffHours = Math.floor(diffMins / 60)
        const diffDays = Math.floor(diffHours / 24)

        if (diffSecs < 60) return 'just now'
        if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`
        if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`
        if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`

        return date.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        })
    }

    return {
        activities,
        loading,
        error,
        loadActivities,
        getActivityIcon,
        getActivityColor,
        formatRelativeTime
    }
}
