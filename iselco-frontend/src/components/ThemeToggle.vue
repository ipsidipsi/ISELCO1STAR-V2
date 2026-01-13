<template>
  <button 
    @click="themeStore.toggleTheme"
    class="p-2 rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-teal-500"
    :class="[
      themeStore.theme === 'dark' 
        ? 'bg-gray-700 text-yellow-400 hover:bg-gray-600' 
        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
    ]"
    title="Toggle Theme"
  >
    <ion-icon 
      :icon="themeStore.theme === 'dark' ? moon : sunny" 
      class="text-xl block"
    ></ion-icon>
  </button>
</template>

<script setup lang="ts">
import { IonIcon } from '@ionic/vue';
import { sunny, moon } from 'ionicons/icons';
import { useThemeStore } from '@/stores/theme';

const themeStore = useThemeStore();
</script>
