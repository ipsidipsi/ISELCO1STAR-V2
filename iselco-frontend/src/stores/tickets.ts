import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export interface Ticket {
    id: number
    ticket_number: string
    title: string
    description: string
    status: 'new' | 'seen' | 'assigned' | 'in_progress' | 'resolved' | 'closed' | 'reopened'
    priority: any
    category: any
    department: any
    requestor: any
    assignedTo?: any
    created_at: string
    updated_at: string
}

export const useTicketStore = defineStore('tickets', () => {
    // State
    const tickets = ref<Ticket[]>([])
    const currentTicket = ref<Ticket | null>(null)
    const loading = ref(false)
    const stats = ref({
        total: 0,
        new: 0,
        assigned: 0,
        in_progress: 0,
        resolved: 0,
        closed: 0,
        my_assigned: 0,
        my_created: 0,
    })

    // Computed
    const byStatus = computed(() => {
        return tickets.value.reduce((acc, ticket) => {
            acc[ticket.status] = (acc[ticket.status] || 0) + 1
            return acc
        }, {} as Record<string, number>)
    })

    // Actions
    async function fetchTickets(filters = {}) {
        loading.value = true
        try {
            const response = await api.get('/tickets', { params: filters })
            tickets.value = response.data.data || response.data
            return response.data
        } catch (error) {
            console.error('Fetch tickets error:', error)
            throw error
        } finally {
            loading.value = false
        }
    }

    async function fetchTicket(id: number) {
        loading.value = true
        try {
            const response = await api.get(`/tickets/${id}`)
            currentTicket.value = response.data
            return response.data
        } catch (error) {
            console.error('Fetch ticket error:', error)
            throw error
        } finally {
            loading.value = false
        }
    }

    async function fetchStats() {
        try {
            // Fetch all tickets to calculate stats
            const response = await api.get('/tickets')
            const allTickets = response.data.data || response.data

            stats.value = {
                total: allTickets.length,
                new: allTickets.filter((t: Ticket) => t.status === 'new').length,
                assigned: allTickets.filter((t: Ticket) => t.status === 'assigned').length,
                in_progress: allTickets.filter((t: Ticket) => t.status === 'in_progress').length,
                resolved: allTickets.filter((t: Ticket) => t.status === 'resolved').length,
                closed: allTickets.filter((t: Ticket) => t.status === 'closed').length,
                my_assigned: allTickets.filter((t: Ticket) => t.assignedTo !== null).length,
                my_created: allTickets.filter((t: Ticket) => t.requestor !== null).length,
            }

            return stats.value
        } catch (error) {
            console.error('Fetch stats error:', error)
            throw error
        }
    }

    async function createTicket(data: any) {
        try {
            const response = await api.post('/tickets', data)
            tickets.value.unshift(response.data)
            return response.data
        } catch (error) {
            console.error('Create ticket error:', error)
            throw error
        }
    }

    return {
        tickets,
        currentTicket,
        loading,
        stats,
        byStatus,
        fetchTickets,
        fetchTicket,
        fetchStats,
        createTicket,
    }
})
