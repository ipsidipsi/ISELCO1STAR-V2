<template>
  <ion-modal :is-open="isOpen" @did-dismiss="$emit('close')">
    <ion-header>
      <ion-toolbar>
        <ion-title>{{ priority ? 'Edit Priority' : 'New Priority' }}</ion-title>
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
            placeholder="e.g., Critical, High, Medium, Low"
            :class="errors.name ? 'ion-invalid ion-touched' : ''"
          ></ion-input>
          <ion-note v-if="errors.name" slot="error">{{ errors.name }}</ion-note>
        </ion-item>

        <!-- Level -->
        <ion-item class="mb-4">
          <ion-label position="stacked">Priority Level (1-5) <span class="text-red-500">*</span></ion-label>
          <ion-select
            v-model="formData.level"
            placeholder="Select level"
            interface="popover"
            :class="errors.level ? 'ion-invalid ion-touched' : ''"
          >
            <ion-select-option :value="5">5 - Critical</ion-select-option>
            <ion-select-option :value="4">4 - High</ion-select-option>
            <ion-select-option :value="3">3 - Medium</ion-select-option>
            <ion-select-option :value="2">2 - Low</ion-select-option>
            <ion-select-option :value="1">1 - Very Low</ion-select-option>
          </ion-select>
          <ion-note v-if="errors.level" slot="error">{{ errors.level }}</ion-note>
          <ion-note v-else>Higher number = higher priority</ion-note>
        </ion-item>

        <!-- SLA Hours -->
        <ion-item class="mb-4">
          <ion-label position="stacked">SLA Response Time (hours) <span class="text-red-500">*</span></ion-label>
          <ion-input
            v-model.number="formData.sla_hours"
            type="number"
            min="1"
            placeholder="e.g., 24"
            :class="errors.sla_hours ? 'ion-invalid ion-touched' : ''"
          ></ion-input>
          <ion-note v-if="errors.sla_hours" slot="error">{{ errors.sla_hours }}</ion-note>
          <ion-note v-else>Expected response time in hours</ion-note>
        </ion-item>

        <!-- SLA Examples -->
        <div class="mb-4 p-3 bg-blue-50 rounded-lg">
          <div class="text-xs text-blue-600 font-medium mb-2">Common SLA Times:</div>
          <div class="grid grid-cols-2 gap-2 text-sm text-blue-700">
            <button type="button" @click="formData.sla_hours = 1" class="text-left hover:underline">• Critical: 1-2 hours</button>
            <button type="button" @click="formData.sla_hours = 4" class="text-left hover:underline">• High: 4-8 hours</button>
            <button type="button" @click="formData.sla_hours = 24" class="text-left hover:underline">• Medium: 24 hours</button>
            <button type="button" @click="formData.sla_hours = 72" class="text-left hover:underline">• Low: 72 hours</button>
          </div>
        </div>

        <!-- Color -->
        <ion-item class="mb-4">
          <ion-label position="stacked">Color <span class="text-red-500">*</span></ion-label>
          <div class="flex items-center space-x-2 w-full mt-2">
            <input
              type="color"
              v-model="formData.color"
              class="h-10 w-20 rounded border"
            />
            <ion-input v-model="formData.color" placeholder="#EF4444"></ion-input>
          </div>
          <ion-note v-if="errors.color" slot="error">{{ errors.color }}</ion-note>
          <ion-note v-else>Choose a color for this priority</ion-note>
        </ion-item>

        <!-- Recommended Colors -->
        <div class="mb-4">
          <div class="text-sm text-gray-600 mb-2">Recommended Colors:</div>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="(colorOption, index) in recommendedColors"
              :key="index"
              type="button"
              @click="formData.color = colorOption.color"
              :style="{ backgroundColor: colorOption.color }"
              class="px-3 py-2 rounded text-white text-xs font-medium shadow-sm hover:scale-105 transition"
              :class="formData.color === colorOption.color ? 'ring-2 ring-purple-500' : ''"
            >
              {{ colorOption.label }}
            </button>
          </div>
        </div>

        <!-- Preview -->
        <div class="mb-4 p-4 bg-gray-50 rounded-lg">
          <div class="text-sm text-gray-600 mb-2">Preview:</div>
          <div class="flex items-center space-x-3">
            <div 
              :style="{ backgroundColor: formData.color || '#6B7280' }"
              class="w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold text-lg"
            >
              {{ formData.level || '?' }}
            </div>
            <div>
              <div class="font-medium text-navy-700">{{ formData.name || 'Priority Name' }}</div>
              <div class="text-sm text-gray-600">SLA: {{ formData.sla_hours || 0 }} hours</div>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="mt-6">
          <ion-button expand="block" type="submit" :disabled="saving">
            <ion-spinner v-if="saving" name="crescent" class="mr-2"></ion-spinner>
            {{ saving ? 'Saving...' : priority ? 'Update Priority' : 'Create Priority' }}
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
  IonContent, IonItem, IonLabel, IonInput, IonSelect, IonSelectOption,
  IonNote, IonIcon, IonSpinner
} from '@ionic/vue'
import { closeOutline } from 'ionicons/icons'
import { usePriorities, type Priority, type PriorityFormData } from '@/composables/usePriorities'

interface Props {
  isOpen: boolean
  priority?: Priority | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  save: []
}>()

const { createPriority, updatePriority } = usePriorities()

const formData = ref<PriorityFormData>({
  name: '',
  level: 3,
  color: '#F59E0B',
  sla_hours: 24
})

const errors = ref<Record<string, string>>({})
const saving = ref(false)

const recommendedColors = [
  { label: 'Critical', color: '#DC2626' },  // red-600
  { label: 'High', color: '#EA580C' },      // orange-600
  { label: 'Medium', color: '#F59E0B' },    // amber-500
  { label: 'Low', color: '#10B981' },       // green-500
  { label: 'Very Low', color: '#6B7280' },  // gray-500
]

// Watch for priority changes to populate form
watch(() => props.priority, (newPriority) => {
  if (newPriority) {
    formData.value = {
      name: newPriority.name,
      level: newPriority.level,
      color: newPriority.color,
      sla_hours: newPriority.sla_hours
    }
  } else {
    // Reset form for new priority
    formData.value = {
      name: '',
      level: 3,
      color: '#F59E0B',
      sla_hours: 24
    }
  }
  errors.value = {}
}, { immediate: true })

function validate(): boolean {
  errors.value = {}
  
  if (!formData.value.name?.trim()) {
    errors.value.name = 'Priority name is required'
  }
  
  if (!formData.value.level || formData.value.level < 1 || formData.value.level > 5) {
    errors.value.level = 'Level must be between 1 and 5'
  }
  
  if (!formData.value.sla_hours || formData.value.sla_hours < 1) {
    errors.value.sla_hours = 'SLA hours must be at least 1'
  }
  
  if (!formData.value.color) {
    errors.value.color = 'Color is required'
  }
  
  return Object.keys(errors.value).length === 0
}

async function handleSubmit() {
  if (!validate()) return
  
  saving.value = true
  try {
    if (props.priority) {
      await updatePriority(props.priority.id, formData.value)
    } else {
      await createPriority(formData.value)
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
ion-select {
  --background: white;
  --padding-start: 12px;
  --padding-end: 12px;
}
</style>
