<template>
  <div class="timeline-list">
    <!-- Loading state -->
    <div v-if="loading" class="flex justify-center py-8">
      <ion-spinner name="crescent" class="text-teal"></ion-spinner>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="text-center py-8 text-red-600">
      <p>{{ error }}</p>
      <button @click="loadActivities" class="mt-2 text-teal hover:underline">
        Try again
      </button>
    </div>

    <!-- Timeline -->
    <div v-else-if="activities.length > 0" class="timeline">
      <TimelineItem
        v-for="(activity, index) in activities"
        :key="activity.id"
        :activity="activity"
        :icon="getActivityIcon(activity.activity_type)"
        :color="getActivityColor(activity.activity_type)"
        :relative-time="formatRelativeTime(activity.created_at)"
        :is-last="index === activities.length - 1"
      />
    </div>

    <!-- Empty state -->
    <div v-else class="text-center py-8 text-gray-500">
      <p>No activity yet</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { IonSpinner } from '@ionic/vue'
import { useTimeline } from '@/composables/useTimeline'
import TimelineItem from './TimelineItem.vue'

const props = defineProps<{
  ticketId: number
}>()

const {
  activities,
  loading,
  error,
  loadActivities,
  getActivityIcon,
  getActivityColor,
  formatRelativeTime
} = useTimeline(props.ticketId)

onMounted(() => {
  loadActivities()
})
</script>

<style scoped>
.timeline-list {
  width: 100%;
}

.timeline {
  padding: 1rem 0;
}
</style>
