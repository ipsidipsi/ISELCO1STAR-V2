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

    return {
        syncStatus,
        loading,
        error,
        fetchSyncStatus
    }
}
