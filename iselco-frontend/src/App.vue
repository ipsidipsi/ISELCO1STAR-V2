<template>
  <ion-app>
    <ion-router-outlet />
    <NotificationContainer />
  </ion-app>
</template>

<script setup lang="ts">
import { IonApp, IonRouterOutlet } from '@ionic/vue';
import { useNotificationStore } from '@/stores/notifications';
import { useAuthStore } from '@/stores/auth';
import { useThemeStore } from '@/stores/theme';
import { watch, onUnmounted, onMounted } from 'vue';

const notificationStore = useNotificationStore();
const authStore = useAuthStore();
const themeStore = useThemeStore();

onMounted(() => {
  themeStore.initTheme();
});

// Initialize notification listener when user is authenticated
watch(() => authStore.user, (user) => {
  if (user) {
    // User is logged in, initialize notifications
    notificationStore.initializeListener();
    notificationStore.fetchPreferences();
  } else {
    // User logged out, stop listener
    notificationStore.stopListener();
  }
}, { immediate: true });

// Cleanup on unmount
onUnmounted(() => {
  notificationStore.stopListener();
});
</script>
