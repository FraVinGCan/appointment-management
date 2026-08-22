<template>
  <div class="fixed right-4 top-4 z-40 flex w-[min(24rem,calc(100vw-2rem))] flex-col gap-3" aria-live="polite">
    <UAlert v-for="item in notifications.items" :key="item.id" :title="item.type === 'error' ? 'Request failed' : 'Success'" :description="item.message" :color="item.type === 'error' ? 'error' : 'success'" variant="soft" :close="true" @update:open="notifications.dismiss(item.id)" />
  </div>
</template>

<script setup>
import { useNotificationStore } from '../stores/notifications'

const notifications = useNotificationStore()
</script>
