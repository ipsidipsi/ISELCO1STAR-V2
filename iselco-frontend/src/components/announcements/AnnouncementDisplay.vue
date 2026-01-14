<template>
  <div v-if="announcements.length > 0" class="mb-6 space-y-3">
    <div 
      v-for="announcement in announcements" 
      :key="announcement.id"
      class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border-l-4 border-blue-500 overflow-hidden relative"
      :class="{ 'opacity-50': isMarkingRead === announcement.id }"
    >
      <!-- Close/Dismiss Button -->
      <button 
        @click="dismiss(announcement.id)" 
        class="absolute top-2 right-2 p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400"
      >
        <ion-icon :icon="closeOutline" class="text-xl"></ion-icon>
      </button>

      <div class="p-4">
        <!-- Header: Title, Badges, Close -->
        <div class="flex justify-between items-start mb-2 pr-6">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700" v-if="announcement.type === 'all'">Universal</span>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-orange-100 text-orange-700" v-else>Department</span>
                    
                    <span class="text-xs text-gray-500">
                        {{ new Date(announcement.created_at).toLocaleDateString() }}
                    </span>
                </div>
                <h3 class="font-bold text-lg text-gray-900 dark:text-white">{{ announcement.title }}</h3>
            </div>
        </div>

        <!-- Content -->
        <p class="text-gray-700 dark:text-gray-300 text-sm whitespace-pre-wrap mb-4">{{ announcement.content }}</p>

        <!-- Media/Attachment -->
        <div v-if="announcement.image_url" class="mb-3">
            <!-- Image (Facebook Style: Large, Full Width) -->
            <div 
                v-if="isImage(announcement.image_url)" 
                class="w-full rounded-lg overflow-hidden bg-gray-100 border border-gray-200 cursor-pointer"
                @click="viewImage(announcement.image_url)"
            >
                <img 
                    :src="getFullUrl(announcement.image_url)" 
                    class="w-full h-auto max-h-96 object-contain hover:opacity-95 transition-opacity" 
                >
            </div>
            
            <!-- Document/File (Attachment Style) -->
            <a 
                v-else 
                :href="getFullUrl(announcement.image_url)" 
                target="_blank"
                class="flex items-center p-3 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors group"
                download
            >
                <div class="w-10 h-10 flex items-center justify-center bg-blue-100 text-blue-600 rounded-lg mr-3 group-hover:bg-blue-200">
                    <ion-icon :icon="documentAttachOutline" class="text-xl"></ion-icon>
                </div>
                <div class="flex-1">
                     <p class="text-sm font-medium text-gray-900 dark:text-gray-800">Download Attachment</p>
                     <p class="text-xs text-gray-500 uppercase">{{ getExtension(announcement.image_url) }} FILE</p>
                </div>
                <ion-icon :icon="downloadOutline" class="text-gray-400"></ion-icon>
            </a>
        </div>

        <!-- Footer -->
        <div class="text-xs text-gray-400 border-t pt-2 mt-2">
            Posted by: <span class="font-medium text-gray-500">{{ announcement.creator?.employee_name || announcement.creator?.username || 'System' }}</span>
        </div>
      </div>
    </div>
    
    <!-- Image Modal -->
    <ion-modal :is-open="!!selectedImage" @didDismiss="selectedImage = null">
        <ion-header>
            <ion-toolbar>
                <ion-buttons slot="end">
                    <ion-button @click="selectedImage = null">Close</ion-button>
                </ion-buttons>
            </ion-toolbar>
        </ion-header>
        <div class="flex items-center justify-center h-full bg-black">
            <img :src="selectedImage" class="max-h-full max-w-full" v-if="selectedImage"/>
        </div>
    </ion-modal>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useAnnouncementStore } from '@/stores/announcements';
import { IonIcon, IonModal, IonHeader, IonToolbar, IonButtons, IonButton } from '@ionic/vue';
import { closeOutline, documentAttachOutline, downloadOutline } from 'ionicons/icons';

const store = useAnnouncementStore();

// Filter only unread announcements for display
// If user dismisses, we mark as read which removes it from this list
const announcements = computed(() => store.unreadAnnouncements);

const isMarkingRead = ref<number | null>(null);
const selectedImage = ref<string | null>(null);

onMounted(() => {
    store.fetchActive();
});

async function dismiss(id: number) {
    isMarkingRead.value = id;
    await store.markAsRead(id);
    isMarkingRead.value = null;
}

// Helper to ensure full URL
function getFullUrl(url: string) {
    if (!url) return '';
    if (url.startsWith('http')) return url;
    
    // Prepend API URL (remove /api suffix if present)
    const baseUrl = import.meta.env.VITE_API_URL || '';
    const cleanBase = baseUrl.endsWith('/api') ? baseUrl.slice(0, -4) : baseUrl;
    
    return `${cleanBase}${url.startsWith('/') ? '' : '/'}${url}`;
}

function viewImage(url: string) {
    selectedImage.value = getFullUrl(url);
}

function isImage(url: string) {
    if (!url) return false;
    const cleanUrl = url.split('?')[0];
    const extension = cleanUrl.split('.').pop()?.toLowerCase();
    return ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'].includes(extension || '');
}

function getExtension(url: string) {
    if (!url) return '';
    return url.split('.').pop()?.toLowerCase().substring(0, 4);
}
</script>
