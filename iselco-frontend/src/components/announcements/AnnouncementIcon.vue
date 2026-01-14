<template>
  <div class="relative inline-block">
    <ion-button fill="clear" @click="showHistory = true" class="relative">
      <ion-icon :icon="megaphoneOutline" class="text-2xl text-gray-600 dark:text-gray-300"></ion-icon>
      <!-- Optional: Badge for new announcements -->
       <span v-if="unreadCount > 0" class="absolute top-2 right-2 flex h-3 w-3">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
      </span>
    </ion-button>

    <AnnouncementHistoryModal 
      :is-open="showHistory" 
      @close="showHistory = false" 
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { IonButton, IonIcon } from '@ionic/vue';
import { megaphoneOutline } from 'ionicons/icons';
import AnnouncementHistoryModal from './AnnouncementHistoryModal.vue';
import { useAnnouncementStore } from '@/stores/announcements';

const store = useAnnouncementStore();
const showHistory = ref(false);
const unreadCount = computed(() => store.unreadCount);

</script>
