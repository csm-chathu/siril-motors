<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Day-End Stock Reconciliation</h2>
        <p class="text-sm text-gray-500 mt-0.5">Match physical stock quantities against system records</p>
      </div>
      <input v-model="reportDate" type="date" class="form-input w-48" @change="load" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" v-if="dayData">
      <!-- Left: item-by-item count -->
      <div class="lg:col-span-2 space-y-4">

        <!-- Summary stats -->
        <div class="grid grid-cols-3 gap-3">
          <div class="card text-center py-3">
            <p class="text-xs text-gray-500">Total SKUs</p>
            <p class="text-2xl font-bold text-gray-800">{{ dayData.products.length }}</p>
          </div>
          <div class="card text-center py-3">
            <p class="text-xs text-gray-500">Items with Variance</p>
            <p class="text-2xl font-bold" :class="varianceCount > 0 ? 'text-red-600' : 'text-green-600'">{{ varianceCount }}</p>
          </div>
          <div class="card text-center py-3">
            <p class="text-xs text-gray-500">Low Stock Items</p>
            <p class="text-2xl font-bold text-amber-600">{{ dayData.low_stock_count ?? 0 }}</p>
          </div>
        </div>

        <!-- Product-level physical count -->
        <div class="card p-0 overflow-hidden">
          <div class="px-5 py-3 border-b bg-gray-50 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700">Item-by-Item Count</h3>
            <span class="text-xs text-gray-400">Enter physical quantity for each part</span>
          </div>
          <div class="max-h-[480px] overflow-y-auto">
            <table class="w-full">
              <thead class="bg-gray-50 border-b sticky top-0">
                <tr>
                  <th class="table-th">SKU</th>
                  <th class="table-th">Part Name</th>
                  <th class="table-th">Category</th>
                  <th class="table-th">Rack</th>
                  <th class="table-th">System Qty</th>
                  <th class="table-th">Physical Qty</th>
                  <th class="table-th">Variance</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="p in dayData.products" :key="p.id"
                  :class="getItemVariance(p) !== 0 ? 'bg-red-50' : 'hover:bg-gray-50'">
                  <td class="table-td font-mono text-xs text-gray-500">{{ p.sku }}</td>
                  <td class="table-td text-sm font-medium">{{ p.name }}</td>
                  <td class="table-td text-xs text-gray-500">{{ p.part_category?.name ?? '—' }}</td>
                  <td class="table-td text-xs font-mono text-gray-400">{{ p.rack_location ?? '—' }}</td>
                  <td class="table-td">
                    <span class="badge bg-blue-100 text-blue-700">{{ p.stock_quantity }}</span>
                  </td>
                  <td class="table-td">
                    <input :value="itemCounts[p.id] ?? p.stock_quantity"
                      @input="itemCounts[p.id] = parseInt($event.target.value) || 0"
                      type="number" min="0" class="form-input w-20 py-1 text-sm" />
                  </td>
                  <td class="table-td">
                    <span v-if="getItemVariance(p) !== 0"
                      :class="getItemVariance(p) > 0 ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700'"
                      class="badge text-xs">
                      {{ getItemVariance(p) > 0 ? '+' : '' }}{{ getItemVariance(p) }}
                    </span>
                    <span v-else class="text-green-500 text-sm">✓</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Right: save report + history -->
      <div class="space-y-4">
        <div class="card space-y-4">
          <h3 class="font-semibold text-gray-700">Submit Report</h3>
          <div class="space-y-1 text-sm border rounded-xl p-3 bg-gray-50">
            <div class="flex justify-between">
              <span class="text-gray-500">Total system stock</span>
              <span>{{ totalSystemQty }} pcs</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Total physical count</span>
              <span class="font-semibold">{{ totalPhysicalQty }} pcs</span>
            </div>
            <div class="flex justify-between border-t pt-1 mt-1">
              <span class="text-gray-500">Net variance</span>
              <span class="font-bold" :class="netVariance === 0 ? 'text-green-600' : 'text-red-600'">
                {{ netVariance >= 0 ? '+' : '' }}{{ netVariance }} pcs
              </span>
            </div>
          </div>
          <div>
            <label class="form-label">Notes</label>
            <textarea v-model="notes" rows="3" class="form-input" placeholder="Any discrepancies or notes…"></textarea>
          </div>
          <div class="flex gap-2">
            <button @click="saveReport('draft')" :disabled="saving" class="btn-secondary flex-1">
              {{ saving === 'draft' ? 'Saving…' : 'Save Draft' }}
            </button>
            <button @click="saveReport('submitted')" :disabled="saving" class="btn-primary flex-1">
              {{ saving === 'submitted' ? 'Submitting…' : 'Submit' }}
            </button>
          </div>
          <p v-if="saveMsg" class="text-sm text-green-600 bg-green-50 px-3 py-2 rounded-lg">{{ saveMsg }}</p>
          <p v-if="saveError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ saveError }}</p>
        </div>

        <!-- Recent reports -->
        <div class="card space-y-3">
          <h3 class="font-semibold text-gray-700 text-sm">Recent Reports</h3>
          <div v-for="r in dayData.recent_reports" :key="r.id"
            class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0 text-sm">
            <div>
              <p class="font-medium">{{ r.report_date }}</p>
              <p class="text-xs text-gray-400">{{ r.notes ? r.notes.slice(0, 40) : 'No notes' }}</p>
            </div>
            <span :class="r.status === 'submitted' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'" class="badge text-xs">
              {{ r.status }}
            </span>
          </div>
          <p v-if="!dayData.recent_reports.length" class="text-xs text-gray-400">No reports yet</p>
        </div>
      </div>
    </div>
    <div v-else class="card text-center text-gray-400 py-12">Loading…</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const reportDate = ref(new Date().toISOString().split('T')[0])
const dayData    = ref(null)
const itemCounts = ref({})
const notes      = ref('')
const saving     = ref(false)
const saveMsg    = ref('')
const saveError  = ref('')

const totalSystemQty = computed(() => {
  if (!dayData.value) return 0
  return dayData.value.products.reduce((s, p) => s + p.stock_quantity, 0)
})

const totalPhysicalQty = computed(() => {
  if (!dayData.value) return 0
  return dayData.value.products.reduce((s, p) => s + (itemCounts.value[p.id] ?? p.stock_quantity), 0)
})

const netVariance = computed(() => totalPhysicalQty.value - totalSystemQty.value)

const varianceCount = computed(() => {
  if (!dayData.value) return 0
  return dayData.value.products.filter(p => getItemVariance(p) !== 0).length
})

function getItemVariance(p) {
  return (itemCounts.value[p.id] ?? p.stock_quantity) - p.stock_quantity
}

async function load() {
  dayData.value = null
  const { data } = await axios.get('/api/reports/day-end')
  dayData.value = data
  itemCounts.value = {}
  data.products.forEach(p => { itemCounts.value[p.id] = p.stock_quantity })
}

async function saveReport(status) {
  saving.value = status; saveMsg.value = ''; saveError.value = ''
  try {
    await axios.post('/api/reports/day-end', {
      report_date:  reportDate.value,
      item_counts:  Object.entries(itemCounts.value).map(([id, qty]) => ({
        product_id: parseInt(id), physical_qty: qty,
      })),
      notes:  notes.value,
      status,
    })
    saveMsg.value = `Report ${status === 'submitted' ? 'submitted' : 'saved as draft'} successfully.`
    load()
  } catch (e) {
    saveError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
  } finally { saving.value = false }
}

onMounted(load)
</script>
