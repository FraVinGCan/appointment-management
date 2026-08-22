<template>
  <div class="fixed right-4 top-4 z-40 flex w-[min(24rem,calc(100vw-2rem))] flex-col gap-3" aria-live="polite">
    <div v-for="item in notifications.items" :key="item.id" class="rounded-xl border px-4 py-3 text-sm shadow-xl" :class="item.type === 'error' ? 'border-rose-800 bg-rose-950 text-rose-100' : 'border-emerald-800 bg-emerald-950 text-emerald-100'">
      <div class="flex items-start justify-between gap-3">
        <span>{{ item.message }}</span>
        <button class="text-current opacity-70 hover:opacity-100" type="button" aria-label="Dismiss notification" @click="notifications.dismiss(item.id)">x</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useNotificationStore } from '../stores/notifications'

const notifications = useNotificationStore()
</script>
