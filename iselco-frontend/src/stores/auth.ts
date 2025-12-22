import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import router from '@/router'

export interface User {
    id: number
    username: string
    employee_name: string
    mobile_number: string | null
    department_id: number | null
    is_active: boolean
    must_change_password: boolean
    roles: any[]
    department?: any
}

export const useAuthStore = defineStore('auth', () => {
    // State
    const user = ref<User | null>(null)
    const token = ref<string | null>(null)
    const isAuthenticated = computed(() => !!token.value)

    // Actions
    async function login(loginValue: string, password: string) {
        try {
            const response = await api.post('/login', {
                login: loginValue,
                password: password,
            })

            user.value = response.data.user
            token.value = response.data.token

            // Check if must change password
            if (response.data.must_change_password) {
                await router.push('/change-password')
            } else {
                await router.push('/dashboard')
            }

            return response.data
        } catch (error: any) {
            throw new Error(error.response?.data?.message || 'Login failed')
        }
    }

    async function logout() {
        try {
            await api.post('/logout')
        } catch (error) {
            console.error('Logout error:', error)
        } finally {
            user.value = null
            token.value = null
            await router.push('/login')
        }
    }

    async function loadUser() {
        try {
            const response = await api.get('/me')
            user.value = response.data.user
            return response.data
        } catch (error) {
            console.error('Load user error:', error)
            logout()
        }
    }

    async function changePassword(currentPassword: string, newPassword: string, confirmPassword: string) {
        try {
            const response = await api.post('/change-password', {
                current_password: currentPassword,
                new_password: newPassword,
                new_password_confirmation: confirmPassword,
            })

            // Reload user to update must_change_password flag
            await loadUser()
            await router.push('/dashboard')

            return response.data
        } catch (error: any) {
            throw new Error(error.response?.data?.message || 'Password change failed')
        }
    }

    return {
        user,
        token,
        isAuthenticated,
        login,
        logout,
        loadUser,
        changePassword,
    }
}, {
    persist: {
        storage: localStorage,
        paths: ['token', 'user'],
    },
})
