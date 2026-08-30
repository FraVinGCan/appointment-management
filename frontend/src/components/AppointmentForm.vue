<script setup>
import { CalendarDate, Time } from "@internationalized/date";
import { computed, onMounted, reactive, ref } from "vue";
import { useRoute } from "vue-router";

import AppDateTimePicker from "./AppDateTimePicker.vue";
import AppError from "./AppError.vue";
import AppLoading from "./AppLoading.vue";
import { useAppointmentStore } from "../stores/appointments";
import { useClientStore } from "../stores/clients";
import { useServiceStore } from "../stores/services";
import * as clientService from "../services/clientService";
import * as serviceApi from "../services/serviceService";
import { useDebouncedWatch } from "../composables/useDebouncedWatch";

const props = defineProps({ appointment: { type: Object, default: null } });
const emit = defineEmits(["saved"]);
const route = useRoute();
const appointments = useAppointmentStore();
const clients = useClientStore();
const services = useServiceStore();
const clientSearchTerm = ref("");
const serviceSearchTerm = ref("");
const form = reactive({
  clientId: "",
  serviceId: "",
  priority: "Medium",
  appointmentDate: null,
  startTime: null,
  endTime: null,
  notes: "",
});
const isEditing = computed(() => Boolean(props.appointment));
const availableServices = computed(() =>
  services.items.filter(
    (service) => service.active || service.id === Number(form.serviceId),
  ),
);
const appointmentDateTime = computed({
  get: () => ({
    date: form.appointmentDate,
    startTime: form.startTime,
    endTime: form.endTime,
  }),
  set: (value) => {
    form.appointmentDate = value?.date || null;
    form.startTime = value?.startTime || null;
    form.endTime = value?.endTime || null;
  },
});

onMounted(async () => {
  await Promise.all([
     clients.fetchList({ per_page: 10 }),
     services.fetchAll({ per_page: 10 }),
  ]);
  if (props.appointment) {
    const [year, month, day] = props.appointment.appointmentDate
      .split("-")
      .map(Number);
    const toTime = (value) => {
      const [hour, minute] = value.split(":").map(Number);
      return new Time(hour, minute);
    };
    Object.assign(form, {
      clientId: String(props.appointment.clientId),
      serviceId: String(props.appointment.serviceId),
      priority: props.appointment.priority,
      appointmentDate: new CalendarDate(year, month, day),
      startTime: toTime(props.appointment.startTime),
      endTime: toTime(props.appointment.endTime),
       notes: props.appointment.notes || "",
      });
    return;
  }

  const clientId = String(route.query.clientId || "");
  const serviceId = String(route.query.serviceId || "");
  await Promise.all([
    addSelectedOption(clientId, clients.items, clientService.get),
    addSelectedOption(serviceId, services.items, serviceApi.get),
  ]);
  Object.assign(form, { clientId, serviceId });
});

async function addSelectedOption(id, options, fetchOption) {
  if (!id || options.some((option) => String(option.id) === id)) return;

  const option = await fetchOption(id);
  if (option) options.push(option);
}

useDebouncedWatch(clientSearchTerm, (value) =>
  clients.fetchList({ search: value.trim(), per_page: 10 }),
);
useDebouncedWatch(serviceSearchTerm, (value) =>
  services.fetchAll(value.trim() ? { search: value.trim(), per_page: 10 } : { per_page: 10 }),
);

function fieldError(field) {
  return appointments.validationErrors[field]?.[0];
}
const dateTimeError = computed(
  () =>
    fieldError("appointmentDate") ||
    fieldError("startTime") ||
    fieldError("endTime"),
);
function formatTime(time) {
  return `${String(time.hour).padStart(2, "0")}:${String(time.minute).padStart(2, "0")}`;
}
async function submit() {
  const payload = {
    ...form,
    clientId: Number(form.clientId),
    serviceId: Number(form.serviceId),
    appointmentDate: form.appointmentDate?.toString() || "",
    startTime: form.startTime ? formatTime(form.startTime) : "",
    endTime: form.endTime ? formatTime(form.endTime) : "",
  };
  try {
    emit(
      "saved",
      await (isEditing.value
        ? appointments.update(props.appointment.id, payload)
        : appointments.create(payload)),
    );
  } catch {
    /* Store state is rendered below. */
  }
}
</script>

<template>
  <AppLoading
    v-if="clients.isLoading || services.isLoading"
    message="Loading form options..."
  />
  <AppError
    v-else-if="clients.error || services.error"
    :message="clients.error || services.error"
  />
  <UForm v-else class="max-w-3xl" :state="form" @submit="submit">
    <UCard variant="subtle">
      <div class="space-y-6">
        <div class="grid gap-5 sm:grid-cols-2">
          <UFormField
            label="Client"
            name="clientId"
            required
            :error="fieldError('clientId')"
            ><USelectMenu
              v-model="form.clientId"
              v-model:search-term="clientSearchTerm"
              value-key="value"
              :items="
                clients.items.map((client) => ({
                  label: `${client.name}${client.active === false ? ' (inactive)' : ''}`,
                  value: String(client.id),
                }))
              "
               placeholder="Select a client"
               ignore-filter
               :search-input="{ placeholder: 'Search clients...', variant: 'none' }"
               clear
               class="w-full"
          /></UFormField>
          <UFormField
            label="Service"
            name="serviceId"
            required
            :error="fieldError('serviceId')"
            ><USelectMenu
              v-model="form.serviceId"
              v-model:search-term="serviceSearchTerm"
              value-key="value"
              :items="
                availableServices.map((service) => ({
                  label: `${service.name}${service.active ? '' : ' (inactive)'}`,
                  value: String(service.id),
                }))
              "
               placeholder="Select a service"
               ignore-filter
               :search-input="{ placeholder: 'Search services...', variant: 'none' }"
               clear
               class="w-full"
          /></UFormField>
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
          <UFormField
            label="Priority"
            name="priority"
            required
            :error="fieldError('priority')"
            ><USelectMenu
              v-model="form.priority"
              :items="['Low', 'Medium', 'High']"
              clear
              class="w-full" /></UFormField
          ><UFormField
            label="Date & time"
            name="appointmentDateTime"
            required
            :error="dateTimeError"
          ><AppDateTimePicker v-model="appointmentDateTime" /></UFormField>
        </div>
        <UFormField
          label="Notes"
          name="notes"
          hint="Optional"
          :error="fieldError('notes')"
          ><UTextarea v-model="form.notes" :rows="4" class="w-full"
        /></UFormField>
        <UAlert
          v-if="appointments.error"
          color="error"
          variant="soft"
          :description="appointments.error"
        />
        <div class="flex flex-wrap gap-3">
          <UButton type="submit" :loading="appointments.isSaving">{{
            isEditing ? "Save changes" : "Create appointment"
          }}</UButton>
        </div>
      </div>
    </UCard>
  </UForm>
</template>
