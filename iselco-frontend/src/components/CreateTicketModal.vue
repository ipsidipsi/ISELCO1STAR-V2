<template>
  <ion-modal :is-open="isOpen" @didDismiss="closeModal">
    <ion-header>
      <ion-toolbar class="px-4">
        <ion-title class="text-xl font-bold text-navy-700">Create New Ticket</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="closeModal">
            <ion-icon :icon="closeOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        
        <!-- Title -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Title <span class="text-red-500">*</span>
          </label>
          <ion-input
            v-model="form.title"
            placeholder="Brief description of the issue"
            class="custom-input"
            :class="{ 'border-red-500': errors.title }"
          ></ion-input>
          <p v-if="errors.title" class="text-red-500 text-sm mt-1">{{ errors.title }}</p>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Description <span class="text-red-500">*</span>
          </label>
          <ion-textarea
            v-model="form.description"
            placeholder="Detailed description of the issue..."
            :rows="4"
            class="custom-input"
            :class="{ 'border-red-500': errors.description }"
          ></ion-textarea>
          <p v-if="errors.description" class="text-red-500 text-sm mt-1">{{ errors.description }}</p>
        </div>

        <!-- Department -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Department <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.department_id"
            @change="handleDepartmentChange"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal focus:border-transparent"
            :class="{ 'border-red-500': errors.department_id }"
          >
            <option value="">Select department</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
              {{ dept.name }}
            </option>
          </select>
          <p v-if="errors.department_id" class="text-red-500 text-sm mt-1">{{ errors.department_id }}</p>
        </div>

        <!-- Category -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Category <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.category_id"
            @change="handleCategoryChange"
            :disabled="!form.department_id"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal focus:border-transparent disabled:opacity-50"
            :class="{ 'border-red-500': errors.category_id }"
          >
            <option value="">Select category</option>
            <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>
          <p v-if="errors.category_id" class="text-red-500 text-sm mt-1">{{ errors.category_id }}</p>
        </div>

        <!-- Priority (Auto-selected based on category) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Priority <span class="text-red-500">*</span>
            <span class="text-xs text-gray-500 ml-2">(Auto-selected from category)</span>
          </label>
          <select
            v-model="form.priority_id"
            disabled
            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed"
            :class="{ 'border-red-500': errors.priority_id }"
          >
            <option value="">Select category first</option>
            <option v-for="priority in priorities" :key="priority.id" :value="priority.id">
              {{ priority.name }} ({{ priority.sla_hours }}h SLA)
            </option>
          </select>
          <p v-if="errors.priority_id" class="text-red-500 text-sm mt-1">{{ errors.priority_id }}</p>
          <p v-if="selectedPriorityInfo" class="text-sm text-teal-600 mt-1">
            ✓ Priority set to: <strong>{{ selectedPriorityInfo.name }}</strong> ({{ selectedPriorityInfo.sla_hours }}h SLA)
          </p>
        </div>

        <!-- Assign To (Optional) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Assign To (Optional)
            <span class="text-xs text-gray-500 ml-2">Search by name or leave empty for auto-assignment</span>
          </label>
          <div class="relative">
            <ion-searchbar
              v-model="userSearchQuery"
              placeholder="Search for user..."
              @ionInput="handleUserSearch"
              :disabled="!form.department_id"
              class="user-searchbar"
            ></ion-searchbar>
            
            <!-- User Search Results Dropdown -->
            <div v-if="showUserDropdown && filteredUsers.length > 0" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto">
              <div
                v-for="user in filteredUsers"
                :key="user.id"
                @click="selectUser(user)"
                class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
              >
                <div class="font-medium text-gray-900">{{ user.employee_name || user.username }}</div>
                <div class="text-sm text-gray-500">
                  @{{ user.username }} • {{ user.department?.name || 'No Department' }}
                </div>
                <div class="text-xs text-gray-400 mt-1">
                  <span v-for="role in user.roles" :key="role.id" class="inline-block mr-2">
                    {{ role.name }}
                  </span>
                </div>
              </div>
            </div>
            
            <!-- No Results -->
            <div v-if="showUserDropdown && userSearchQuery && filteredUsers.length === 0" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 px-4 py-3 text-gray-500 text-sm">
              No users found matching "{{ userSearchQuery }}"
            </div>
          </div>
          
          <!-- Selected User Display -->
          <div v-if="selectedUser" class="mt-2 p-3 bg-teal-50 border border-teal-200 rounded-lg flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 bg-teal-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                {{ selectedUser.employee_name?.charAt(0) || 'U' }}
              </div>
              <div>
                <div class="font-medium text-gray-900">{{ selectedUser.employee_name || selectedUser.username }}</div>
                <div class="text-xs text-gray-600">{{ selectedUser.department?.name || 'No Department' }}</div>
              </div>
            </div>
            <button
              type="button"
              @click="clearSelectedUser"
              class="text-red-600 hover:text-red-800 text-sm font-medium"
            >
              Remove
            </button>
          </div>
        </div>

        <!-- Buttons -->
        <div class="flex space-x-3 pt-4">
          <button
            type="button"
            @click="closeModal"
            class="flex-1 px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="flex-1 px-6 py-3 bg-teal hover:bg-teal-600 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
          >
            <span v-if="!loading">Create Ticket</span>
            <ion-spinner v-else name="crescent" class="w-5 h-5"></ion-spinner>
          </button>
        </div>
      </form>
    </ion-content>
  </ion-modal>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import {
  IonModal, IonHeader, IonToolbar, IonTitle, IonContent,
  IonButtons, IonButton, IonIcon, IonInput, IonTextarea, IonSpinner, IonSearchbar
} from '@ionic/vue'
import { closeOutline } from 'ionicons/icons'
import { useMetadataStore } from '@/stores/metadata'
import { useTickets } from '@/composables/useTickets'
import { useNotification } from '@/composables/useNotification'
import api from '@/services/api'

const props = defineProps<{
  isOpen: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'created'): void
}>()

const metadataStore = useMetadataStore()
const { createTicket } = useTickets()
const { showSuccess, showError } = useNotification()

const form = ref({
  title: '',
  description: '',
  department_id: '',
  category_id: '',
  priority_id: '',
  assigned_to_id: null as number | null,
})

const errors = ref<Record<string, string>>({})
const loading = ref(false)

// User assignment states
const userSearchQuery = ref('')
const allUsers = ref<any[]>([])
const filteredUsers = ref<any[]>([])
const selectedUser = ref<any | null>(null)
const showUserDropdown = ref(false)
const searchTimeout = ref<any>(null)

const departments = computed(() => metadataStore.departments)
const categories = computed(() => metadataStore.categories)
const priorities = computed(() => metadataStore.priorities)

const filteredCategories = computed(() => {
  if (!form.value.department_id) return []
  return categories.value.filter(
    cat => cat.department_id === Number(form.value.department_id) || cat.department_id === null
  )
})

const selectedPriorityInfo = computed(() => {
  if (!form.value.priority_id) return null
  return priorities.value.find(p => p.id === Number(form.value.priority_id))
})

onMounted(async () => {
  if (departments.value.length === 0) {
    await metadataStore.fetchAllMetadata()
  }
})

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    resetForm()
  }
})

function handleDepartmentChange() {
  form.value.category_id = ''
  form.value.priority_id = ''
  // Clear user selection when department changes
  clearSelectedUser()
  // Load users for the new department
  if (form.value.department_id) {
    loadUsersForDepartment(Number(form.value.department_id))
  }
}

function handleCategoryChange() {
  // Auto-select priority based on category
  const selectedCategory = categories.value.find(
    cat => cat.id === Number(form.value.category_id)
  )
  
  if (selectedCategory && selectedCategory.priority_id) {
    form.value.priority_id = String(selectedCategory.priority_id)
  }
}

function validateForm() {
  errors.value = {}
  
  if (!form.value.title) errors.value.title = 'Title is required'
  if (!form.value.description) errors.value.description = 'Description is required'
  if (!form.value.department_id) errors.value.department_id = 'Department is required'
  if (!form.value.category_id) errors.value.category_id = 'Category is required'
  if (!form.value.priority_id) errors.value.priority_id = 'Priority is required'
  
  return Object.keys(errors.value).length === 0
}

// User assignment functions
async function loadUsersForDepartment(departmentId: number) {
  try {
    const response = await api.get('/users', { params: { department_id: departmentId } })
    allUsers.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to load users:', error)
    allUsers.value = []
  }
}

function handleUserSearch() {
  // Debounce search
  clearTimeout(searchTimeout.value)
  
  searchTimeout.value = setTimeout(() => {
    if (!userSearchQuery.value || userSearchQuery.value.length < 2) {
      filteredUsers.value = []
      showUserDropdown.value = false
      return
    }
    
    const query = userSearchQuery.value.toLowerCase()
    filteredUsers.value = allUsers.value.filter(user => 
      user.employee_name?.toLowerCase().includes(query) ||
      user.username?.toLowerCase().includes(query)
    )
    showUserDropdown.value = true
  }, 300)
}

function selectUser(user: any) {
  selectedUser.value = user
  form.value.assigned_to_id = user.id
  userSearchQuery.value = ''
  showUserDropdown.value = false
  filteredUsers.value = []
}

function clearSelectedUser() {
  selectedUser.value = null
  form.value.assigned_to_id = null
  userSearchQuery.value = ''
  filteredUsers.value = []
  showUserDropdown.value = false
}

async function handleSubmit() {
  if (!validateForm()) return
  
  loading.value = true
  
  try {
    const ticketData: any = {
      title: form.value.title,
      description: form.value.description,
      department_id: Number(form.value.department_id),
      category_id: Number(form.value.category_id),
      priority_id: Number(form.value.priority_id),
    }
    
    // Add assigned_to_id if a user is selected
    if (form.value.assigned_to_id) {
      ticketData.assigned_to_id = form.value.assigned_to_id
    }
    
    const ticket = await createTicket(ticketData)
    
    // Emit created event first to refresh the ticket list
    emit('created')
    
    // Close modal before showing toast to prevent interference
    closeModal()
    
    // Show success notification with ticket number after modal is closed
    await showSuccess(
      'Ticket Created Successfully!',
      `Your ticket ${ticket.ticket_number} has been created and assigned priority: ${selectedPriorityInfo.value?.name}`
    )
  } catch (error: any) {
    // Show error notification
    await showError(
      'Failed to Create Ticket',
      error.message || 'An error occurred while creating the ticket. Please try again.'
    )
  } finally {
    loading.value = false
  }
}

function resetForm() {
  form.value = {
    title: '',
    description: '',
    department_id: '',
    category_id: '',
    priority_id: '',
    assigned_to_id: null,
  }
  errors.value = {}
  clearSelectedUser()
}

function closeModal() {
  emit('close')
}
</script>

<style scoped>
.custom-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #D1D5DB;
  border-radius: 0.5rem;
  --background: white;
  --padding-start: 0;
  --padding-end: 0;
}

.custom-input:focus {
  border-color: #14B8A6;
  outline: none;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
}

.user-searchbar {
  --background: white;
  --border-radius: 0.5rem;
  --box-shadow: none;
  --placeholder-color: #9CA3AF;
  padding: 0;
}
</style>
