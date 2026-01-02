import { ref } from 'vue'
import api from '@/services/api'

export interface Category {
    id: number
    name: string
    description?: string
    department_id?: number
    priority_id?: number
    icon?: string
    color?: string
    is_active: boolean
    is_orphaned?: boolean
    department?: {
        id: number
        name: string
        code: string
        is_active: boolean
    }
    priority?: {
        id: number
        name: string
        level: number
        color: string
    }
    created_at: string
    updated_at: string
}

export interface CategoryFormData {
    name: string
    description?: string
    department_id?: number | null
    priority_id?: number | null
    icon?: string
    color?: string
    is_active?: boolean
}

export function useCategories() {
    const categories = ref<Category[]>([])
    const loading = ref(false)
    const error = ref<string | null>(null)

    /**
     * Fetch all categories with optional filtering
     */
    async function fetchCategories(params?: {
        department_id?: number
        is_active?: boolean
        orphaned?: boolean
    }) {
        loading.value = true
        error.value = null

        try {
            const response = await api.get('/admin/categories', { params })
            categories.value = response.data
            return response.data
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch categories'
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Fetch orphaned categories (linked to inactive departments)
     */
    async function fetchOrphanedCategories() {
        loading.value = true
        error.value = null

        try {
            const response = await api.get('/admin/categories/orphaned')
            return response.data
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch orphaned categories'
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Create new category
     */
    async function createCategory(data: CategoryFormData) {
        loading.value = true
        error.value = null

        try {
            const response = await api.post('/admin/categories', data)
            await fetchCategories() // Refresh list
            return response.data
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to create category'
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Update existing category
     */
    async function updateCategory(id: number, data: CategoryFormData) {
        loading.value = true
        error.value = null

        try {
            const response = await api.put(`/admin/categories/${id}`, data)
            await fetchCategories() // Refresh list
            return response.data
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to update category'
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Delete (deactivate) category
     */
    async function deleteCategory(id: number) {
        loading.value = true
        error.value = null

        try {
            const response = await api.delete(`/admin/categories/${id}`)
            await fetchCategories() // Refresh list
            return response.data
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to delete category'
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Bulk reassign categories to new department
     */
    async function bulkReassignCategories(categoryIds: number[], newDepartmentId: number) {
        loading.value = true
        error.value = null

        try {
            const response = await api.post('/admin/categories/bulk-reassign', {
                category_ids: categoryIds,
                new_department_id: newDepartmentId
            })
            await fetchCategories() // Refresh list
            return response.data
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to reassign categories'
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Convert category to global (remove department link)
     */
    async function convertToGlobal(id: number) {
        loading.value = true
        error.value = null

        try {
            const response = await api.post(`/admin/categories/${id}/convert-to-global`)
            await fetchCategories() // Refresh list
            return response.data
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to convert category'
            throw err
        } finally {
            loading.value = false
        }
    }

    return {
        categories,
        loading,
        error,
        fetchCategories,
        fetchOrphanedCategories,
        createCategory,
        updateCategory,
        deleteCategory,
        bulkReassignCategories,
        convertToGlobal
    }
}
