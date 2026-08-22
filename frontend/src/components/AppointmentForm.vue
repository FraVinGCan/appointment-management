<script setup>
import { computed, onMounted, reactive } from "vue";

import AppError from "./AppError.vue";
import AppLoading from "./AppLoading.vue";
import { useAppointmentStore } from "../stores/appointments";
import { useClientStore } from "../stores/clients";
import { useServiceStore } from "../stores/services";

const props = defineProps({ appointment: { type: Object, default: null } });
const emit = defineEmits(["saved"]);
const appointments = useAppointmentStore();
const clients = useClientStore();
const services = useServiceStore();
const form = reactive({
  clientId: "",
  serviceId: "",
  priority: "Medium",
  appointmentDate: "",
  startTime: "",
  endTime: "",
  notes: "",
});
const isEditing = computed(() => Boolean(props.appointment));
const availableServices = computed(() =>
  services.items.filter(
    (service) => service.active || service.id === Number(form.serviceId),
  ),
);

onMounted(async () => {
  await Promise.all([
    clients.fetchList({ per_page: 100 }),
    services.fetchAll(),
  ]);
  if (props.appointment)
    Object.assign(form, {
      clientId: String(props.appointment.clientId),
      serviceId: String(props.appointment.serviceId),
      priority: props.appointment.priority,
      appointmentDate: props.appointment.appointmentDate,
      startTime: props.appointment.startTime,
      endTime: props.appointment.endTime,
      notes: props.appointment.notes || "",
    });
});

function fieldError(field) {
  return appointments.validationErrors[field]?.[0];
}
async function submit() {
  const payload = {
    ...form,
    clientId: Number(form.clientId),
    serviceId: Number(form.serviceId),
  };
  try {
    const saved = isEditing.value
      ? await appointments.update(props.appointment.id, payload)
      : await appointments.create(payload);
    emit("saved", saved);
  } catch {
    /* Store state is rendered below. */
  }
}
</script>

<template>
  <div>
    <AppLoading
      v-if="clients.isLoading || services.isLoading"
      message="Loading form options..." />
    <AppError
      v-else-if="clients.error || services.error"
      :message="clients.error || services.error" />
    <form
      v-else
      class="max-w-3xl space-y-5 rounded-2xl border border-slate-800 bg-slate-900 p-6"
      @submit.prevent="submit">
      <div class="grid gap-5 sm:grid-cols-2">
        <label class="block text-sm font-medium"
          >Client<select
            v-model="form.clientId"
            required
            class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2">
            <option disabled value="">Select a client</option>
            <option
              v-for="client in clients.items"
              :key="client.id"
              :value="client.id">
              {{ client.name
              }}{{ client.active === false ? " (inactive)" : "" }}
            </option></select
          ><span
            v-if="fieldError('clientId')"
            class="mt-1 block text-sm text-rose-400"
            >{{ fieldError("clientId") }}</span
          ></label
        >
        <label class="block text-sm font-medium"
          >Service<select
            v-model="form.serviceId"
            required
            class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2">
            <option disabled value="">Select a service</option>
            <option
              v-for="service in availableServices"
              :key="service.id"
              :value="service.id">
              {{ service.name }}{{ service.active ? "" : " (inactive)" }}
            </option></select
          ><span
            v-if="fieldError('serviceId')"
            class="mt-1 block text-sm text-rose-400"
            >{{ fieldError("serviceId") }}</span
          ></label
        >
      </div>
      <div class="grid gap-5 sm:grid-cols-2">
        <label class="block text-sm font-medium"
          >Priority<select
            v-model="form.priority"
            required
            class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2">
            <option>Low</option>
            <option>Medium</option>
            <option>High</option></select
          ><span
            v-if="fieldError('priority')"
            class="mt-1 block text-sm text-rose-400"
            >{{ fieldError("priority") }}</span
          ></label
        ><label class="block text-sm font-medium"
          >Date<input
            v-model="form.appointmentDate"
            required
            type="date"
            class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2" /><span
            v-if="fieldError('appointmentDate')"
            class="mt-1 block text-sm text-rose-400"
            >{{ fieldError("appointmentDate") }}</span
          ></label
        ><label class="block text-sm font-medium"
          >Start time<input
            v-model="form.startTime"
            required
            type="time"
            class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2" /><span
            v-if="fieldError('startTime')"
            class="mt-1 block text-sm text-rose-400"
            >{{ fieldError("startTime") }}</span
          ></label
        ><label class="block text-sm font-medium"
          >End time<input
            v-model="form.endTime"
            required
            type="time"
            class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2" /><span
            v-if="fieldError('endTime')"
            class="mt-1 block text-sm text-rose-400"
            >{{ fieldError("endTime") }}</span
          ></label
        >
      </div>
      <label class="block text-sm font-medium"
        >Notes<textarea
          v-model="form.notes"
          rows="4"
          class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2" />
      </label>
      <p
        v-if="appointments.error"
        class="rounded-lg bg-rose-950/60 px-3 py-2 text-sm text-rose-300">
        {{ appointments.error }}
      </p>
      <button
        class="rounded-lg bg-cyan-400 px-4 py-3 font-semibold text-slate-950 disabled:opacity-50"
        type="submit"
        :disabled="appointments.isSaving">
        {{
          appointments.isSaving
            ? "Saving..."
            : isEditing
              ? "Save changes"
              : "Create appointment"
        }}
      </button>
    </form>
  </div>
</template>
