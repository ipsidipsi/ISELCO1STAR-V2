<template>
  <div class="p-4 space-y-4">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Notification Settings</h3>
    
    <ion-list>
      <!-- Web Push (Android/Desktop) -->
      <ion-item v-if="webPush.isSupported">
        <ion-label>
          <h3>Enable Web Push</h3>
          <p class="text-sm text-gray-500">Receive notifications on Android & Desktop browsers</p>
        </ion-label>
        <ion-toggle 
          :model-value="preferences.web_push_enabled"
          @ion-change="handleWebPushToggle"
        />
      </ion-item>
      
      <!-- Browser Notifications -->
      <ion-item>
        <ion-label>
          <h3>Browser Notifications</h3>
          <p class="text-sm text-gray-500">Show toast notifications while browsing</p>
        </ion-label>
        <ion-toggle 
          :model-value="preferences.browser_enabled"
          @ion-change="(e) => savePreferences({ browser_enabled: e.detail.checked })"
        />
      </ion-item>
      
      <!-- Notification Sounds -->
      <ion-item>
        <ion-label>
          <h3>Notification Sounds</h3>
          <p class="text-sm text-gray-500">Play sound for new notifications</p>
        </ion-label>
        <ion-toggle 
          :model-value="preferences.sound_enabled"
          @ion-change="(e) => savePreferences({ sound_enabled: e.detail.checked })"
        />
      </ion-item>
      
      <!-- Mute All -->
      <ion-item>
        <ion-label>
          <h3>Mute All Notifications</h3>
          <p class="text-sm text-gray-500">Disable all notification alerts</p>
        </ion-label>
        <ion-toggle 
          :model-value="preferences.is_muted"
          @ion-change="(e) => savePreferences({ is_muted: e.detail.checked })"
          color="danger"
        />
      </ion-item>
    </ion-list>
    
    <ion-button @click="testNotification" expand="block">
      <ion-icon slot="start" :icon="notificationsOutline"></ion-icon>
      Test Notification
    </ion-button>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { IonList, IonItem, IonLabel, IonToggle, IonButton, IonIcon } from '@ionic/vue'
import { notificationsOutline } from 'ionicons/icons'
import { useNotificationStore } from '@/stores/notifications'
import { useWebPush } from '@/composables/useWebPush'
import { storeToRefs } from 'pinia'
import Swal from 'sweetalert2'

const notifStore = useNotificationStore()
const { preferences } = storeToRefs(notifStore)
const webPush = useWebPush()

onMounted(async () => {
  await notifStore.fetchPreferences()
  await webPush.checkSubscription()
})

async function handleWebPushToggle(event: any) {
  const enabled = event.detail.checked
  
  if (enabled) {
    // Request permission first
    const granted = await webPush.requestPermission()
    
    if (granted) {
      // Subscribe to Web Push
      const success = await webPush.subscribe()
      
      if (success) {
        await savePreferences({ web_push_enabled: true })
      } else {
        Swal.fire('Failed', 'Could not subscribe to Web Push', 'error')
      }
    } else {
      Swal.fire('Permission Denied', 'Please enable notifications in your browser settings', 'warning')
    }
  } else {
    // Unsubscribe
    await webPush.unsubscribe()
    await savePreferences({ web_push_enabled: false })
  }
}

async function savePreferences(updates: any) {
  await notifStore.updatePreferences(updates)
}

async function testNotification() {
  const testNotif = {
    id: 'test-' + Date.now(),
    type: 'App\\Notifications\\TicketUpdated',
    notifiable_type: 'App\\Models\\User',
    notifiable_id: 0,
    data: {
      ticket_id: 1,
      ticket_number: 'TEST-001',
      ticket_title: 'Test Notification',
      actor_id: 0,
      actor_name: 'System',
      action_type: 'test',
      message: 'This is a test notification'
    },
    read_at: null,
    created_at: new Date().toISOString()
  }
  
  notifStore.handleRealTimeNotification(testNotif)
  
  Swal.fire({
    title: 'Test Notification Sent!',
    text: 'Check your notification panel',
    icon: 'success',
    timer: 2000,
    showConfirmButton: false
  })
}
</script>
