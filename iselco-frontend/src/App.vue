<template>
  <ion-app>
    <ion-router-outlet />
    <NotificationContainer />
  </ion-app>
</template>

<script setup lang="ts">
import { IonApp, IonRouterOutlet } from '@ionic/vue';
import NotificationContainer from '@/components/NotificationContainer.vue';
</script>
