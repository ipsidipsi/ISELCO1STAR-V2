import { ref, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useTicketStore } from '@/stores/tickets'

/**
 * Tickets Composable
 * 
 * Separates ticket management logic from components
 */
export function useTickets() {
    const ticketStore = useTicketStore()

    // Use storeToRefs to maintain reactivity
    const { tickets, stats } = storeToRefs(ticketStore)

    const loading = ref(false)
    const error = ref('')

    async function loadTickets(filters = {}) {
        loading.value = true
        error.value = ''

        try {
            await ticketStore.fetchTickets(filters)
        } catch (err: any) {
            error.value = err.message || 'Failed to load tickets'
        } finally {
            loading.value = false
        }
    }

    async function loadStats() {
        loading.value = true
        error.value = ''

        try {
            await ticketStore.fetchStats()
        } catch (err: any) {
            error.value = err.message || 'Failed to load statistics'
        } finally {
            loading.value = false
        }
    }

    interface TicketStats {
        pending: number
        assigned_to_me: number
        in_progress: number
        pending_verification: number
        closed: number
        my_requests: number
    }

    async function createTicket(data: any) {
        loading.value = true
        error.value = ''

        try {
            const ticket = await ticketStore.createTicket(data)
            return ticket
        } catch (err: any) {
            error.value = err.message || 'Failed to create ticket'
            throw err
        } finally {
            loading.value = false
        }
    }

    return {
        tickets,
        stats,
        loading,
        error,
        loadTickets,
        loadStats,
        createTicket,
    }
}
