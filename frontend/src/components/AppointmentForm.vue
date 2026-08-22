<script setup>
import { computed, onMounted, reactive } from 'vue'

import AppError from './AppError.vue'
import AppLoading from './AppLoading.vue'
import { useAppointmentStore } from '../stores/appointments'
import { useClientStore } from '../stores/clients'
import { useServiceStore } from '../stores/services'

const props = defineProps({ appointment: { type: Object, default: null } })
const emit = defineEmits(['saved'])
const appointments = useAppointmentStore()
const clients = useClientStore()
const services = useServiceStore()
const form = reactive({ clientId: '', serviceId: '', priority: 'Medium', appointmentDate: '', startTime: '', endTime: '', notes: '' })
const isEditing = computed(() => Boolean(props.appointment))
const availableServices = computed(() => services.items.filter((service) => service.active || service.id === Number(form.serviceId)))

onMounted(async () => {
  await Promise.all([clients.fetchList({ per_page: 100 }), services.fetchAll()])
  if (props.appointment) Object.assign(form, { clientId: String(props.appointment.clientId), serviceId: String(props.appointment.serviceId), priority: props.appointment.priority, appointmentDate: props.appointment.appointmentDate, startTime: props.appointment.startTime, endTime: props.appointment.endTime, notes: props.appointment.notes || '' })
})

function fieldError(field) { return appointments.validationErrors[field]?.[0] }
async function submit() {
  const payload = { ...form, clientId: Number(form.clientId), serviceId: Number(form.serviceId) }
  try { emit('saved', await (isEditing.value ? appointments.update(props.appointment.id, payload) : appointments.create(payload))) } catch { /* Store state is rendered below. */ }
}
</script>

<template>
  <AppLoading v-if="clients.isLoading || services.isLoading" message="Loading form options..." />
  <AppError v-else-if="clients.error || services.error" :message="clients.error || services.error" />
  <UForm v-else class="max-w-3xl" :state="form" @submit="submit">
    <UCard variant="subtle">
      <div class="space-y-6">
      <div class="grid gap-5 sm:grid-cols-2">
        <UFormField label="Client" name="clientId" required :error="fieldError('clientId')"><USelectMenu v-model="form.clientId" value-key="value" :items="clients.items.map((client) => ({ label: `${client.name}${client.active === false ? ' (inactive)' : ''}`, value: String(client.id) }))" placeholder="Select a client" class="w-full" /></UFormField>
        <UFormField label="Service" name="serviceId" required :error="fieldError('serviceId')"><USelectMenu v-model="form.serviceId" value-key="value" :items="availableServices.map((service) => ({ label: `${service.name}${service.active ? '' : ' (inactive)'}`, value: String(service.id) }))" placeholder="Select a service" class="w-full" /></UFormField>
      </div>
      <div class="grid gap-5 sm:grid-cols-2"><UFormField label="Priority" name="priority" required :error="fieldError('priority')"><USelect v-model="form.priority" :items="['Low', 'Medium', 'High']" class="w-full" /></UFormField><UFormField label="Date" name="appointmentDate" required :error="fieldError('appointmentDate')"><UInput v-model="form.appointmentDate" type="date" class="w-full" /></UFormField><UFormField label="Start time" name="startTime" required :error="fieldError('startTime')"><UInput v-model="form.startTime" type="time" class="w-full" /></UFormField><UFormField label="End time" name="endTime" required :error="fieldError('endTime')"><UInput v-model="form.endTime" type="time" class="w-full" /></UFormField></div>
      <UFormField label="Notes" name="notes" hint="Optional" :error="fieldError('notes')"><UTextarea v-model="form.notes" :rows="4" class="w-full" /></UFormField>
      <UAlert v-if="appointments.error" color="error" variant="soft" :description="appointments.error" />
      <div class="flex flex-wrap gap-3"><UButton type="submit" :loading="appointments.isSaving">{{ isEditing ? 'Save changes' : 'Create appointment' }}</UButton></div>
      </div>
    </UCard>
  </UForm>
</template>
