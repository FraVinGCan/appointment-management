<script setup>
import { reactive } from "vue";
import { useServiceStore } from "../stores/services";
const props = defineProps({ service: { type: Object, default: null } });
const emit = defineEmits(["saved"]);
const services = useServiceStore();
const form = reactive({
  name: props.service?.name || "",
  description: props.service?.description || "",
  durationMinutes: props.service?.durationMinutes || 30,
  active: props.service?.active ?? true,
});
function error(field) {
  return services.validationErrors[field]?.[0];
}
async function submit() {
  try {
    emit(
      "saved",
      await (props.service
        ? services.update(props.service.id, {
            ...form,
            durationMinutes: Number(form.durationMinutes),
          })
        : services.create({
            ...form,
            durationMinutes: Number(form.durationMinutes),
          })),
    );
  } catch {
    /* Store state is rendered below. */
  }
}
</script>
<template>
  <form
    class="max-w-2xl space-y-5 rounded-2xl border border-slate-800 bg-slate-900 p-6"
    @submit.prevent="submit">
    <label class="block text-sm font-medium"
      >Name<input
        v-model="form.name"
        required
        class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2" /><span
        v-if="error('name')"
        class="mt-1 block text-sm text-rose-400"
        >{{ error("name") }}</span
      ></label
    ><label class="block text-sm font-medium"
      >Description<textarea
        v-model="form.description"
        rows="4"
        class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2" /><span
        v-if="error('description')"
        class="mt-1 block text-sm text-rose-400"
        >{{ error("description") }}</span
      ></label
    ><label class="block text-sm font-medium"
      >Duration in minutes<input
        v-model="form.durationMinutes"
        required
        min="1"
        type="number"
        class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2" /><span
        v-if="error('durationMinutes')"
        class="mt-1 block text-sm text-rose-400"
        >{{ error("durationMinutes") }}</span
      ></label
    ><label class="flex items-center gap-3 text-sm font-medium"
      ><input
        v-model="form.active"
        type="checkbox"
        class="size-4 accent-cyan-400" />
      Active service</label
    >
    <p
      v-if="services.error"
      class="rounded-lg bg-rose-950/60 px-3 py-2 text-sm text-rose-300">
      {{ services.error }}
    </p>
    <button
      class="rounded-lg bg-cyan-400 px-4 py-3 font-semibold text-slate-950 disabled:opacity-50"
      type="submit"
      :disabled="services.isSaving">
      {{
        services.isSaving
          ? "Saving..."
          : service
            ? "Save changes"
            : "Create service"
      }}
    </button>
  </form>
</template>
