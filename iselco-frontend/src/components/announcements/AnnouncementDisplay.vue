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

      <div class="p-4 flex gap-4">
        <!-- Optional Image -->
        <div v-if="announcement.image_url" class="flex-shrink-0">
            <img :src="announcement.image_url" class="w-16 h-16 object-cover rounded-lg cursor-pointer" @click="viewImage(announcement.image_url)">
        </div>

        <div class="flex-1 pr-6">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700" v-if="announcement.type === 'all'">Universal</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-orange-100 text-orange-700" v-else>Department</span>
                
                <span class="text-xs text-gray-500">
                    {{ new Date(announcement.created_at).toLocaleDateString() }}
                </span>
            </div>
            
            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-1">{{ announcement.title }}</h3>
            <p class="text-gray-600 dark:text-gray-300 text-sm whitespace-pre-wrap">{{ announcement.content }}</p>
            
            <p class="text-xs text-gray-400 mt-2">Posted by: {{ announcement.creator }}</p>
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
import { closeOutline } from 'ionicons/icons';

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

function viewImage(url: string) {
    selectedImage.value = url;
}
</script>
