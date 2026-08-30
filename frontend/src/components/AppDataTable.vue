<script setup>
import { computed, ref } from "vue";

import AppEmpty from "./AppEmpty.vue";

const props = defineProps({
  columns: { type: Array, required: true },
  data: { type: Array, default: () => [] },
  search: { type: String, default: "" },
  searchPlaceholder: { type: String, default: "Search..." },
  filterCount: { type: Number, default: 0 },
  currentPage: { type: Number, default: 1 },
  total: { type: Number, default: 0 },
  perPage: { type: Number, default: 10 },
  isLoading: { type: Boolean, default: false },
  onSelect: { type: Function, default: undefined },
  tableClass: { type: String, default: "" },
  emptyMessage: { type: String, default: "No results found." },
  emptyIcon: { type: String, default: "" },
  emptyAction: { type: Object, default: () => ({}) },
});

const emit = defineEmits([
  "update:search",
  "update:perPage",
  "change",
  "clear-filters",
  "apply-filters",
  "filters-open",
]);
const filtersOpen = ref(false);
const pageSizes = [5, 10, 25, 50, 100];
const firstResult = computed(() =>
  props.total ? (props.currentPage - 1) * props.perPage + 1 : 0,
);
const lastResult = computed(() =>
  props.total ? Math.min(props.currentPage * props.perPage, props.total) : 0,
);
</script>

<template>
  <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 p-4">
      <UInput
        :model-value="search"
        icon="i-lucide-search"
        :placeholder="searchPlaceholder"
        class="w-72 max-w-full flex-none"
        @update:model-value="emit('update:search', $event)"
      />
      <UPopover
        v-model:open="filtersOpen"
        @update:open="emit('filters-open', $event)"
      >
        <UChip
          :text="filterCount"
          :show="filterCount > 0"
          color="primary"
          size="lg"
          :ui="{ base: 'p-2 text-sm font-semibold' }"
        >
          <UButton
            color="neutral"
            variant="outline"
            icon="i-lucide-list-filter"
            aria-label="Open filters"
          />
        </UChip>
        <template #content>
          <div class="grid max-h-[calc(100vh-2rem)] w-72 gap-4 overflow-y-auto p-4">
            <slot name="filters" />
            <UButton
              v-if="filterCount"
              color="neutral"
              variant="soft"
              block
              class="justify-center"
              @click="emit('clear-filters')"
            >
              Clear filters
            </UButton>
            <UButton block @click="emit('apply-filters'); filtersOpen = false">
              Apply filters
            </UButton>
          </div>
        </template>
      </UPopover>
      <slot name="toolbar" />
    </div>

    <div class="overflow-x-auto" :class="tableClass">
      <UTable
        :data="data"
        :columns="columns"
        :on-select="onSelect"
        :loading="isLoading"
        :ui="{ tr: 'cursor-pointer', separator: 'z-0' }"
        class="min-w-full"
      >
        <template #empty>
          <AppEmpty :message="emptyMessage" :icon="emptyIcon" :action="emptyAction" class="p-6" />
        </template>
      </UTable>
    </div>
    <div v-if="!data.length && !isLoading" class="p-4 sm:hidden">
      <AppEmpty :message="emptyMessage" :icon="emptyIcon" :action="emptyAction" />
    </div>

    <div class="grid items-center gap-3 border-t border-slate-800 px-4 py-3 text-sm text-slate-400 sm:grid-cols-[1fr_auto_1fr]">
      <span class="text-center sm:text-left">Showing {{ firstResult }} to {{ lastResult }} of {{ total }} results</span>
      <label class="flex items-center justify-center gap-2">
        <span>Rows</span>
        <USelect
          :model-value="perPage"
          :items="pageSizes"
          class="w-20"
          @update:model-value="emit('update:perPage', Number($event))"
        />
      </label>
      <div class="flex justify-center gap-3 sm:justify-self-end">
        <UPagination
          v-if="total > perPage"
          :page="currentPage"
          :total="total"
          :items-per-page="perPage"
          :disabled="isLoading"
          show-edges
          :ui="{ first: 'hidden', last: 'hidden' }"
          @update:page="emit('change', $event)"
        />
      </div>
    </div>
  </div>
</template>
