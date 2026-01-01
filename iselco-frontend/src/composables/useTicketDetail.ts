import { ref, onUnmounted } from 'vue'
import { useTicketStore } from '@/stores/tickets'
import { useAuthStore } from '@/stores/auth'
import { useNotification } from '@/composables/useNotification'
import api from '@/services/api'

/**
 * Ticket Detail Composable
 * 
 * Manages individual ticket operations and workflow actions
 */
export function useTicketDetail(ticketId: number) {
    const ticketStore = useTicketStore()
    const authStore = useAuthStore()
    const { showSuccess, showError, showConfirm, showLoading, close, showInfo } = useNotification()

    const loading = ref(false)
    const ticket = ref<any>(null)

    /**
     * Load ticket details
     */
    async function loadTicket() {
        loading.value = true
        try {
            const response = await api.get(`/tickets/${ticketId}`)
            ticket.value = response.data

            // Start listening for updates
            subscribeToUpdates()

            return response.data
        } catch (error: any) {
            await showError('Failed to Load Ticket', error.message || 'Could not load ticket details')
            throw error
        } finally {
            loading.value = false
        }
    }

    /**
     * Subscribe to real-time updates
     */
    function subscribeToUpdates() {
        if (window.Echo) {
            console.log(`TicketDetail: Subscribing to ticket.${ticketId}`)
            window.Echo.private(`ticket.${ticketId}`)
                .listen('.ticket.activity', (e: any) => {
                    console.log('TicketDetail: Update received', e.activity)
                    handleRealTimeUpdate(e.activity)
                })
        }
    }

    /**
     * Handle incoming real-time activity
     */
    function handleRealTimeUpdate(activity: any) {
        if (!ticket.value) return

        // 1. Update Status locally if changed
        if (activity.activity_type === 'status_changed' && activity.metadata?.new_status) {
            ticket.value.status = activity.metadata.new_status
        }

        // 2. Update Assignment locally if changed
        if (activity.activity_type === 'assigned' && activity.metadata?.assignee_id) {
            ticket.value.assigned_to_id = activity.metadata.assignee_id
            ticket.value.assigned_to = {
                id: activity.metadata.assignee_id,
                employee_name: activity.metadata.assignee_name || 'Assigned User' // Fallback
            }
        }

        // 3. Show notification if action was done by someone else
        if (activity.user_id !== authStore.user?.id) {
            const description = activity.description
                ? activity.description
                : 'Ticket updated'

            showInfo('Update Received', description)
        }
    }

    onUnmounted(() => {
        if (window.Echo) {
            window.Echo.private(`ticket.${ticketId}`).stopListening('.ticket.activity')
        }
    })

    /**
     * Accept ticket (assign to self)
     */
    async function acceptTicket() {
        const result = await showConfirm(
            'Accept This Ticket?',
            'This ticket will be assigned to you. You will be responsible for resolving it.',
            'Accept Ticket',
            'Cancel'
        )

        if (!result.isConfirmed) return

        showLoading('Accepting Ticket...', 'Please wait while we assign this ticket to you')

        try {
            await api.post(`/tickets/${ticketId}/accept`)
            close()
            await showSuccess('Ticket Accepted!', 'The ticket has been assigned to you. You can now start working on it.')
            await loadTicket() // Reload to get updated status
        } catch (error: any) {
            close()
            await showError('Failed to Accept Ticket', error.response?.data?.message || 'Could not accept ticket')
        }
    }

    /**
     * Start working on ticket
     */
    async function startWork() {
        const result = await showConfirm(
            'Start Working?',
            'This will change the ticket status to "In Progress".',
            'Start Work',
            'Cancel'
        )

        if (!result.isConfirmed) return

        showLoading('Starting Work...', 'Updating ticket status')

        try {
            await api.post(`/tickets/${ticketId}/start`)
            close()
            await showSuccess('Work Started!', 'Ticket status updated to "In Progress"')
            await loadTicket()
        } catch (error: any) {
            close()
            await showError('Failed to Start Work', error.response?.data?.message || 'Could not update ticket status')
        }
    }

    /**
     * Resolve ticket
     */
    async function resolveTicket(notes: string = '') {
        showLoading('Resolving Ticket...', 'Marking ticket as resolved')

        try {
            await api.post(`/tickets/${ticketId}/resolve`, { notes })
            close()
            await showSuccess(
                'Ticket Resolved!',
                'The ticket has been marked as resolved and is pending verification by the requestor.'
            )
            await loadTicket()
        } catch (error: any) {
            close()
            await showError('Failed to Resolve Ticket', error.response?.data?.message || 'Could not resolve ticket')
        }
    }

    /**
     * Verify and close ticket (requestor)
     */
    async function verifyTicket() {
        const result = await showConfirm(
            'Verify Solution?',
            'This will mark the ticket as verified and close it. This action confirms the issue has been resolved.',
            'Verify & Close',
            'Cancel'
        )

        if (!result.isConfirmed) return

        showLoading('Verifying Ticket...', 'Closing ticket')

        try {
            await api.post(`/tickets/${ticketId}/verify`)
            close()
            await showSuccess('Ticket Verified!', 'The ticket has been closed successfully.')
            await loadTicket()
        } catch (error: any) {
            close()
            await showError('Failed to Verify Ticket', error.response?.data?.message || 'Could not verify ticket')
        }
    }

    /**
     * Reject and reopen ticket (requestor)
     */
    async function rejectTicket(reason: string = '') {
        showLoading('Reopening Ticket...', 'Sending back for additional work')

        try {
            await api.post(`/tickets/${ticketId}/reject`, { reason })
            close()
            await showSuccess(
                'Ticket Reopened',
                'The ticket has been sent back to the assignee for additional work.'
            )
            await loadTicket()
        } catch (error: any) {
            close()
            await showError('Failed to Reopen Ticket', error.response?.data?.message || 'Could not reopen ticket')
        }
    }

    /**
     * Reassign ticket to another user (admin)
     */
    async function reassignTicket(userId: number, userName: string) {
        const result = await showConfirm(
            'Reassign Ticket?',
            `This ticket will be reassigned to ${userName}.`,
            'Reassign',
            'Cancel'
        )

        if (!result.isConfirmed) return

        showLoading('Reassigning Ticket...', `Assigning to ${userName}`)

        try {
            await api.post(`/tickets/${ticketId}/reassign`, { assigned_to_id: userId })
            close()
            await showSuccess('Ticket Reassigned!', `The ticket has been assigned to ${userName}.`)
            await loadTicket()
        } catch (error: any) {
            close()
            await showError('Failed to Reassign Ticket', error.response?.data?.message || 'Could not reassign ticket')
        }
    }

    /**
     * Reopen closed ticket
     */
    async function reopenTicket(reason: string = '') {
        const result = await showConfirm(
            'Reopen This Ticket?',
            'This will change the ticket status back to "In Progress".',
            'Reopen',
            'Cancel'
        )

        if (!result.isConfirmed) return

        showLoading('Reopening Ticket...', 'Updating status')

        try {
            await api.post(`/tickets/${ticketId}/reopen`, { reason })
            close()
            await showSuccess('Ticket Reopened', 'The ticket is now back in progress.')
            await loadTicket()
        } catch (error: any) {
            close()
            await showError('Failed to Reopen Ticket', error.response?.data?.message || 'Could not reopen ticket')
        }
    }

    return {
        ticket,
        loading,
        loadTicket,
        acceptTicket,
        startWork,
        resolveTicket,
        verifyTicket,
        rejectTicket,
        reassignTicket,
        reopenTicket,
    }
}
