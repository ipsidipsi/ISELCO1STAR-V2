<template>
  <ion-modal :is-open="isOpen" @did-dismiss="$emit('close')">
    <ion-header>
      <ion-toolbar>
        <ion-title>{{ category ? 'Edit Category' : 'New Category' }}</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="$emit('close')">
            <ion-icon :icon="closeOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <form @submit.prevent="handleSubmit">
        
        <!-- Name -->
        <ion-item class="mb-4">
          <ion-label position="stacked">Name <span class="text-red-500">*</span></ion-label>
          <ion-input
            v-model="formData.name"
            placeholder="Enter category name"
            :class="errors.name ? 'ion-invalid ion-touched' : ''"
          ></ion-input>
          <ion-note v-if="errors.name" slot="error">{{ errors.name }}</ion-note>
        </ion-item>

        <!-- Description -->
        <ion-item class="mb-4">
          <ion-label position="stacked">Description</ion-label>
          <ion-textarea
            v-model="formData.description"
            placeholder="Optional description"
            :auto-grow="true"
          ></ion-textarea>
        </ion-item>

        <!-- Department -->
        <ion-item class="mb-4">
          <ion-label position="stacked">Department</ion-label>
          <ion-select
            v-model="formData.department_id"
            placeholder="Global (all departments)"
            interface="popover"
          >
            <ion-select-option :value="null">Global (all departments)</ion-select-option>
            <ion-select-option
              v-for="dept in departments"
              :key="dept.id"
              :value="dept.id"
            >
              {{ dept.name }}
            </ion-select-option>
          </ion-select>
          <ion-note>Leave blank for global category</ion-note>
        </ion-item>

        <!-- Default Priority -->
        <ion-item class="mb-4">
          <ion-label position="stacked">Default Priority</ion-label>
          <ion-select
            v-model="formData.priority_id"
            placeholder="Select default priority"
            interface="popover"
          >
            <ion-select-option :value="null">No default</ion-select-option>
            <ion-select-option
              v-for="priority in priorities"
              :key="priority.id"
              :value="priority.id"
            >
              {{ priority.name }}
            </ion-select-option>
          </ion-select>
        </ion-item>

        <!-- Color -->
        <ion-item class="mb-4">
          <ion-label position="stacked">Color</ion-label>
          <div class="flex items-center space-x-2 w-full mt-2">
            <input
              type="color"
              v-model="formData.color"
              class="h-10 w-20 rounded border"
            />
            <ion-input v-model="formData.color" placeholder="#6B7280"></ion-input>
          </div>
          <ion-note>Choose a color for this category</ion-note>
        </ion-item>

        <!-- Preset Colors -->
        <div class="mb-4">
          <div class="text-sm text-gray-600 mb-2">Quick Colors:</div>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="color in presetColors"
              :key="color"
              type="button"
              @click="formData.color = color"
              :style="{ backgroundColor: color }"
              class="w-8 h-8 rounded-full border-2 border-white shadow-sm hover:scale-110 transition"
              :class="formData.color === color ? 'ring-2 ring-purple-500' : ''"
            ></button>
          </div>
        </div>

        <!-- Icon (Optional) -->
        <ion-item class="mb-4">
          <ion-label position="stacked">Icon (Optional)</ion-label>
          <ion-input
            v-model="formData.icon"
            placeholder="document-text-outline"
          ></ion-input>
          <ion-note>Ionicons icon name (e.g., document-text-outline)</ion-note>
        </ion-item>

        <!-- Active Status -->
        <ion-item v-if="category" class="mb-4">
          <ion-label>Active</ion-label>
          <ion-toggle v-model="formData.is_active"></ion-toggle>
        </ion-item>

        <!-- Submit Button -->
        <div class="mt-6">
          <ion-button expand="block" type="submit" :disabled="saving">
            <ion-spinner v-if="saving" name="crescent" class="mr-2"></ion-spinner>
            {{ saving ? 'Saving...' : category ? 'Update Category' : 'Create Category' }}
          </ion-button>
        </div>
      </form>
    </ion-content>
  </ion-modal>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import {
  IonModal, IonHeader, IonToolbar, IonTitle, IonButtons, IonButton,
  IonContent, IonItem, IonLabel, IonInput, IonTextarea, IonSelect,
  IonSelectOption, IonToggle, IonNote, IonIcon, IonSpinner
} from '@ionic/vue'
import { closeOutline } from 'ionicons/icons'
import { useCategories, type Category, type CategoryFormData } from '@/composables/useCategories'

interface Props {
  isOpen: boolean
  category?: Category | null
  departments: Array<{ id: number; name: string; is_active: boolean }>
  priorities: Array<{ id: number; name: string }>
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  save: []
}>()

const { createCategory, updateCategory } = useCategories()

const formData = ref<CategoryFormData>({
  name: '',
  description: '',
  department_id: null,
  priority_id: null,
  icon: '',
  color: '#6B7280',
  is_active: true
})

const errors = ref<Record<string, string>>({})
const saving = ref(false)

const presetColors = [
  '#3B82F6', // blue
  '#10B981', // green
  '#F59E0B', // yellow
  '#EF4444', // red
  '#8B5CF6', // purple
  '#EC4899', // pink
  '#14B8A6', // teal
  '#F97316', // orange
  '#6B7280', // gray
  '#06B6D4', // cyan
]

// Watch for category changes to populate form
watch(() => props.category, (newCategory) => {
  if (newCategory) {
    formData.value = {
      name: newCategory.name,
      description: newCategory.description || '',
      department_id: newCategory.department_id || null,
      priority_id: newCategory.priority_id || null,
      icon: newCategory.icon || '',
      color: newCategory.color || '#6B7280',
      is_active: newCategory.is_active
    }
  } else {
    // Reset form for new category
    formData.value = {
      name: '',
      description: '',
      department_id: null,
      priority_id: null,
      icon: '',
      color: '#6B7280',
      is_active: true
    }
  }
  errors.value = {}
}, { immediate: true })

function validate(): boolean {
  errors.value = {}
  
  if (!formData.value.name?.trim()) {
    errors.value.name = 'Category name is required'
  }
  
  return Object.keys(errors.value).length === 0
}

async function handleSubmit() {
  if (!validate()) return
  
  saving.value = true
  try {
    if (props.category) {
      await updateCategory(props.category.id, formData.value)
    } else {
      await createCategory(formData.value)
    }
    emit('save')
  } catch (error: any) {
    // Handle validation errors from backend
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    }
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
ion-item {
  --background: transparent;
  --border-color: #e5e7eb;
}

ion-input,
ion-textarea,
ion-select {
  --background: white;
  --padding-start: 12px;
  --padding-end: 12px;
}
</style>
