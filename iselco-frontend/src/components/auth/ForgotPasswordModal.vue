<template>
  <ion-modal :is-open="isOpen" @didDismiss="cancel" class="forgot-password-modal">
    <div class="modal-content bg-white h-full overflow-y-auto">
      <div class="p-6">
        <div class="text-center mb-6">
          <div class="w-16 h-16 mx-auto mb-4 bg-teal-100 rounded-full flex items-center justify-center">
            <ion-icon :icon="keyOutline" class="text-3xl text-teal"></ion-icon>
          </div>
          <h2 class="text-2xl font-bold text-navy-700">Forgot Password</h2>
          <p class="text-gray-600 mt-2">Enter your details to request a password reset.</p>
        </div>

        <form @submit.prevent="submitRequest">
          <!-- Username Field -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Username <span class="text-red-500">*</span>
            </label>
            <ion-input
              v-model="form.username"
              type="text"
              placeholder="Enter your system username"
              class="border border-gray-300 rounded-lg px-4 py-3 w-full focus:ring-2 focus:ring-teal focus:border-transparent"
              :class="{ 'border-red-500': errors.username }"
              required
            ></ion-input>
            <p v-if="errors.username" class="text-red-500 text-sm mt-1">{{ errors.username }}</p>
          </div>

          <!-- Employee Name Field -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Employee Name <span class="text-red-500">*</span>
            </label>
            <ion-input
              v-model="form.employee_name"
              type="text"
              placeholder="Enter your full name"
              class="border border-gray-300 rounded-lg px-4 py-3 w-full focus:ring-2 focus:ring-teal focus:border-transparent"
              required
            ></ion-input>
          </div>

          <!-- Department Selection -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Department <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.department_id"
              class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-teal focus:border-transparent bg-white"
              required
            >
              <option value="" disabled selected>Select your department</option>
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                {{ dept.name }} ({{ dept.code }})
              </option>
            </select>
          </div>

          <!-- Mobile Number Field -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Mobile Number
            </label>
            <ion-input
              v-model="form.mobile_number"
              type="tel"
              placeholder="Enter your mobile number"
              class="border border-gray-300 rounded-lg px-4 py-3 w-full focus:ring-2 focus:ring-teal focus:border-transparent"
            ></ion-input>
          </div>

          <!-- Error Message -->
          <div v-if="errorMessage" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-700 text-sm">{{ errorMessage }}</p>
          </div>

          <!-- Success Message -->
          <div v-if="successMessage" class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-green-700 text-sm">{{ successMessage }}</p>
            <p class="text-green-600 text-xs mt-1">Ticket Number: {{ ticketNumber }}</p>
          </div>

          <!-- Actions -->
          <div class="flex space-x-3">
            <button
              type="button"
              @click="cancel"
              class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-4 rounded-lg transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="loading || !!successMessage"
              class="flex-1 bg-teal hover:bg-teal-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="!loading">Send Ticket</span>
              <ion-spinner v-else name="crescent" class="w-5 h-5"></ion-spinner>
            </button>
          </div>
        </form>
      </div>
    </div>
  </ion-modal>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { IonModal, IonInput, IonIcon, IonSpinner } from '@ionic/vue';
import { keyOutline } from 'ionicons/icons';
import api from '@/services/api';

const props = defineProps<{
  isOpen: boolean;
}>();

const emit = defineEmits(['update:isOpen', 'close']);

const form = reactive({
  username: '',
  employee_name: '',
  department_id: '',
  mobile_number: '',
});

const departments = ref<any[]>([]);
const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const ticketNumber = ref('');
const errors = ref<any>({});

async function fetchDepartments() {
  try {
    const response = await api.get('/departments');
    departments.value = response.data;
  } catch (error) {
    console.error('Failed to load departments', error);
  }
}

onMounted(() => {
  fetchDepartments();
});

function cancel() {
  emit('update:isOpen', false);
  emit('close');
  resetForm();
}

function resetForm() {
  form.username = '';
  form.employee_name = '';
  form.department_id = '';
  form.mobile_number = '';
  errorMessage.value = '';
  successMessage.value = '';
  ticketNumber.value = '';
  errors.value = {};
}

async function submitRequest() {
  loading.value = true;
  errorMessage.value = '';
  successMessage.value = '';
  errors.value = {};

  try {
    const response = await api.post('/forgot-password', form);
    successMessage.value = response.data.message;
    ticketNumber.value = response.data.ticket_number;
    
    // Optional: Close modal after delay
    setTimeout(() => {
        cancel();
    }, 3000);

  } catch (error: any) {
    if (error.response && error.response.status === 422) {
      errors.value = error.response.data.errors;
      errorMessage.value = 'Please check your inputs.';
    } else {
      errorMessage.value = error.response?.data?.message || 'Failed to submit request. Please try again.';
    }
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.forgot-password-modal {
  --height: auto;
  --max-height: 90%;
  --border-radius: 16px;
  --width: 90%;
  --max-width: 500px;
}

ion-input {
  --background: white;
  --padding-start: 0;
  --padding-end: 0;
}

/* Ensure select looks good on mobile */
select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236B7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
}
</style>
