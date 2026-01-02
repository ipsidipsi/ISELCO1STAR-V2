import { ref } from 'vue'
import api from '@/services/api'

export interface Priority {
    id: number
    name: string
    level: number
    color: string
    sla_hours: number
    ticket_count?: number
    created_at: string
    updated_at: string
}

export interface PriorityFormData {
    name: string
    level: number
    color: string
    sla_hours: number
}

export function usePriorities() {
    const priorities = ref<Priority[]>([])
    const loading = ref(false)
    const error = ref<string | null>(null)

    /**
     * Fetch all priorities
     */
    async function fetchPriorities() {
        loading.value = true
        error.value = null

        try {
            const response = await api.get('/admin/priorities')
            priorities.value = response.data
            return response.data
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch priorities'
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Create new priority
     */
    async function createPriority(data: PriorityFormData) {
        loading.value = true
        error.value = null

        try {
            const response = await api.post('/admin/priorities', data)
            await fetchPriorities() // Refresh list
            return response.data
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to create priority'
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Update existing priority
     */
    async function updatePriority(id: number, data: PriorityFormData) {
        loading.value = true
        error.value = null

        try {
            const response = await api.put(`/admin/priorities/${id}`, data)
            await fetchPriorities() // Refresh list
            return response.data
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to update priority'
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Delete priority (only if not in use)
     */
    async function deletePriority(id: number) {
        loading.value = true
        error.value = null

        try {
            const response = await api.delete(`/admin/priorities/${id}`)
            await fetchPriorities() // Refresh list
            return response.data
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to delete priority'
            throw err
        } finally {
            loading.value = false
        }
    }

    return {
        priorities,
        loading,
        error,
        fetchPriorities,
        createPriority,
        updatePriority,
        deletePriority
    }
}
