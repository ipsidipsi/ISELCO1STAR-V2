import { ref } from 'vue'
import api from '@/services/api'

export interface DepartmentSyncStatus {
    total_departments: number
    active_departments: number
    inactive_departments: number
    last_synced_at: string | null
    affected_categories: number
    inactive_departments_detail: Array<{
        id: number
        name: string
        code: string
        is_active: boolean
        categories_count: number
    }>
}

export function useDepartmentSync() {
    const syncStatus = ref<DepartmentSyncStatus | null>(null)
    const loading = ref(false)
    const error = ref<string | null>(null)

    /**
     * Fetch department sync status
     */
    async function fetchSyncStatus() {
        loading.value = true
        error.value = null

        try {
            const response = await api.get('/admin/departments/sync-status')
            syncStatus.value = response.data
            return response.data
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch sync status'
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Manually trigger department sync from external API
     */
    async function triggerSync() {
        loading.value = true
        error.value = null

        try {
            const response = await api.post('/admin/departments/sync')
            syncStatus.value = {
                total_departments: response.data.total_departments,
                active_departments: response.data.active_departments,
                inactive_departments: 0,
                last_synced_at: response.data.last_synced_at,
                affected_categories: 0,
                inactive_departments_detail: []
            }
            return response.data
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to trigger sync'
            throw err
        } finally {
            loading.value = false
        }
    }

    return {
        syncStatus,
        loading,
        error,
        fetchSyncStatus,
        triggerSync
    }
}
