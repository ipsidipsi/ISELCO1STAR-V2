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
          
          <!-- File Upload Buttons (directly under description) -->
          <div class="flex items-center gap-2 mt-2">
            <FileUploadButton
              :multiple="true"
              :show-camera-button="true"
              :show-label="false"
              @files-selected="handleFilesSelected"
            />
            <span v-if="selectedFiles.length > 0" class="text-xs text-gray-500">
              {{ selectedFiles.length }} file(s) selected
            </span>
          </div>
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

        <!-- Selected Files Preview -->
        <div v-if="selectedFiles.length > 0">
          <div class="selected-files mt-3">
            <div
              v-for="(fileItem, index) in selectedFiles"
              :key="fileItem.id"
              class="file-chip-container"
            >
              <!-- Remove button (shows on hover when ready or failed) -->
              <button
                v-if="(fileItem.ready || fileItem.failed) && !fileItem.uploading && !fileItem.uploaded"
                @click="removeFile(index)"
                class="file-remove-btn"
                type="button"
                title="Remove file"
              >
                <ion-icon :icon="closeCircleOutline"></ion-icon>
              </button>

              <div class="file-chip">
                <ion-icon 
                  :icon="getFileIconForType(fileItem.file.type)" 
                  class="file-chip-icon"
                ></ion-icon>
                <div class="file-info">
                  <span class="file-chip-name">{{ truncateFileName(fileItem.file.name) }}</span>
                  <span class="file-chip-size">{{ formatBytes(fileItem.file.size) }}</span>
                  
                  <!-- Progress bar (only during upload) -->
                  <div v-if="fileItem.uploading" class="file-progress-bar">
                    <div 
                      class="file-progress-fill" 
                      :style="{ width: fileItem.progress + '%' }"
                    ></div>
                  </div>
                  
                  <!-- Upload status text -->
                  <span v-if="fileItem.uploading" class="upload-status">
                    Uploading {{ fileItem.progress }}%
                  </span>
                  <span v-else-if="fileItem.failed" class="upload-status failed">
                    Upload failed
                  </span>
                  <span v-else-if="fileItem.uploaded" class="upload-status sent">
                    Sent
                  </span>
                  <span v-else-if="fileItem.ready" class="upload-status ready">
                    Ready to send
                  </span>
                </div>

                <!-- Status badges (bottom-right corner) -->
                
                <!-- Green checkmark when ready to send -->
                <div v-if="fileItem.ready && !fileItem.uploading && !fileItem.uploaded" class="status-badge ready">
                  <ion-icon :icon="checkmarkCircleOutline"></ion-icon>
                </div>
                
                <!-- Blue checkmark when sent successfully -->
                <div v-if="fileItem.uploaded" class="status-badge sent">
                  <ion-icon :icon="checkmarkCircleOutline"></ion-icon>
                </div>
                
                <!-- Red X when failed -->
                <div v-if="fileItem.failed" class="status-badge error">
                  <ion-icon :icon="closeCircleOutline"></ion-icon>
                </div>
              </div>
            </div>
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
import { 
  closeOutline, 
  checkmarkCircleOutline, 
  closeCircleOutline,
  documentTextOutline,
  documentOutline,
  imageOutline,
  documentAttachOutline,
  archiveOutline
} from 'ionicons/icons'
import { useMetadataStore } from '@/stores/metadata'
import { useTickets } from '@/composables/useTickets'
import { useAttachments } from '@/composables/useAttachments'
import { useNotification } from '@/composables/useNotification'
import FileUploadButton from './FileUploadButton.vue'
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
const { showSuccess, showError, showConfirm } = useNotification()

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

// File attachment states
interface SelectedFileItem {
  file: File
  id: string
  preview?: string
  ready: boolean
  uploading: boolean
  uploaded: boolean
  failed: boolean
  progress: number
}

const selectedFiles = ref<SelectedFileItem[]>([])

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

// File handling functions
function handleFilesSelected(files: File[]) {
  for (const file of files) {
    selectedFiles.value.push({
      file,
      id: Math.random().toString(36).substring(7),
      preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : undefined,
      ready: true,
      uploading: false,
      uploaded: false,
      failed: false,
      progress: 0
    })
  }
}

function removeFile(index: number) {
  const file = selectedFiles.value[index]
  if (file.preview) {
    URL.revokeObjectURL(file.preview)
  }
  selectedFiles.value.splice(index, 1)
}

function formatBytes(bytes: number): string {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 10) / 10 + ' ' + sizes[i]
}

function truncateFileName(name: string, maxLength: number = 20): string {
  if (name.length <= maxLength) return name
  const ext = name.split('.').pop() || ''
  const nameWithoutExt = name.substring(0, name.length - ext.length - 1)
  const truncated = nameWithoutExt.substring(0, maxLength - ext.length - 4)
  return `${truncated}...${ext}`
}

function getFileIconForType(mimeType: string) {
  if (mimeType.startsWith('image/')) return imageOutline
  if (mimeType === 'application/pdf') return documentTextOutline
  if (mimeType.includes('word') || mimeType.includes('document')) return documentOutline
  if (mimeType.includes('zip') || mimeType.includes('rar')) return archiveOutline
  return documentAttachOutline
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
  
  // Show confirmation dialog
  const selectedDepartment = departments.value.find(d => d.id === Number(form.value.department_id))
  const selectedCategory = categories.value.find(c => c.id === Number(form.value.category_id))
  
  const confirmationMessage = `You are about to create a new ticket with the following details:

Title: ${form.value.title}
Department: ${selectedDepartment?.name}
Category: ${selectedCategory?.name}
Priority: ${selectedPriorityInfo.value?.name}
${selectedUser.value ? `Assigned To: ${selectedUser.value.employee_name || selectedUser.value.username}` : 'Assigned To: Auto-assignment'}

Do you want to proceed?`

  const result = await showConfirm(
    'Create New Ticket?',
    confirmationMessage,
    'Create Ticket',
    'Cancel'
  )

  if (!result.isConfirmed) {
    return // User cancelled
  }
  
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
    
    // Upload files if any (before showing success)
    if (selectedFiles.value.length > 0 && ticket) {
      const { uploadFile } = useAttachments()
      
      // Upload each file and track progress
      for (let i = 0; i < selectedFiles.value.length; i++) {
        const fileItem = selectedFiles.value[i]
        
        try {
          // Mark as uploading (hide ready checkmark, show progress)
          fileItem.ready = false
          fileItem.uploading = true
          fileItem.progress = 0
          fileItem.failed = false
          
          // Upload the file
          const result = await uploadFile(fileItem.file, 'App\\Models\\Ticket', ticket.id)
          
          if (result) {
            // Mark as uploaded with progress
            fileItem.progress = 100
            fileItem.uploading = false
            fileItem.uploaded = true
          } else {
            // Upload returned null (validation failed)
            fileItem.uploading = false
            fileItem.failed = false
          }
        } catch (error) {
          // Upload threw an error
          fileItem.uploading = false
          fileItem.failed = true
        }
      }
      
      // Small delay to show completion state
      await new Promise(resolve => setTimeout(resolve, 500))
    }
    
    // Emit created event first to refresh the ticket list
    emit('created')
    
    // Close modal before showing toast to prevent interference
    closeModal()
    
    // Show success notification with ticket number after modal is closed
    const successMessage = selectedFiles.value.length > 0
      ? `Your ticket ${ticket.ticket_number} has been created with ${selectedFiles.value.filter(f => f.uploaded).length} attachment(s)`
      : `Your ticket ${ticket.ticket_number} has been created and assigned priority: ${selectedPriorityInfo.value?.name}`
    
    await showSuccess(
      'Ticket Created Successfully!',
      successMessage
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
  // Clear selected files and clean up previews
  selectedFiles.value.forEach(f => {
    if (f.preview) URL.revokeObjectURL(f.preview)
  })
  selectedFiles.value = []
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

/* File attachment styles */
.selected-files {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-top: 0.75rem;
  padding: 0.75rem;
  background: rgba(249, 250, 251, 0.5);
  border-radius: 0.5rem;
}

.file-chip-container {
  position: relative;
  min-width: 120px;
}

.file-chip {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  background: white;
  border: 2px solid #E5E7EB;
  border-radius: 0.75rem;
  transition: all 0.2s ease;
}

.file-chip:hover {
  border-color: #14B8A6;
  box-shadow: 0 2px 8px rgba(20, 184, 166, 0.2);
}

.file-chip-icon {
  color: #14B8A6;
  font-size: 2.5rem;
}

.file-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  width: 100%;
}

.file-chip-name {
  color: #1F2937;
  font-weight: 500;
  font-size: 0.8125rem;
  text-align: center;
  word-break: break-word;
}

.file-chip-size {
  color: #6B7280;
  font-size: 0.6875rem;
}

.file-remove-btn {
  position: absolute;
  top: -8px;
  right: -8px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  background: #6B7280;
  border: 2px solid white;
  border-radius: 50%;
  color: white;
  cursor: pointer;
  font-size: 1rem;
  z-index: 10;
  transition: all 0.2s ease;
  padding: 0;
  opacity: 0;
}

.file-chip-container:hover .file-remove-btn {
  opacity: 1;
}

.file-remove-btn:hover {
  transform: scale(1.15);
  background: #4B5563;
}

.status-badge {
  position: absolute;
  bottom: 8px;
  right: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border: 2px solid white;
  border-radius: 50%;
  font-size: 1rem;
  z-index: 10;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.status-badge.ready {
  background: #10B981;
  color: white;
}

.status-badge.sent {
  background: #3B82F6;
  color: white;
}

.status-badge.error {
  background: #EF4444;
  color: white;
}

.file-progress-bar {
  width: 100%;
  height: 4px;
  background: #E5E7EB;
  border-radius: 2px;
  overflow: hidden;
  margin-top: 0.25rem;
}

.file-progress-fill {
  height: 100%;
  background: linear-gradient(135deg, #14B8A6, #0D9488);
  transition: width 0.3s ease;
  border-radius: 2px;
}

.upload-status {
  font-size: 0.6875rem;
  color: #6B7280;
  margin-top: 0.25rem;
}

.upload-status.ready {
  color: #10B981;
  font-weight: 600;
}

.upload-status.sent {
  color: #3B82F6;
  font-weight: 600;
}

.upload-status.failed {
  color: #EF4444;
  font-weight: 600;
}
</style>
