<template>
  <ion-page>
    <!-- Header -->
    <ion-header class="glass-header">
      <ion-toolbar class="px-4 bg-transparent">
        <div class="flex items-center justify-between py-2">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
              <ion-icon :icon="settingsOutline" class="text-xl text-white"></ion-icon>
            </div>
            <div>
              <h1 class="text-xl font-bold text-navy-700">Category Management</h1>
              <p class="text-sm text-gray-600">Manage categories, priorities, and departments</p>
            </div>
          </div>
          <ion-button fill="clear" @click="router.push('/dashboard')" class="glass-button">
            <ion-icon :icon="closeOutline" class="text-gray-700"></ion-icon>
          </ion-button>
        </div>
      </ion-toolbar>
    </ion-header>

    <!-- Content -->
    <ion-content :fullscreen="true" class="gradient-bg">
      <div class="p-4 max-w-7xl mx-auto">
        
        <!-- Tabs -->
        <div class="glass-card mb-6 p-0">
          <div class="flex border-b border-gray-200">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                'flex-1 px-6 py-4 text-sm font-medium transition-all',
                activeTab === tab.id
                  ? 'text-purple-600 border-b-2 border-purple-600 bg-purple-50'
                  : 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
              ]"
            >
              <ion-icon :icon="tab.icon" class="mr-2"></ion-icon>
              {{ tab.label }}
            </button>
          </div>
        </div>

        <!-- Tab Content -->
        
        <!-- Categories Tab -->
        <div v-if="activeTab === 'categories'" class="glass-card">
          <div class="flex justify-between items-center mb-6">
            <div>
              <h2 class="text-lg font-semibold text-navy-700">Ticket Categories</h2>
              <p class="text-sm text-gray-600">Manage ticket categories and their settings</p>
            </div>
            <div class="flex space-x-2">
              <ion-button 
                v-if="orphanedCount > 0"
                @click="showOrphanedOnly = !showOrphanedOnly"
                :fill="showOrphanedOnly ? 'solid' : 'outline'"
                color="warning"
                size="small"
              >
                <ion-icon :icon="warningOutline" slot="start"></ion-icon>
                Orphaned ({{ orphanedCount }})
              </ion-button>
              <ion-button @click="openCategoryModal()" color="primary">
                <ion-icon :icon="addCircleOutline" slot="start"></ion-icon>
                Add Category
              </ion-button>
            </div>
          </div>

          <!-- Categories Table -->
          <div v-if="categoriesLoading" class="flex justify-center py-8">
            <ion-spinner name="crescent"></ion-spinner>
          </div>
          
          <div v-else-if="displayedCategories.length === 0" class="text-center py-8 text-gray-500">
            <ion-icon :icon="folderOpenOutline" class="text-5xl mb-2"></ion-icon>
            <p>No categories found. Create your first category!</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="category in displayedCategories" :key="category.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3">
                    <div class="flex items-center space-x-2">
                      <div 
                        v-if="category.icon"
                        :style="{ backgroundColor: category.color || '#6B7280' }"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-white"
                      >
                        <ion-icon :icon="category.icon || documentTextOutline"></ion-icon>
                      </div>
                      <div>
                        <div class="font-medium text-navy-700">{{ category.name }}</div>
                        <div v-if="category.description" class="text-sm text-gray-500">{{ category.description }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <div v-if="category.department" class="flex items-center space-x-2">
                      <span class="text-sm text-gray-700">{{ category.department.name }}</span>
                      <span 
                        v-if="category.is_orphaned" 
                        class="px-2 py-1 text-xs font-medium bg-orange-100 text-orange-700 rounded-full"
                      >
                        ⚠️ Inactive Dept
                      </span>
                    </div>
                    <span v-else class="text-sm text-gray-500 italic">Global</span>
                  </td>
                  <td class="px-4 py-3">
                    <span 
                      v-if="category.priority"
                      :style="{ backgroundColor: category.priority.color }"
                      class="px-2 py-1 text-xs font-medium text-white rounded-full"
                    >
                      {{ category.priority.name }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <span 
                      :class="category.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
                      class="px-2 py-1 text-xs font-medium rounded-full"
                    >
                      {{ category.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-right">
                    <div class="flex justify-end space-x-2">
                      <ion-button 
                        v-if="category.is_orphaned"
                        @click="convertCategoryToGlobal(category)"
                        fill="outline"
                        size="small"
                        color="warning"
                      >
                        <ion-icon :icon="globeOutline" slot="icon-only"></ion-icon>
                      </ion-button>
                      <ion-button @click="openCategoryModal(category)" fill="outline" size="small">
                        <ion-icon :icon="createOutline" slot="icon-only"></ion-icon>
                      </ion-button>
                      <ion-button @click="confirmDeleteCategory(category)" fill="outline" size="small" color="danger">
                        <ion-icon :icon="trashOutline" slot="icon-only"></ion-icon>
                      </ion-button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Priorities Tab -->
        <div v-if="activeTab === 'priorities'" class="glass-card">
          <div class="flex justify-between items-center mb-6">
            <div>
              <h2 class="text-lg font-semibold text-navy-700">Priority Levels</h2>
              <p class="text-sm text-gray-600">Manage ticket priority levels and SLA hours</p>
            </div>
            <ion-button @click="openPriorityModal()" color="primary">
              <ion-icon :icon="addCircleOutline" slot="start"></ion-icon>
              Add Priority
            </ion-button>
          </div>

          <div v-if="prioritiesLoading" class="flex justify-center py-8">
            <ion-spinner name="crescent"></ion-spinner>
          </div>

          <div v-else-if="priorities.length === 0" class="text-center py-8 text-gray-500">
            <ion-icon :icon="flagOutline" class="text-5xl mb-2"></ion-icon>
            <p>No priorities found. Create your first priority level!</p>
          </div>

          <div v-else class="space-y-3">
            <div 
              v-for="priority in priorities" 
              :key="priority.id"
              class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition"
            >
              <div class="flex items-center space-x-4">
                <div 
                  :style="{ backgroundColor: priority.color }"
                  class="w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold"
                >
                  {{ priority.level }}
                </div>
                <div>
                  <div class="font-medium text-navy-700">{{ priority.name }}</div>
                  <div class="text-sm text-gray-600">SLA: {{ priority.sla_hours }} hours</div>
                  <div v-if="priority.ticket_count" class="text-xs text-gray-500 mt-1">
                    Used by {{ priority.ticket_count }} ticket(s)
                  </div>
                </div>
              </div>
              <div class="flex space-x-2">
                <ion-button @click="openPriorityModal(priority)" fill="outline" size="small">
                  <ion-icon :icon="createOutline" slot="icon-only"></ion-icon>
                </ion-button>
                <ion-button @click="confirmDeletePriority(priority)" fill="outline" size="small" color="danger">
                  <ion-icon :icon="trashOutline" slot="icon-only"></ion-icon>
                </ion-button>
              </div>
            </div>
          </div>
        </div>

        <!-- Departments Tab -->
        <div v-if="activeTab === 'departments'" class="glass-card">
          <div class="flex justify-between items-center mb-6">
            <div>
              <h2 class="text-lg font-semibold text-navy-700">Departments (External Sync)</h2>
              <p class="text-sm text-gray-600">View-only - Synced from external API</p>
            </div>
            <ion-button @click="loadSyncStatus()" color="primary" fill="outline" :disabled="syncLoading">
              <ion-icon :icon="refreshOutline" slot="start"></ion-icon>
              {{ syncLoading ? 'Syncing...' : 'Sync Now' }}
            </ion-button>
          </div>

          <!-- Sync Status Card -->
          <div v-if="syncStatus" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="p-4 bg-blue-50 rounded-lg">
              <div class="text-xs text-blue-600 font-medium mb-1">Total Departments</div>
              <div class="text-2xl font-bold text-blue-700">{{ syncStatus.total_departments }}</div>
            </div>
            <div class="p-4 bg-green-50 rounded-lg">
              <div class="text-xs text-green-600 font-medium mb-1">Active</div>
              <div class="text-2xl font-bold text-green-700">{{ syncStatus.active_departments }}</div>
            </div>
            <div class="p-4 bg-orange-50 rounded-lg">
              <div class="text-xs text-orange-600 font-medium mb-1">Inactive</div>
              <div class="text-2xl font-bold text-orange-700">{{ syncStatus.inactive_departments }}</div>
            </div>
            <div class="p-4 bg-purple-50 rounded-lg">
              <div class="text-xs text-purple-600 font-medium mb-1">Affected Categories</div>
              <div class="text-2xl font-bold text-purple-700">{{ syncStatus.affected_categories }}</div>
            </div>
          </div>

          <!-- Last Sync Info -->
          <div v-if="syncStatus?.last_synced_at" class="mb-4 p-3 bg-gray-50 rounded-lg text-sm text-gray-600">
            <ion-icon :icon="timeOutline" class="mr-2"></ion-icon>
            Last synced: {{ formatDate(syncStatus.last_synced_at) }}
          </div>

          <!-- Departments List -->
          <div v-if="syncLoading" class="flex justify-center py-8">
            <ion-spinner name="crescent"></ion-spinner>
          </div>

          <div v-else class="space-y-3">
            <div 
              v-for="dept in allDepartments" 
              :key="dept.id"
              :class="[
                'flex items-center justify-between p-4 rounded-lg transition',
                dept.is_active ? 'bg-green-50' : 'bg-orange-50'
              ]"
            >
              <div class="flex items-center space-x-3">
                <div 
                  :class="dept.is_active ? 'bg-green-500' : 'bg-orange-500'"
                  class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold"
                >
                  {{ dept.code?.substring(0, 2).toUpperCase() }}
                </div>
                <div>
                  <div class="font-medium text-navy-700">{{ dept.name }}</div>
                  <div class="text-sm text-gray-600">Code: {{ dept.code }}</div>
                </div>
              </div>
              <div class="flex items-center space-x-3">
                <span 
                  :class="dept.is_active ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'"
                  class="px-3 py-1 text-xs font-medium rounded-full"
                >
                  {{ dept.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </ion-content>

    <!-- Category Form Modal -->
    <CategoryFormModal
      :is-open="showCategoryModal"
      :category="selectedCategory"
      :departments="activeDepartments"
      :priorities="priorities"
      @close="closeCategoryModal"
      @save="handleCategorySave"
    />

    <!-- Priority Form Modal -->
    <PriorityFormModal
      :is-open="showPriorityModal"
      :priority="selectedPriority"
      @close="closePriorityModal"
      @save="handlePrioritySave"
    />

  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  IonPage, IonHeader, IonToolbar, IonContent, IonButton, IonIcon, IonSpinner,
  alertController
} from '@ionic/vue'
import {
  settingsOutline, closeOutline, addCircleOutline, createOutline, trashOutline,
  documentsOutline, flagOutline, businessOutline, warningOutline, globeOutline,
  documentTextOutline, folderOpenOutline, refreshOutline, timeOutline
} from 'ionicons/icons'
import { useCategories, type Category } from '@/composables/useCategories'
import { usePriorities, type Priority } from '@/composables/usePriorities'
import { useDepartmentSync } from '@/composables/useDepartmentSync'
import { useNotification } from '@/composables/useNotification'
import api from '@/services/api'
import CategoryFormModal from '@/components/CategoryFormModal.vue'
import PriorityFormModal from '@/components/PriorityFormModal.vue'

const router = useRouter()
const { categories, loading: categoriesLoading, fetchCategories, deleteCategory, convertToGlobal } = useCategories()
const { priorities, loading: prioritiesLoading, fetchPriorities, deletePriority } = usePriorities()
const { syncStatus, loading: syncLoading, fetchSyncStatus, triggerSync } = useDepartmentSync()
const { showSuccess, showError, showWarning, showInfo } = useNotification()

// Departments state
const departments = ref<Array<{ id: number; name: string; code: string; is_active: boolean }>>([])

// Tab state
const activeTab = ref('categories')
const tabs = [
  { id: 'categories', label: 'Categories', icon: documentsOutline },
  { id: 'priorities', label: 'Priorities', icon: flagOutline },
  { id: 'departments', label: 'Departments', icon: businessOutline }
]

// Category state
const showCategoryModal = ref(false)
const selectedCategory = ref<Category | null>(null)
const showOrphanedOnly = ref(false)

// Priority state
const showPriorityModal = ref(false)
const selectedPriority = ref<Priority | null>(null)

// Computed
const activeDepartments = computed(() => departments.value.filter((d: any) => d.is_active))
const allDepartments = computed(() => departments.value)
const orphanedCount = computed(() => categories.value.filter(c => c.is_orphaned).length)
const displayedCategories = computed(() => {
  if (showOrphanedOnly.value) {
    return categories.value.filter(c => c.is_orphaned)
  }
  return categories.value
})

// Category methods
function openCategoryModal(category?: Category) {
  selectedCategory.value = category || null
  showCategoryModal.value = true
}

function closeCategoryModal() {
  showCategoryModal.value = false
  selectedCategory.value = null
}

async function handleCategorySave() {
  closeCategoryModal()
  await fetchCategories()
  showSuccess('Category saved successfully')
}

async function confirmDeleteCategory(category: Category) {
  const alert = await alertController.create({
    header: 'Delete Category',
    message: `Are you sure you want to deactivate "${category.name}"?`,
    buttons: [
      { text: 'Cancel', role: 'cancel' },
      {
        text: 'Delete',
        role: 'destructive',
        handler: async () => {
          try {
            await deleteCategory(category.id)
            showSuccess('Category deactivated successfully')
          } catch (error) {
            showError('Failed to delete category')
          }
        }
      }
    ]
  })
  await alert.present()
}

async function convertCategoryToGlobal(category: Category) {
  const alert = await alertController.create({
    header: 'Convert to Global',
    message: `Convert "${category.name}" to a global category (remove department link)?`,
    buttons: [
      { text: 'Cancel', role: 'cancel' },
      {
        text: 'Convert',
        handler: async () => {
          try {
            await convertToGlobal(category.id)
            showSuccess('Category converted to global')
          } catch (error) {
            showError('Failed to convert category')
          }
        }
      }
    ]
  })
  await alert.present()
}

// Priority methods
function openPriorityModal(priority?: Priority) {
  selectedPriority.value = priority || null
  showPriorityModal.value = true
}

function closePriorityModal() {
  showPriorityModal.value = false
  selectedPriority.value = null
}

async function handlePrioritySave() {
  closePriorityModal()
  await fetchPriorities()
  showSuccess('Priority saved successfully')
}

async function confirmDeletePriority(priority: Priority) {
  const alert = await alertController.create({
    header: 'Delete Priority',
    message: `Are you sure you want to delete "${priority.name}"?${
      priority.ticket_count ? `\n\nWarning: This priority is used by ${priority.ticket_count} ticket(s).` : ''
    }`,
    buttons: [
      { text: 'Cancel', role: 'cancel' },
      {
        text: 'Delete',
        role: 'destructive',
        handler: async () => {
          try {
            await deletePriority(priority.id)
            showSuccess('Priority deleted successfully')
          } catch (error: any) {
            showError(error.response?.data?.message || 'Failed to delete priority')
          }
        }
      }
    ]
  })
  await alert.present()
}

// Department sync methods
async function loadDepartments() {
  try {
    const response = await api.get('/departments')
    departments.value = response.data
  } catch (error) {
    showError('Failed to load departments')
  }
}

async function loadSyncStatus() {
  try {
    showInfo('Syncing departments from external API...')
    await triggerSync()
    await loadDepartments()
    await fetchSyncStatus()
    showSuccess('Departments synced successfully!')
  } catch (error: any) {
    showError(error.response?.data?.message || 'Failed to sync departments')
  }
}

// Utility
function formatDate(date: string) {
  return new Date(date).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchCategories(),
    fetchPriorities(),
    loadDepartments(),
    fetchSyncStatus()
  ])
})
</script>

<style scoped>
/* Gradient Background */
.gradient-bg {
  --background: linear-gradient(135deg, #f5f7fa 0%, #e6eef5 100%);
}

/* Glass Header */
.glass-header {
  --background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

/* Glass Cards */
.glass-card {
  background: rgba(255, 255, 255, 0.75);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-radius: 1rem;
  padding: 1.5rem;
  border: 1px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
  transition: all 0.3s ease;
}

.glass-button {
  background: rgba(255, 255, 255, 0.3);
  backdrop-filter: blur(5px);
  -webkit-backdrop-filter: blur(5px);
  border-radius: 0.5rem;
}

table {
  border-collapse: separate;
  border-spacing: 0;
}

table thead tr th {
  font-weight: 600;
  letter-spacing: 0.05em;
}

table tbody tr {
  transition: background-color 0.2s;
}
</style>
