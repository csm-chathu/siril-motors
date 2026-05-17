<template>
  <div class="space-y-5">
    <div>
      <h2 class="text-xl font-semibold text-gray-800">Stock Ledger</h2>
      <p class="text-sm text-gray-500 mt-0.5">Track every stock movement — purchases, sales, and returns — per part</p>
    </div>

    <!-- Filters -->
    <div class="card flex flex-wrap gap-3 items-end">
      <div class="flex-1 min-w-56">
        <label class="form-label">Part / Product *</label>
        <SearchableSelect v-model="filters.product_id" :options="productOptions"
          placeholder="Search by name or SKU…" @update:modelValue="load" />
      </div>
      <div>
        <label class="form-label">From Date</label>
        <input v-model="filters.from_date" type="date" class="form-input" @change="load" />
      </div>
      <div>
        <label class="form-label">To Date</label>
        <input v-model="filters.to_date" type="date" class="form-input" @change="load" />
      </div>
      <button @click="filters.from_date = ''; filters.to_date = ''; load()"
        class="px-3 py-2 text-sm text-gray-500 border rounded-lg hover:bg-gray-50">Clear Dates</button>
    </div>

    <!-- Product info card -->
    <div v-if="result.product" class="card">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
          <p class="text-xs text-gray-500 uppercase font-semibold">Part Name</p>
          <p class="font-semibold text-gray-800 mt-0.5">{{ result.product.name }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase font-semibold">SKU</p>
          <p class="font-mono text-gray-700 mt-0.5">{{ result.product.sku }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase font-semibold">Category</p>
          <p class="text-gray-700 mt-0.5">{{ result.product.part_category?.name ?? '—' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase font-semibold">Current Stock</p>
          <p class="text-2xl font-bold mt-0.5" :class="result.current_stock > 0 ? 'text-green-600' : 'text-red-500'">
            {{ result.current_stock }} units
          </p>
        </div>
      </div>
    </div>

    <!-- Movements table -->
    <div v-if="filters.product_id" class="card p-0 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Date</th>
              <th class="table-th">Reference</th>
              <th class="table-th">Description</th>
              <th class="table-th text-center">Type</th>
              <th class="table-th text-right">Qty In</th>
              <th class="table-th text-right">Qty Out</th>
              <th class="table-th text-right">Unit Cost</th>
              <th class="table-th text-right">Balance</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="loading">
              <td colspan="8" class="table-td text-center text-gray-400 py-8">Loading…</td>
            </tr>
            <tr v-else-if="!result.movements?.length">
              <td colspan="8" class="table-td text-center text-gray-400 py-8">No movements found for this period</td>
            </tr>
            <tr v-for="(row, idx) in result.movements" :key="idx"
              class="hover:bg-gray-50"
              :class="row.type === 'IN' ? 'bg-green-50/30' : row.type === 'RETURN' ? 'bg-red-50/30' : ''">
              <td class="table-td text-sm text-gray-600">{{ fmtDate(row.date) }}</td>
              <td class="table-td font-mono text-xs text-blue-700">{{ row.reference }}</td>
              <td class="table-td text-sm text-gray-700">{{ row.description }}</td>
              <td class="table-td text-center">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                  :class="{
                    'bg-green-100 text-green-700': row.type === 'IN',
                    'bg-red-100 text-red-600': row.type === 'OUT',
                    'bg-orange-100 text-orange-700': row.type === 'RETURN',
                  }">
                  {{ row.type }}
                </span>
              </td>
              <td class="table-td text-right font-semibold text-green-700">
                {{ row.qty_in > 0 ? '+' + row.qty_in : '—' }}
              </td>
              <td class="table-td text-right font-semibold text-red-600">
                {{ row.qty_out > 0 ? '-' + row.qty_out : '—' }}
              </td>
              <td class="table-td text-right text-gray-600">{{ row.unit_cost ? numFmt(row.unit_cost) : '—' }}</td>
              <td class="table-td text-right font-bold"
                :class="row.balance > 0 ? 'text-gray-800' : 'text-red-600'">
                {{ row.balance }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-else class="card text-center py-12 text-gray-400">
      <p class="text-lg font-medium">Select a part to view its stock ledger</p>
      <p class="text-sm mt-1">Search by part name or SKU above</p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import SearchableSelect from '@/components/SearchableSelect.vue'

const loading  = ref(false)
const products = ref([])
const result   = ref({ product: null, movements: [], current_stock: 0 })

const filters = reactive({ product_id: '', from_date: '', to_date: '' })

const productOptions = computed(() =>
  products.value.map(p => ({ id: p.id, name: p.name, sub: p.sku }))
)

async function load() {
  if (!filters.product_id) { result.value = { product: null, movements: [], current_stock: 0 }; return }
  loading.value = true
  try {
    const { data } = await axios.get('/api/stock-ledger', { params: filters })
    result.value = data
  } finally {
    loading.value = false
  }
}

function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}
function numFmt(n) { return Number(n || 0).toLocaleString('en-LK', { minimumFractionDigits: 2 }) }

onMounted(async () => {
  const { data } = await axios.get('/api/stock-ledger/products')
  products.value = data
})
</script>
