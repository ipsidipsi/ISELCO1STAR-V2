<template>
  <div>
    <!-- Trigger Button -->
    <ion-item button @click="openModal" :detail="false">
      <ion-label position="stacked">{{ label }}</ion-label>
      <div class="selected-value">
        <span v-if="selectedDepartments.length === 0" class="placeholder">{{ placeholder }}</span>
        <span v-else class="selected-count">
          {{ selectedDepartments.length }} department(s) selected
        </span>
      </div>
    </ion-item>

    <!-- Selection Modal -->
    <ion-modal :is-open="isOpen" @didDismiss="closeModal">
      <ion-header>
        <ion-toolbar>
          <ion-title>{{ label }}</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="closeModal">Done</ion-button>
          </ion-buttons>
        </ion-toolbar>
        
        <!-- Search Bar -->
        <ion-toolbar>
          <ion-searchbar
            v-model="searchQuery"
            placeholder="Search departments..."
            @ionInput="handleSearch"
          ></ion-searchbar>
        </ion-toolbar>
      </ion-header>

      <ion-content>
        <ion-list>
          <!-- Select All Option -->
          <ion-item>
            <ion-checkbox
              slot="start"
              :checked="allSelected"
              @ionChange="toggleAll"
            ></ion-checkbox>
            <ion-label>
              <strong>Select All ({{ filteredDepartments.length }})</strong>
            </ion-label>
          </ion-item>

          <ion-item-divider></ion-item-divider>

          <!-- Department List -->
          <ion-item v-for="dept in filteredDepartments" :key="dept.id">
            <ion-checkbox
              slot="start"
              :checked="isSelected(dept.id)"
              @ionChange="toggleDepartment(dept.id)"
            ></ion-checkbox>
            <ion-label>{{ dept.name }}</ion-label>
          </ion-item>

          <!-- No Results -->
          <ion-item v-if="filteredDepartments.length === 0">
            <ion-label class="ion-text-center">
              <p>No departments found</p>
            </ion-label>
          </ion-item>
        </ion-list>
      </ion-content>

      <ion-footer>
        <ion-toolbar>
          <ion-note slot="start" class="ion-padding-start">
            {{ selectedDepartments.length }} selected
          </ion-note>
          <ion-buttons slot="end">
            <ion-button @click="clearAll" fill="clear" color="danger">
              Clear All
            </ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-footer>
    </ion-modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  IonModal,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonButtons,
  IonButton,
  IonList,
  IonItem,
  IonLabel,
  IonCheckbox,
  IonSearchbar,
  IonItemDivider,
  IonFooter,
  IonNote,
} from '@ionic/vue'

interface Department {
  id: number
  name: string
  code?: string
  is_active?: boolean
}

const props = defineProps<{
  modelValue: number[]
  departments: Department[]
  label?: string
  placeholder?: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: number[]): void
}>()

const isOpen = ref(false)
const searchQuery = ref('')
const selectedDepartments = ref<number[]>([...props.modelValue])

// Computed
const filteredDepartments = computed(() => {
  if (!searchQuery.value) {
    return props.departments
  }
  const query = searchQuery.value.toLowerCase()
  return props.departments.filter(dept =>
    dept.name.toLowerCase().includes(query) ||
    dept.code?.toLowerCase().includes(query)
  )
})

const allSelected = computed(() => {
  return filteredDepartments.value.length > 0 &&
    filteredDepartments.value.every(dept => selectedDepartments.value.includes(dept.id))
})

// Methods
function openModal() {
  selectedDepartments.value = [...props.modelValue]
  searchQuery.value = ''
  isOpen.value = true
}

function closeModal() {
  emit('update:modelValue', selectedDepartments.value)
  isOpen.value = false
}

function isSelected(deptId: number) {
  return selectedDepartments.value.includes(deptId)
}

function toggleDepartment(deptId: number) {
  const index = selectedDepartments.value.indexOf(deptId)
  if (index > -1) {
    selectedDepartments.value.splice(index, 1)
  } else {
    selectedDepartments.value.push(deptId)
  }
}

function toggleAll() {
  if (allSelected.value) {
    // Unselect all filtered departments
    filteredDepartments.value.forEach(dept => {
      const index = selectedDepartments.value.indexOf(dept.id)
      if (index > -1) {
        selectedDepartments.value.splice(index, 1)
      }
    })
  } else {
    // Select all filtered departments
    filteredDepartments.value.forEach(dept => {
      if (!selectedDepartments.value.includes(dept.id)) {
        selectedDepartments.value.push(dept.id)
      }
    })
  }
}

function clearAll() {
  selectedDepartments.value = []
}

function handleSearch() {
  // Search is reactive through computed property
}
</script>

<style scoped>
.selected-value {
  padding: 12px 0;
  width: 100%;
}

.placeholder {
  color: var(--ion-color-medium);
  font-size: 0.875rem;
}

.selected-count {
  color: var(--ion-color-primary);
  font-weight: 500;
  font-size: 0.875rem;
}
</style>
