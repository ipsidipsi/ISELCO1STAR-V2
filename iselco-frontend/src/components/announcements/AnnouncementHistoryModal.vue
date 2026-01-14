<template>
  <ion-modal :is-open="isOpen" @didDismiss="$emit('close')">
    <ion-header>
      <ion-toolbar>
        <ion-title>Announcement History</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="$emit('close')">Close</ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
      <ion-refresher slot="fixed" @ionRefresh="handleRefresh">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      <div v-if="loading" class="flex justify-center p-4">
        <ion-spinner name="crescent"></ion-spinner>
      </div>

      <div v-else-if="history.length === 0" class="text-center text-gray-500 mt-10">
        <p>No announcement history found.</p>
      </div>

      <ion-list v-else>
        <ion-item v-for="item in history" :key="item.id" class="mb-2">
          <ion-icon :icon="megaphoneOutline" slot="start" class="text-gray-400"></ion-icon>
          <ion-label>
            <h2 class="font-bold">{{ item.title }}</h2>
            <p class="text-sm text-gray-600 line-clamp-2">{{ item.content }}</p>
            <div class="flex gap-2 mt-1">
                <ion-badge color="tertiary" v-if="item.type === 'all'">All Users</ion-badge>
                <ion-badge color="warning" v-else-if="item.type === 'department'">Department</ion-badge>
                <ion-badge color="medium" v-else>Users</ion-badge>
                
                <span class="text-xs text-gray-500 self-center">
                    {{ new Date(item.created_at).toLocaleDateString() }}
                </span>
            </div>
            
            <div v-if="item.departments && item.departments.length" class="mt-1 flex flex-wrap gap-1">
                <span v-for="dept in item.departments" :key="dept.id" class="text-xs bg-gray-100 px-1 rounded">
                    {{ dept.name }}
                </span>
            </div>
          </ion-label>
        </ion-item>
      </ion-list>
    </ion-content>
  </ion-modal>
</template>

<script setup lang="ts">
import { onMounted, computed, ref, watch } from 'vue';
import { 
    IonModal, IonHeader, IonToolbar, IonTitle, IonButtons, IonButton, IonContent,
    IonList, IonItem, IonLabel, IonIcon, IonBadge, IonRefresher, IonRefresherContent,
    IonSpinner
} from '@ionic/vue';
import { megaphoneOutline } from 'ionicons/icons';
import { useAnnouncementStore } from '@/stores/announcements';

const props = defineProps<{ isOpen: boolean }>();
const store = useAnnouncementStore();

const history = computed(() => store.history);
const loading = computed(() => store.loading);

// watch isOpen to load data
watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        store.fetchHistory();
    }
});

async function handleRefresh(event: any) {
    await store.fetchHistory();
    event.target.complete();
}
</script>
