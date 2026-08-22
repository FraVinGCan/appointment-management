<script setup>
import { computed, onMounted, reactive } from 'vue'
import { useRouter } from 'vue-router'

import AppError from '../components/AppError.vue'
import AppLoading from '../components/AppLoading.vue'
import { useAppointmentStore } from '../stores/appointments'
import { useNotificationStore } from '../stores/notifications'
import { useServiceStore } from '../stores/services'

const appointments = useAppointmentStore()
const services = useServiceStore()
const notifications = useNotificationStore()
const router = useRouter()
const form = reactive({
  serviceId: '',
  priority: 'Medium',
  appointmentDate: '',
  startTime: '',
  endTime: '',
  notes: '',
})
const today = computed(() => new Date().toISOString().slice(0, 10))

onMounted(() => services.fetchActive())

function fieldError(field) {
  return appointments.validationErrors[field]?.[0]
}

async function submit() {
  try {
    await appointments.createBooking({ ...form })
    notifications.notify('Booking request submitted successfully.')
    router.push({ name: 'client-appointments' })
  } catch {
    // The store exposes request and field errors to the template.
  }
}
</script>

<template>
  <section class="space-y-6">
    <div>
      <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-400">Client workspace</p>
      <h1 class="mt-2 text-3xl font-semibold">Book an appointment</h1>
      <p class="mt-2 text-slate-400">Choose a service and request a time that works for you.</p>
    </div>

    <AppLoading v-if="services.isLoading" message="Loading available services..." />
    <AppError v-else-if="services.error" :message="services.error" :retry="true" @retry="services.fetchActive()" />
    <form v-else class="max-w-2xl space-y-5 rounded-2xl border border-slate-800 bg-slate-900 p-6" @submit.prevent="submit">
      <label class="block text-sm font-medium">Service
        <select v-model="form.serviceId" required class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100 outline-none focus:border-cyan-400">
          <option disabled value="">Select a service</option>
          <option v-for="service in services.items" :key="service.id" :value="service.id">{{ service.name }} ({{ service.durationMinutes }} min)</option>
        </select>
        <span v-if="fieldError('serviceId')" class="mt-1 block text-sm text-rose-400">{{ fieldError('serviceId') }}</span>
      </label>

      <div class="grid gap-5 sm:grid-cols-2">
        <label class="block text-sm font-medium">Date
          <input v-model="form.appointmentDate" required type="date" :min="today" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100 outline-none focus:border-cyan-400" />
          <span v-if="fieldError('appointmentDate')" class="mt-1 block text-sm text-rose-400">{{ fieldError('appointmentDate') }}</span>
        </label>
        <label class="block text-sm font-medium">Priority
          <select v-model="form.priority" required class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100 outline-none focus:border-cyan-400">
            <option>Low</option><option>Medium</option><option>High</option>
          </select>
          <span v-if="fieldError('priority')" class="mt-1 block text-sm text-rose-400">{{ fieldError('priority') }}</span>
        </label>
        <label class="block text-sm font-medium">Start time
          <input v-model="form.startTime" required type="time" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100 outline-none focus:border-cyan-400" />
          <span v-if="fieldError('startTime')" class="mt-1 block text-sm text-rose-400">{{ fieldError('startTime') }}</span>
        </label>
        <label class="block text-sm font-medium">End time
          <input v-model="form.endTime" required type="time" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100 outline-none focus:border-cyan-400" />
          <span v-if="fieldError('endTime')" class="mt-1 block text-sm text-rose-400">{{ fieldError('endTime') }}</span>
        </label>
      </div>

      <label class="block text-sm font-medium">Notes <span class="font-normal text-slate-500">(optional)</span>
        <textarea v-model="form.notes" rows="4" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100 outline-none focus:border-cyan-400" />
        <span v-if="fieldError('notes')" class="mt-1 block text-sm text-rose-400">{{ fieldError('notes') }}</span>
      </label>

      <p v-if="appointments.error" class="rounded-lg bg-rose-950/60 px-3 py-2 text-sm text-rose-300">{{ appointments.error }}</p>
      <button class="w-full rounded-lg bg-cyan-400 px-4 py-3 font-semibold text-slate-950 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto" type="submit" :disabled="appointments.isSaving">{{ appointments.isSaving ? 'Submitting request...' : 'Submit booking request' }}</button>
    </form>
  </section>
</template>
