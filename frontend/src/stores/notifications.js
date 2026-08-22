import { defineStore } from 'pinia'

let nextId = 0

export const useNotificationStore = defineStore('notifications', {
  state: () => ({ items: [] }),

  actions: {
    notify(message, type = 'success', duration = 4000) {
      const id = ++nextId
      this.items.push({ id, message, type })
      if (duration > 0) window.setTimeout(() => this.dismiss(id), duration)
    },
    dismiss(id) {
      this.items = this.items.filter((item) => item.id !== id)
    },
  },
})
