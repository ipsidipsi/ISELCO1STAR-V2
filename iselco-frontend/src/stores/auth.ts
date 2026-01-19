import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import router from '@/router'
import { Capacitor } from '@capacitor/core'

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
    permissions?: any[]
    departments?: any[]
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

            // Merge all active roles if provided
            if (response.data.all_roles && user.value) {
                user.value.roles = response.data.all_roles
            }

            // Navigation to Dashboard or Change Password is now handled by Router Guards
            // We just force a clean reload at root to ensure asset paths works correctly on Android
            window.location.replace('/')

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
            // 1. Disconnect Echo/Reverb
            if ((window as any).Echo) {
                (window as any).Echo.disconnect()
            }

            // 2. Clear State
            user.value = null
            token.value = null

            // 3. Clear Storage
            // Just clear specific keys to avoid nuking other app settings if any
            localStorage.removeItem('auth')
            // localStorage.clear() // Removed nuclear option to be safer

            // 4. Redirect based on platform
            if (Capacitor.isNativePlatform()) {
                // Android: Replace route to clear history
                await router.replace('/login')
            } else {
                // Web: Force full reload to ensure memory is clean
                window.location.replace('/login')
            }
        }
    }

    async function loadUser() {
        try {
            const response = await api.get('/me')
            user.value = response.data.user

            // Merge all active roles (permanent + temporary) into user.roles
            if (response.data.all_roles && user.value) {
                user.value.roles = response.data.all_roles
            }

            return response.data
        } catch (error) {
            console.error('Load user error:', error)
            logout()
        }
    }

    async function changePassword(currentPassword: string | null, newPassword: string, confirmPassword: string) {
        try {
            const payload: any = {
                new_password: newPassword,
                new_password_confirmation: confirmPassword,
            }

            // Only include current password if provided
            if (currentPassword) {
                payload.current_password = currentPassword
            }

            const response = await api.post('/change-password', payload)

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
        pick: ['token', 'user'],
    },
})
