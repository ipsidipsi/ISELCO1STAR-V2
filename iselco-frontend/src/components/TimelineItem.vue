<template>
  <div class="timeline-item">
    <!-- Vertical line connector -->
    <div class="timeline-line" v-if="!isLast"></div>
    
    <!-- Icon circle -->
    <div class="timeline-icon" :class="`bg-${color}-100 border-${color}-400`">
      <ion-icon :icon="icon" :class="`text-${color}-600`"></ion-icon>
    </div>

    <!-- Content -->
    <div class="timeline-content">
      <div class="timeline-header">
        <p class="timeline-description">{{ activity.description }}</p>
        <span class="timeline-time">{{ relativeTime }}</span>
      </div>

      <!-- Metadata details for specific activity types -->
      <div v-if="hasMetadata" class="timeline-metadata">
        <div v-if="activity.activity_type === 'status_changed' && activity.metadata" class="status-change-detail">
          <span class="status-badge old">{{ formatStatus(activity.metadata.old_status) }}</span>
          <ion-icon :icon="arrowForwardOutline" class="mx-1 text-gray-400"></ion-icon>
          <span class="status-badge new">{{ formatStatus(activity.metadata.new_status) }}</span>
        </div>
        
        <div v-if="activity.activity_type === 'resolved' && activity.metadata?.notes" class="notes-detail">
          <p class="text-sm text-gray-600 italic">{{ activity.metadata.notes }}</p>
        </div>
        
        <div v-if="activity.activity_type === 'reopened' && activity.metadata?.reason" class="notes-detail">
          <p class="text-sm text-red-600 italic">{{ activity.metadata.reason }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { IonIcon } from '@ionic/vue'
import { arrowForwardOutline } from 'ionicons/icons'
import type { Activity } from '@/composables/useTimeline'

const props = defineProps<{
  activity: Activity
  icon: string
  color: string
  relativeTime: string
  isLast: boolean
}>()

const hasMetadata = computed(() => {
  return (
    (props.activity.activity_type === 'status_changed' && props.activity.metadata) ||
    (props.activity.activity_type === 'resolved' && props.activity.metadata?.notes) ||
    (props.activity.activity_type === 'reopened' && props.activity.metadata?.reason)
  )
})

function formatStatus(status: string): string {
  return status.replace('_', ' ').split(' ').map(w => 
    w.charAt(0).toUpperCase() + w.slice(1)
  ).join(' ')
}
</script>

<style scoped>
.timeline-item {
  position: relative;
  display: flex;
  gap: 1rem;
  padding-bottom: 1.5rem;
}

.timeline-line {
  position: absolute;
  left: 1.25rem;
  top: 2.5rem;
  bottom: 0;
  width: 2px;
  background: #E5E7EB;
}

.timeline-icon {
  flex-shrink: 0;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  border: 3px solid;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  background: white;
  z-index: 1;
}

.timeline-content {
  flex: 1;
  padding-top: 0.25rem;
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  gap: 1rem;
  margin-bottom: 0.5rem;
}

.timeline-description {
  font-size: 0.9375rem;
  color: #1F2937;
  font-weight: 500;
  flex: 1;
}

.timeline-time {
  font-size: 0.8125rem;
  color: #6B7280;
  white-space: nowrap;
}

.timeline-metadata {
  margin-top: 0.5rem;
}

.status-change-detail {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.old {
  background: #F3F4F6;
  color: #6B7280;
}

.status-badge.new {
  background: #D1FAE5;
  color: #065F46;
}

.notes-detail {
  padding: 0.75rem;
  background: #F9FAFB;
  border-left: 3px solid #14B8A6;
  border-radius: 0.375rem;
}

/* Color variations */
.bg-blue-100 { background-color: #DBEAFE; }
.border-blue-400 { border-color: #60A5FA; }
.text-blue-600 { color: #2563EB; }

.bg-purple-100 { background-color: #E9D5FF; }
.border-purple-400 { border-color: #C084FC; }
.text-purple-600 { color: #9333EA; }

.bg-teal-100 { background-color: #CCFBF1; }
.border-teal-400 { border-color: #2DD4BF; }
.text-teal-600 { color: #0D9488; }

.bg-orange-100 { background-color: #FFEDD5; }
.border-orange-400 { border-color: #FB923C; }
.text-orange-600 { color: #EA580C; }

.bg-green-100 { background-color: #D1FAE5; }
.border-green-400 { border-color: #34D399; }
.text-green-600 { color: #059669; }

.bg-red-100 { background-color: #FEE2E2; }
.border-red-400 { border-color: #F87171; }
.text-red-600 { color: #DC2626; }

.bg-gray-100 { background-color: #F3F4F6; }
.border-gray-400 { border-color: #9CA3AF; }
.text-gray-600 { color: #4B5563; }

.bg-emerald-100 { background-color: #D1FAE5; }
.border-emerald-400 { border-color: #34D399; }
.text-emerald-600 { color: #059669; }
</style>
