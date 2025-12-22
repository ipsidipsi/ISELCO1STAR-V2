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
                  You must change your password before continuing. Please choose a strong password.
                </p>
              </div>
            </div>
          </div>

          <!-- Change Password Card -->
          <div class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-semibold text-navy-700 mb-6">Set New Password</h2>

            <form @submit.prevent="handleChangePassword">
              <!-- Current Password -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Current Password
                </label>
                <ion-input
                  v-model="currentPassword"
                  :type="showCurrent ? 'text' : 'password'"
                  placeholder="Enter current password"
                  class="border border-gray-300 rounded-lg px-4 py-3 w-full focus:ring-2 focus:ring-teal focus:border-transparent"
                  :class="{ 'border-red-500': errors.current }"
                ></ion-input>
                <p v-if="errors.current" class="text-red-500 text-sm mt-1">{{ errors.current }}</p>
              </div>

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
                
                <!-- Password Strength Indicator -->
                <div v-if="newPassword" class="mt-2">
                  <div class="flex items-center space-x-2">
                    <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div 
                        class="h-full transition-all duration-300"
                        :class="passwordStrengthClass"
                        :style="{ width: passwordStrengthWidth }"
                      ></div>
                    </div>
                    <span class="text-xs font-medium" :class="passwordStrengthTextClass">
                      {{ passwordStrengthText }}
                    </span>
                  </div>
                </div>
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

              <!-- Show Password Toggles -->
              <div class="flex items-center space-x-4 mb-6">
                <label class="flex items-center">
                  <input
                    type="checkbox"
                    v-model="showNew"
                    class="w-4 h-4 text-teal border-gray-300 rounded focus:ring-teal"
                  />
                  <span class="ml-2 text-sm text-gray-700">Show new password</span>
                </label>
              </div>

              <!-- Password Requirements -->
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h4 class="text-sm font-semibold text-blue-800 mb-2">Password Requirements:</h4>
                <ul class="text-sm text-blue-700 space-y-1">
                  <li class="flex items-center">
                    <ion-icon 
                      :icon="newPassword.length >= 8 ? checkmarkCircleOutline : closeCircleOutline"
                      :class="newPassword.length >= 8 ? 'text-green-600' : 'text-gray-400'"
                      class="mr-2"
                    ></ion-icon>
                    At least 8 characters
                  </li>
                  <li class="flex items-center">
                    <ion-icon 
                      :icon="hasUpperCase ? checkmarkCircleOutline : closeCircleOutline"
                      :class="hasUpperCase ? 'text-green-600' : 'text-gray-400'"
                      class="mr-2"
                    ></ion-icon>
                    One uppercase letter
                  </li>
                  <li class="flex items-center">
                    <ion-icon 
                      :icon="hasNumber ? checkmarkCircleOutline : closeCircleOutline"
                      :class="hasNumber ? 'text-green-600' : 'text-gray-400'"
                      class="mr-2"
                    ></ion-icon>
                    One number
                  </li>
                  <li class="flex items-center">
                    <ion-icon 
                      :icon="newPassword !== '1234' ? checkmarkCircleOutline : closeCircleOutline"
                      :class="newPassword !== '1234' ? 'text-green-600' : 'text-gray-400'"
                      class="mr-2"
                    ></ion-icon>
                    Not the default password "1234"
                  </li>
                </ul>
              </div>

              <!-- Error Message -->
              <div v-if="errorMessage" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-700 text-sm">{{ errorMessage }}</p>
              </div>

              <!-- Submit Button -->
              <button
                type="submit"
                :disabled="loading || !isPasswordValid"
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
import { ref, computed } from 'vue'
import { IonPage, IonHeader, IonToolbar, IonContent, IonInput, IonIcon, IonSpinner } from '@ionic/vue'
import { warningOutline, checkmarkCircleOutline, closeCircleOutline } from 'ionicons/icons'
import { useAuth } from '@/composables/useAuth'

const { changePassword, loading, errorMessage } = useAuth()

// Form state
const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const showCurrent = ref(false)
const showNew = ref(false)
const showConfirm = ref(false)

const errors = ref<{ current?: string; newPass?: string; confirm?: string }>({})

// Password validation
const hasUpperCase = computed(() => /[A-Z]/.test(newPassword.value))
const hasNumber = computed(() => /[0-9]/.test(newPassword.value))
const isNotDefault = computed(() => newPassword.value !== '1234')
const isLongEnough = computed(() => newPassword.value.length >= 8)
const passwordsMatch = computed(() => newPassword.value === confirmPassword.value && confirmPassword.value !== '')

const isPasswordValid = computed(() => {
  return hasUpperCase.value && 
         hasNumber.value && 
         isNotDefault.value && 
         isLongEnough.value && 
         passwordsMatch.value
})

// Password strength
const passwordStrength = computed(() => {
  let strength = 0
  if (isLongEnough.value) strength++
  if (hasUpperCase.value) strength++
  if (hasNumber.value) strength++
  if (/[!@#$%^&*]/.test(newPassword.value)) strength++
  if (newPassword.value.length >= 12) strength++
  return strength
})

const passwordStrengthWidth = computed(() => `${(passwordStrength.value / 5) * 100}%`)

const passwordStrengthClass = computed(() => {
  if (passwordStrength.value <= 2) return 'bg-red-500'
  if (passwordStrength.value <= 3) return 'bg-yellow-500'
  return 'bg-green-500'
})

const passwordStrengthTextClass = computed(() => {
  if (passwordStrength.value <= 2) return 'text-red-600'
  if (passwordStrength.value <= 3) return 'text-yellow-600'
  return 'text-green-600'
})

const passwordStrengthText = computed(() => {
  if (passwordStrength.value <= 2) return 'Weak'
  if (passwordStrength.value <= 3) return 'Medium'
  return 'Strong'
})

async function handleChangePassword() {
  // Reset errors
  errors.value = {}
  
  // Validation
  if (!currentPassword.value) {
    errors.value.current = 'Current password is required'
    return
  }
  
  if (!newPassword.value) {
    errors.value.newPass = 'New password is required'
    return
  }
  
  if (!isPasswordValid.value) {
    errors.value.newPass = 'Password does not meet requirements'
    return
  }
  
  if (newPassword.value !== confirmPassword.value) {
    errors.value.confirm = 'Passwords do not match'
    return
  }
  
  await changePassword(currentPassword.value, newPassword.value, confirmPassword.value)
}
</script>
