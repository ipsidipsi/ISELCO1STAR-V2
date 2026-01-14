<template>
  <ion-modal :is-open="isOpen" @didDismiss="$emit('close')">
    <ion-header>
      <ion-toolbar>
        <ion-title>Create Broadcast</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="$emit('close')">Close</ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
        
      <div class="space-y-4">
        <!-- Title -->
        <ion-item>
          <ion-label position="stacked">Title *</ion-label>
          <ion-input v-model="form.title" placeholder="Announcement Title"></ion-input>
        </ion-item>

        <!-- Content -->
        <ion-item>
          <ion-label position="stacked">Message *</ion-label>
          <ion-textarea v-model="form.content" rows="4" placeholder="Write your announcement here..."></ion-textarea>
        </ion-item>

        <!-- Target Audience -->
        <ion-item>
            <ion-label position="stacked">Target Audience</ion-label>
            <ion-select v-model="form.type" placeholder="Select Target" @ionChange="handleTypeChange">
                <ion-select-option value="all" v-if="canBroadcastUniversal">All Users (Universal)</ion-select-option>
                <ion-select-option value="department">Specific Departments</ion-select-option>
                <!-- <ion-select-option value="selected_users">Specific Users</ion-select-option> -->
            </ion-select>
        </ion-item>

        <!-- Department Selector (Multi) -->
        <div v-if="form.type === 'department'" class="px-4">
             <p class="text-sm text-gray-500 mb-2">Select Departments to broadcast to:</p>
             <div class="border rounded-md p-2 max-h-40 overflow-y-auto">
                <div v-for="dept in accessibleDepartments" :key="dept.id" class="flex items-center py-1">
                    <ion-checkbox 
                        slot="start" 
                        :checked="form.department_ids.includes(dept.id)"
                        @ionChange="toggleDepartment(dept.id)"
                    ></ion-checkbox>
                    <span class="ml-2 text-sm">{{ dept.name }}</span>
                </div>
             </div>
        </div>

        <!-- Image Upload -->
        <div class="px-4 mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Attach Image (Optional)</label>
            <input type="file" @change="handleFileChange" accept="image/*" class="block w-full text-sm text-gray-500
              file:mr-4 file:py-2 file:px-4
              file:rounded-full file:border-0
              file:text-sm file:font-semibold
              file:bg-blue-50 file:text-blue-700
              hover:file:bg-blue-100
            "/>
            <p v-if="imagePreview" class="mt-2">
                <img :src="imagePreview" class="h-32 rounded-lg object-cover" />
            </p>
        </div>
        
        <!-- Expiry (Optional) -->
        <ion-item>
            <ion-label position="stacked">Expires At (Optional)</ion-label>
            <ion-datetime-button datetime="expiry"></ion-datetime-button>
            <ion-modal :keep-contents-mounted="true">
                <ion-datetime id="expiry" v-model="form.expires_at" presentation="date"></ion-datetime>
            </ion-modal>
        </ion-item>

        <div class="pt-4">
            <ion-button expand="block" @click="submit" :disabled="loading">
                <ion-spinner v-if="loading" name="crescent" slot="start"></ion-spinner>
                Broadcast Message
            </ion-button>
        </div>

      </div>
    </ion-content>
  </ion-modal>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { 
    IonModal, IonHeader, IonToolbar, IonTitle, IonButtons, IonButton, IonContent,
    IonItem, IonLabel, IonInput, IonTextarea, IonSelect, IonSelectOption,
    IonCheckbox, IonDatetime, IonDatetimeButton, IonSpinner, toastController
} from '@ionic/vue';
import { useAuthStore } from '@/stores/auth';
import { useAnnouncementStore } from '@/stores/announcements';
import api from '@/services/api';

const props = defineProps<{
  isOpen: boolean
}>();

const emit = defineEmits(['close', 'created']);

const authStore = useAuthStore();
const announcementStore = useAnnouncementStore();

const form = ref({
    title: '',
    content: '',
    type: 'department', // default
    department_ids: [] as number[],
    user_ids: [],
    expires_at: null,
    image: null as File | null
});

const accessibleDepartments = ref<any[]>([]);
const imagePreview = ref<string | null>(null);
const loading = ref(false);

const canBroadcastUniversal = computed(() => {
    return authStore.user?.roles?.some((r: any) => r.slug === 'superadmin') || 
           authStore.user?.permissions?.some((p: any) => p === 'broadcast.universal');
});

onMounted(async () => {
    await loadDepartments();
});

async function loadDepartments() {
    // We need to fetch departments the user can access.
    // Ideally user object has this, or we fetch from an endpoint.
    // For now, let's look at authStore.user.departments (assigned) + primary department
    // But backend logic says "getAccessibleDepartmentIds".
    // Let's use the Metadata endpoint but filter or just fetch all if superadmin.
    
    // Quick fix: Fetch all departments and filter if not superadmin? 
    // Or better: Let the backend provide "my-departments".
    // Since we don't have a specific endpoint, let's use what we have in AuthStore or fetch all.
    
    try {
        const response = await api.get('/departments');
        const allDepts = response.data;
        
        if (authStore.user?.roles?.some((r: any) => r.slug === 'superadmin')) {
            accessibleDepartments.value = allDepts;
        } else {
             // Filter based on user's authorized departments
             // This logic mimics the backend verify
             const myDeptIds = new Set<number>();
             if (authStore.user?.department_id) myDeptIds.add(authStore.user.department_id);
             
             authStore.user?.departments?.forEach((d: any) => myDeptIds.add(d.id));
             
             // Also temp departments? Metadata might be outdated.
             // Simplest: Just show all if Department Admin.
             if (authStore.user?.roles?.some((r: any) => r.slug === 'department_admin')) {
                  // Actually, department admin should only see their own.
                  accessibleDepartments.value = allDepts.filter((d: any) => myDeptIds.has(d.id));
             } else {
                  accessibleDepartments.value = allDepts.filter((d: any) => myDeptIds.has(d.id));
             }
        }
    } catch (e) {
        console.error('Failed to load departments', e);
    }
}

function handleTypeChange() {
    // Reset selections if switching types
}

function toggleDepartment(id: number) {
    const idx = form.value.department_ids.indexOf(id);
    if (idx === -1) {
        form.value.department_ids.push(id);
    } else {
        form.value.department_ids.splice(idx, 1);
    }
}

function handleFileChange(event: any) {
    const file = event.target.files[0];
    if (file) {
        form.value.image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
}

async function submit() {
    if (!form.value.title || !form.value.content) {
        const toast = await toastController.create({
            message: 'Please fill in required fields',
            duration: 2000,
            color: 'warning'
        });
        await toast.present();
        return;
    }

    if (form.value.type === 'department' && form.value.department_ids.length === 0) {
         const toast = await toastController.create({
            message: 'Please select at least one department',
            duration: 2000,
            color: 'warning'
        });
        await toast.present();
        return;
    }

    loading.value = true;
    try {
        const formData = new FormData();
        formData.append('title', form.value.title);
        formData.append('content', form.value.content);
        formData.append('type', form.value.type);
        if (form.value.expires_at) formData.append('expires_at', form.value.expires_at);
        if (form.value.image) formData.append('image', form.value.image);
        
        form.value.department_ids.forEach(id => formData.append('department_ids[]', id.toString()));

        await announcementStore.createAnnouncement(formData);

        const toast = await toastController.create({
            message: 'Announcement broadcasted successfully',
            duration: 2000,
            color: 'success'
        });
        await toast.present();
        emit('created');
        emit('close');
        
        // Reset form
        form.value = {
            title: '',
            content: '',
            type: 'department',
            department_ids: [],
            user_ids: [],
            expires_at: null,
            image: null
        };
        imagePreview.value = null;

    } catch (error: any) {
        const toast = await toastController.create({
            message: error.response?.data?.message || 'Failed to broadcast',
            duration: 3000,
            color: 'danger'
        });
        await toast.present();
    } finally {
        loading.value = false;
    }
}
</script>
