<template>
  <div class="card p-0 overflow-hidden">
    <div class="px-5 py-3 border-b bg-gray-50 flex items-center justify-between">
      <h3 class="font-semibold text-gray-700">{{ title }}
        <span class="text-sm font-normal text-gray-400 ml-1">({{ rows.length }})</span>
      </h3>
      <button @click="$emit('add')" class="btn-primary text-sm py-1.5 px-3">+ Add</button>
    </div>
    <div v-if="loading" class="py-12 text-center text-gray-400 text-sm">Loading…</div>
    <table v-else class="w-full">
      <thead class="bg-gray-50 border-b">
        <tr>
          <th v-for="c in columns" :key="c.key" class="table-th">{{ c.label }}</th>
          <th class="table-th w-24"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        <tr v-if="!rows.length">
          <td :colspan="columns.length + 1" class="table-td text-center text-gray-400">No records yet</td>
        </tr>
        <tr v-for="r in rows" :key="r.id" class="hover:bg-gray-50">
          <td v-for="c in columns" :key="c.key" class="table-td">{{ r[c.key] ?? '—' }}</td>
          <td class="table-td">
            <div class="flex justify-end gap-1.5">
              <button @click="$emit('edit', r)"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-medium bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors">
                <PencilIcon class="w-3 h-3" /> Edit
              </button>
              <button @click="$emit('delete', r)"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-medium bg-red-50 text-red-600 hover:bg-red-100 transition-colors">
                <TrashIcon class="w-3 h-3" /> Delete
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'

defineProps({
  title:   String,
  rows:    Array,
  columns: Array,
  loading: Boolean,
})
defineEmits(['add', 'edit', 'delete'])
</script>
