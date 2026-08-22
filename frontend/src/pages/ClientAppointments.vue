<script setup>
import { onMounted, ref } from 'vue'

import AppConfirm from '../components/AppConfirm.vue'
import AppEmpty from '../components/AppEmpty.vue'
import AppError from '../components/AppError.vue'
import AppLoading from '../components/AppLoading.vue'
import { useAppointmentStore } from '../stores/appointments'
import { useNotificationStore } from '../stores/notifications'

const appointments = useAppointmentStore()
const notifications = useNotificationStore()
const appointmentToCancel = ref(null)

onMounted(() => appointments.fetchClientList())

function canCancel(appointment) {
  return ['Requested', 'Confirmed'].includes(appointment.status)
}

function formatDate(date) {
  return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(new Date(`${date}T00:00:00`))
}

async function goToPage(page) {
  await appointments.fetchClientList({ page })
}

async function cancelAppointment() {
  try {
    const updated = await appointments.cancelClient(appointmentToCancel.value.id)
    appointments.updateItem(updated)
    notifications.notify('Appointment cancelled.')
    appointmentToCancel.value = null
  } catch {
    // The store exposes the backend conflict message.
  }
}
</script>

<template>
  <section class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-400">Client workspace</p>
        <h1 class="mt-2 text-3xl font-semibold">My appointments</h1>
        <p class="mt-2 text-slate-400">Review your booking requests and confirmed visits.</p>
      </div>
      <RouterLink class="rounded-lg bg-cyan-400 px-4 py-2 text-sm font-semibold text-slate-950" to="/book">Book an appointment</RouterLink>
    </div>

    <AppLoading v-if="appointments.isLoading" message="Loading your appointments..." />
    <AppError v-else-if="appointments.error" :message="appointments.error" :retry="true" @retry="appointments.fetchClientList()" />
    <AppEmpty v-else-if="!appointments.items.length" message="You do not have any appointments yet.">
      <RouterLink class="mt-4 inline-block font-semibold text-cyan-400" to="/book">Request your first appointment</RouterLink>
    </AppEmpty>
    <div v-else class="space-y-4">
      <article v-for="appointment in appointments.items" :key="appointment.id" class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold">{{ appointment.service?.name || 'Appointment' }}</h2>
            <p class="mt-1 text-sm text-slate-400">{{ formatDate(appointment.appointmentDate) }} · {{ appointment.startTime }} - {{ appointment.endTime }}</p>
          </div>
          <span class="rounded-full bg-slate-800 px-3 py-1 text-xs font-semibold" :class="appointment.status === 'Cancelled' ? 'text-slate-500' : 'text-cyan-300'">{{ appointment.status }}</span>
        </div>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-4 border-t border-slate-800 pt-4 text-sm">
          <div class="text-slate-400">Priority: <span class="text-slate-200">{{ appointment.priority }}</span></div>
          <button v-if="canCancel(appointment)" class="rounded-lg border border-rose-800 px-3 py-2 font-semibold text-rose-300 hover:bg-rose-950/50" type="button" @click="appointmentToCancel = appointment">Cancel appointment</button>
        </div>
      </article>

      <div v-if="appointments.pagination?.last_page > 1" class="flex items-center justify-between text-sm text-slate-400">
        <button class="rounded-lg border border-slate-700 px-3 py-2 disabled:opacity-40" type="button" :disabled="appointments.pagination.current_page <= 1 || appointments.isLoading" @click="goToPage(appointments.pagination.current_page - 1)">Previous</button>
        <span>Page {{ appointments.pagination.current_page }} of {{ appointments.pagination.last_page }}</span>
        <button class="rounded-lg border border-slate-700 px-3 py-2 disabled:opacity-40" type="button" :disabled="appointments.pagination.current_page >= appointments.pagination.last_page || appointments.isLoading" @click="goToPage(appointments.pagination.current_page + 1)">Next</button>
      </div>
    </div>

    <AppConfirm :open="Boolean(appointmentToCancel)" title="Cancel appointment?" message="This booking request will be marked as cancelled and cannot be restored." confirm-label="Cancel appointment" :loading="appointments.isSaving" @cancel="appointmentToCancel = null" @confirm="cancelAppointment" />
  </section>
</template>
