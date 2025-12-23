<template>
  <ion-page>
    <!-- Header -->
    <ion-header class="bg-white shadow-sm">
      <ion-toolbar class="px-4">
        <div class="flex items-center py-2">
          <h1 class="text-xl font-bold text-navy-700">Change Password</h1>
        </div>
      </ion-toolbar>
    </ion-header>

    <!-- Content -->
    <ion-content :fullscreen="true" class="bg-gray-50">
      <div class="flex items-center justify-center min-h-screen px-4">
        <div class="w-full max-w-md">
          
          <!-- Info Alert -->
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <div class="flex">
              <ion-icon :icon="warningOutline" class="text-yellow-600 text-xl mr-3"></ion-icon>
              <div>
                <h3 class="text-sm font-semibold text-yellow-800">Password Change Required</h3>
                <p class="text-sm text-yellow-700 mt-1">
                  You must change your password before continuing.
                </p>
              </div>
            </div>
          </div>

          <!-- Change Password Card -->
          <div class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-semibold text-navy-700 mb-6">Set New Password</h2>

            <form @submit.prevent="handleChangePassword">
              
              <!-- New Password -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  New Password
                </label>
                <ion-input
                  v-model="newPassword"
                  :type="showNew ? 'text' : 'password'"
                  placeholder="Enter new password"
                  class="border border-gray-300 rounded-lg px-4 py-3 w-full focus:ring-2 focus:ring-teal focus:border-transparent"
                  :class="{ 'border-red-500': errors.newPass }"
                ></ion-input>
                <p v-if="errors.newPass" class="text-red-500 text-sm mt-1">{{ errors.newPass }}</p>
              </div>

              <!-- Confirm Password -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Confirm New Password
                </label>
                <ion-input
                  v-model="confirmPassword"
                  :type="showConfirm ? 'text' : 'password'"
                  placeholder="Confirm new password"
                  class="border border-gray-300 rounded-lg px-4 py-3 w-full focus:ring-2 focus:ring-teal focus:border-transparent"
                  :class="{ 'border-red-500': errors.confirm }"
                ></ion-input>
                <p v-if="errors.confirm" class="text-red-500 text-sm mt-1">{{ errors.confirm }}</p>
              </div>

              <!-- Show Password Toggle -->
              <div class="flex items-center space-x-4 mb-6">
                <label class="flex items-center">
                  <input
                    type="checkbox"
                    v-model="showNew"
                    class="w-4 h-4 text-teal border-gray-300 rounded focus:ring-teal"
                  />
                  <span class="ml-2 text-sm text-gray-700">Show password</span>
                </label>
              </div>

              <!-- Error Message -->
              <div v-if="errorMessage" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-700 text-sm">{{ errorMessage }}</p>
              </div>

              <!-- Submit Button -->
              <button
                type="submit"
                :disabled="loading"
                class="w-full bg-teal hover:bg-teal-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="!loading">Change Password</span>
                <ion-spinner v-else name="crescent" class="w-5 h-5"></ion-spinner>
              </button>
            </form>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { IonPage, IonHeader, IonToolbar, IonContent, IonInput, IonIcon, IonSpinner } from '@ionic/vue'
import { warningOutline } from 'ionicons/icons'
import { useAuth } from '@/composables/useAuth'

const { changePassword, loading, errorMessage } = useAuth()

// Form state
const newPassword = ref('')
const confirmPassword = ref('')
const showNew = ref(false)
const showConfirm = ref(false)

const errors = ref<{ newPass?: string; confirm?: string }>({})

async function handleChangePassword() {
  // Reset errors
  errors.value = {}
  errorMessage.value = ''
  
  // Simple validation
  if (!newPassword.value) {
    errors.value.newPass = 'New password is required'
    return
  }
  
  if (newPassword.value !== confirmPassword.value) {
    errors.value.confirm = 'Passwords do not match'
    return
  }
  
  // Call change password without current password (will use null)
  await changePassword(null, newPassword.value, confirmPassword.value)
}
</script>
