<script setup>
import { onMounted, ref, watch } from "vue";

import AppConfirm from "../components/AppConfirm.vue";
import AppEmpty from "../components/AppEmpty.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import { useClientStore } from "../stores/clients";
import { useNotificationStore } from "../stores/notifications";

const clients = useClientStore();
const notifications = useNotificationStore();
const search = ref("");
const selected = ref(null);
let timer;
onMounted(() => clients.fetchList());
watch(search, (value) => {
  window.clearTimeout(timer);
  timer = window.setTimeout(
    () => clients.fetchList({ search: value, page: 1 }),
    250,
  );
});
async function deactivate() {
  try {
    await clients.deactivate(selected.value.id);
    const item = clients.current;
    const index = clients.items.findIndex((client) => client.id === item.id);
    if (index >= 0) clients.items.splice(index, 1, item);
    notifications.notify("Client deactivated.");
    selected.value = null;
  } catch {
    /* Store state is rendered below. */
  }
}
async function page(page) {
  await clients.fetchList({
    page,
    ...(search.value ? { search: search.value } : {}),
  });
}
</script>

<template>
  <section class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p
          class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-400">
          Staff workspace
        </p>
        <h1 class="mt-2 text-3xl font-semibold">Clients</h1>
      </div>
      <RouterLink
        class="rounded-lg bg-cyan-400 px-4 py-2 text-sm font-semibold text-slate-950"
        to="/clients/create"
        >Add client</RouterLink
      >
    </div>
    <input
      v-model="search"
      type="search"
      placeholder="Search clients"
      class="w-full max-w-xl rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100 outline-none focus:border-cyan-400" /><AppLoading
      v-if="clients.isLoading"
      message="Loading clients..." /><AppError
      v-else-if="clients.error"
      :message="clients.error"
      :retry="true"
      @retry="clients.fetchList()" /><AppEmpty
      v-else-if="!clients.items.length"
      message="No clients found." />
    <div
      v-else
      class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead
            class="border-b border-slate-800 text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-5 py-4">Name</th>
              <th class="px-5 py-4">Email</th>
              <th class="px-5 py-4">Phone</th>
              <th class="px-5 py-4">Status</th>
              <th class="px-5 py-4">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800">
            <tr v-for="client in clients.items" :key="client.id">
              <td class="px-5 py-4 font-semibold">{{ client.name }}</td>
              <td class="px-5 py-4 text-slate-400">{{ client.email }}</td>
              <td class="px-5 py-4 text-slate-400">
                {{ client.phone || "Not provided" }}
              </td>
              <td class="px-5 py-4">
                <span
                  :class="
                    client.active === false
                      ? 'text-slate-500'
                      : 'text-emerald-300'
                  "
                  >{{ client.active === false ? "Inactive" : "Active" }}</span
                >
              </td>
              <td class="px-5 py-4">
                <div class="flex gap-3 text-xs">
                  <RouterLink
                    class="font-semibold text-cyan-300"
                    :to="`/clients/${client.id}`"
                    >View</RouterLink
                  ><RouterLink
                    class="font-semibold text-slate-300"
                    :to="`/clients/${client.id}/edit`"
                    >Edit</RouterLink
                  ><button
                    v-if="client.active !== false"
                    class="font-semibold text-rose-300"
                    type="button"
                    @click="selected = client">
                    Deactivate
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div
        v-if="clients.pagination?.last_page > 1"
        class="flex items-center justify-between border-t border-slate-800 px-5 py-4 text-sm text-slate-400">
        <button
          class="rounded-lg border border-slate-700 px-3 py-2 disabled:opacity-40"
          type="button"
          :disabled="clients.pagination.current_page <= 1"
          @click="page(clients.pagination.current_page - 1)">
          Previous</button
        ><span
          >Page {{ clients.pagination.current_page }} of
          {{ clients.pagination.last_page }}</span
        ><button
          class="rounded-lg border border-slate-700 px-3 py-2 disabled:opacity-40"
          type="button"
          :disabled="
            clients.pagination.current_page >= clients.pagination.last_page
          "
          @click="page(clients.pagination.current_page + 1)">
          Next
        </button>
      </div>
    </div>
    <AppConfirm
      :open="Boolean(selected)"
      title="Deactivate client?"
      message="This client will no longer be able to use client booking routes."
      confirm-label="Deactivate"
      :loading="clients.isSaving"
      @cancel="selected = null"
      @confirm="deactivate" />
  </section>
</template>
