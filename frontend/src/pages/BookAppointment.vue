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
    <UForm v-else class="max-w-2xl space-y-5" :state="form" @submit="submit">
      <UCard variant="subtle"><div class="space-y-6">
      <UFormField label="Service" name="serviceId" required :error="fieldError('serviceId')"><USelect v-model="form.serviceId" placeholder="Select a service" value-key="value" :items="services.items.map((service) => ({ label: `${service.name} (${service.durationMinutes} min)`, value: String(service.id) }))" class="w-full" /></UFormField>

      <div class="grid gap-5 sm:grid-cols-2">
        <UFormField label="Date" name="appointmentDate" required :error="fieldError('appointmentDate')"><UInput v-model="form.appointmentDate" type="date" :min="today" class="w-full" /></UFormField>
        <UFormField label="Priority" name="priority" required :error="fieldError('priority')"><USelect v-model="form.priority" :items="['Low', 'Medium', 'High']" class="w-full" /></UFormField>
        <UFormField label="Start time" name="startTime" required :error="fieldError('startTime')"><UInput v-model="form.startTime" type="time" class="w-full" /></UFormField>
        <UFormField label="End time" name="endTime" required :error="fieldError('endTime')"><UInput v-model="form.endTime" type="time" class="w-full" /></UFormField>
      </div>

      <UFormField label="Notes" name="notes" hint="Optional" :error="fieldError('notes')"><UTextarea v-model="form.notes" :rows="4" class="w-full" /></UFormField>

      <UAlert v-if="appointments.error" color="error" variant="soft" :description="appointments.error" />
      <div class="flex flex-wrap gap-3"><UButton type="submit" :loading="appointments.isSaving">Submit booking request</UButton></div>
      </div>
      </UCard>
    </UForm>
  </section>
</template>
