<template>
  <Teleport to="body">
    <div class="notification-container">
      <CustomNotification
        v-for="notification in notifications"
        :key="notification.id"
        :visible="notification.visible"
        :type="notification.type"
        :variant="notification.variant"
        :title="notification.title"
        :message="notification.message"
        :position="notification.position"
        :timer="notification.timer"
        :show-close="notification.showClose"
        :show-cancel="notification.showCancel"
        :confirm-text="notification.confirmText"
        :cancel-text="notification.cancelText"
        :input-type="notification.inputType"
        :input-placeholder="notification.inputPlaceholder"
        @close="handleClose(notification.id)"
        @confirm="(value) => handleConfirm(notification.id, value)"
        @cancel="handleCancel(notification.id)"
      />
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { useCustomNotification } from '@/composables/useCustomNotification'
import CustomNotification from './CustomNotification.vue'

const {
  notifications,
  handleClose,
  handleConfirm,
  handleCancel,
} = useCustomNotification()
</script>

<style scoped>
.notification-container {
  position: relative;
  z-index: 10000;
}
</style>
