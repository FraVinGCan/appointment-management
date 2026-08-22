<script setup>
import { reactive } from "vue";

import { useClientStore } from "../stores/clients";

const props = defineProps({ client: { type: Object, default: null } });
const emit = defineEmits(["saved"]);
const clients = useClientStore();
const form = reactive({
  name: props.client?.name || "",
  email: props.client?.email || "",
  phone: props.client?.phone || "",
  password: "",
  password_confirmation: "",
});
function error(field) {
  return clients.validationErrors[field]?.[0];
}
async function submit() {
  try {
    const payload = { ...form };
    if (props.client) delete payload.password;
    if (props.client) delete payload.password_confirmation;
    emit(
      "saved",
      await (props.client
        ? clients.update(props.client.id, payload)
        : clients.create(payload)),
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
      >Email<input
        v-model="form.email"
        required
        type="email"
        class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2" /><span
        v-if="error('email')"
        class="mt-1 block text-sm text-rose-400"
        >{{ error("email") }}</span
      ></label
    ><label class="block text-sm font-medium"
      >Phone<input
        v-model="form.phone"
        type="tel"
        class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2" /><span
        v-if="error('phone')"
        class="mt-1 block text-sm text-rose-400"
        >{{ error("phone") }}</span
      ></label
    >
    <div v-if="!client" class="grid gap-5 sm:grid-cols-2">
      <label class="block text-sm font-medium"
        >Temporary password<input
          v-model="form.password"
          required
          type="password"
          class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2" /><span
          v-if="error('password')"
          class="mt-1 block text-sm text-rose-400"
          >{{ error("password") }}</span
        ></label
      ><label class="block text-sm font-medium"
        >Confirm password<input
          v-model="form.password_confirmation"
          required
          type="password"
          class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2"
      /></label>
    </div>
    <p
      v-if="clients.error"
      class="rounded-lg bg-rose-950/60 px-3 py-2 text-sm text-rose-300">
      {{ clients.error }}
    </p>
    <button
      class="rounded-lg bg-cyan-400 px-4 py-3 font-semibold text-slate-950 disabled:opacity-50"
      type="submit"
      :disabled="clients.isSaving">
      {{
        clients.isSaving
          ? "Saving..."
          : client
            ? "Save changes"
            : "Create client"
      }}
    </button>
  </form>
</template>
