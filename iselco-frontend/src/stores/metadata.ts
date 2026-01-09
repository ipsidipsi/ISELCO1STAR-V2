import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export interface Department {
    id: number
    name: string
    code: string
    is_active: boolean
}

export interface Category {
    id: number
    name: string
    description: string
    department_id: number | null
    priority_id: number | null
    icon: string
    color: string
    is_active: boolean
}

export interface Priority {
    id: number
    name: string
    level: number
    color: string
    sla_hours: number
}

export const useMetadataStore = defineStore('metadata', () => {
    // State
    const departments = ref<Department[]>([])
    const categories = ref<Category[]>([])
    const priorities = ref<Priority[]>([])
    const loading = ref(false)

    // Actions
    async function fetchDepartments() {
        loading.value = true
        try {
            const response = await api.get('/departments')
            departments.value = response.data
            return response.data
        } catch (error) {
            console.error('Fetch departments error:', error)
            throw error
        } finally {
            loading.value = false
        }
    }

    async function fetchCategories(departmentId?: number) {
        loading.value = true
        try {
            const params = departmentId ? { department_id: departmentId } : {}
            const response = await api.get('/categories', { params })
            categories.value = response.data
            return response.data
        } catch (error) {
            console.error('Fetch categories error:', error)
            throw error
        } finally {
            loading.value = false
        }
    }

    async function fetchPriorities() {
        loading.value = true
        try {
            const response = await api.get('/priorities')
            priorities.value = response.data
            return response.data
        } catch (error) {
            console.error('Fetch priorities error:', error)
            throw error
        } finally {
            loading.value = false
        }
    }

    async function fetchAllMetadata() {
        await Promise.all([
            fetchDepartments(),
            fetchCategories(),
            fetchPriorities()
        ])
    }

    return {
        departments,
        categories,
        priorities,
        loading,
        fetchDepartments,
        fetchCategories,
        fetchPriorities,
        fetchAllMetadata,
    }
}, {
    persist: {
        storage: localStorage,
        pick: ['departments', 'categories', 'priorities'],
    },
})
