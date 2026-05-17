<template>
  <div class="space-y-4">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex gap-2">
        <button v-for="t in tabs" :key="t.value"
          @click="activeTab = t.value"
          class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors"
          :class="activeTab === t.value
            ? 'bg-blue-600 text-white border-blue-600'
            : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400'">
          {{ t.label }}
        </button>
      </div>
      <button @click="openDirectGRN" class="btn-primary flex items-center gap-2">
        <span class="text-lg leading-none">+</span> New Direct GRN
      </button>
    </div>

    <!-- ─── GRN RECORDS TAB ─── -->
    <div v-if="activeTab === 'grn'" class="card overflow-hidden p-0">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">GRN / PO Number</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Supplier</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Received Date</th>
              <th class="px-4 py-3 text-right font-semibold text-gray-600">Items</th>
              <th class="px-4 py-3 text-right font-semibold text-gray-600">Received Value (LKR)</th>
              <th class="px-4 py-3 text-center font-semibold text-gray-600">Status</th>
              <th class="px-4 py-3 text-center font-semibold text-gray-600">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="loadingGRN"><td colspan="7" class="py-8 text-center text-gray-400">Loading…</td></tr>
            <tr v-else-if="!grns.length"><td colspan="7" class="py-8 text-center text-gray-400">No GRN records found.</td></tr>
            <tr v-for="grn in grns" :key="grn.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-mono text-green-700 font-semibold">{{ grn.purchase_number }}</td>
              <td class="px-4 py-3">
                <div class="font-medium text-gray-800">{{ grn.supplier?.name ?? '—' }}</div>
                <div v-if="grn.supplier_ref" class="text-xs text-gray-400">Ref: {{ grn.supplier_ref }}</div>
              </td>
              <td class="px-4 py-3 text-gray-600">{{ fmtDate(grn.purchased_at) }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ grn.items_count ?? '—' }}</td>
              <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ numFmt(grn.total) }}</td>
              <td class="px-4 py-3 text-center">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="statusClass(grn.status)">
                  {{ grn.status }}
                </span>
              </td>
              <td class="px-4 py-3 text-center">
                <button @click="viewGRN(grn)" class="text-xs text-blue-600 hover:underline">View GRN</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="grnMeta.last_page > 1" class="px-4 py-3 flex justify-between items-center border-t text-sm text-gray-500">
        <span>Page {{ grnMeta.current_page }} of {{ grnMeta.last_page }}</span>
        <div class="flex gap-2">
          <button :disabled="grnMeta.current_page <= 1" @click="grnPage--; fetchGRNs()"
            class="px-3 py-1 rounded border disabled:opacity-40">Prev</button>
          <button :disabled="grnMeta.current_page >= grnMeta.last_page" @click="grnPage++; fetchGRNs()"
            class="px-3 py-1 rounded border disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>

    <!-- ─── PENDING POs TAB ─── -->
    <div v-if="activeTab === 'pending'" class="card overflow-hidden p-0">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">PO Number</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Supplier</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Order Date</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Expected Delivery</th>
              <th class="px-4 py-3 text-right font-semibold text-gray-600">Ordered Value (LKR)</th>
              <th class="px-4 py-3 text-center font-semibold text-gray-600">Status</th>
              <th class="px-4 py-3 text-center font-semibold text-gray-600">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="loadingPending"><td colspan="7" class="py-8 text-center text-gray-400">Loading…</td></tr>
            <tr v-else-if="!pendingPOs.length"><td colspan="7" class="py-8 text-center text-gray-400">No pending purchase orders.</td></tr>
            <tr v-for="po in pendingPOs" :key="po.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-mono text-blue-700 font-semibold">{{ po.purchase_number }}</td>
              <td class="px-4 py-3">
                <div class="font-medium text-gray-800">{{ po.supplier?.name ?? '—' }}</div>
                <div v-if="po.supplier_ref" class="text-xs text-gray-400">Ref: {{ po.supplier_ref }}</div>
              </td>
              <td class="px-4 py-3 text-gray-600">{{ fmtDate(po.purchased_at) }}</td>
              <td class="px-4 py-3">
                <span v-if="po.expected_delivery" :class="isOverdue(po.expected_delivery) ? 'text-red-600 font-semibold' : 'text-gray-600'">
                  {{ fmtDate(po.expected_delivery) }}
                  <span v-if="isOverdue(po.expected_delivery)" class="text-xs ml-1">(Overdue)</span>
                </span>
                <span v-else class="text-gray-400">—</span>
              </td>
              <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ numFmt(po.total) }}</td>
              <td class="px-4 py-3 text-center">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="statusClass(po.status)">
                  {{ po.status }}
                </span>
              </td>
              <td class="px-4 py-3 text-center">
                <button @click="openReceive(po)"
                  class="text-xs bg-green-600 text-white px-3 py-1 rounded-full hover:bg-green-700 font-semibold">
                  Receive Goods
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── VIEW GRN DETAIL MODAL ── -->
    <div v-if="viewModal.open" class="fixed inset-0 z-50 flex items-start justify-center bg-black/40 overflow-y-auto py-8">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <div>
            <h3 class="text-lg font-semibold">GRN — {{ viewModal.grn?.purchase_number }}</h3>
            <p class="text-sm text-gray-500">{{ viewModal.grn?.supplier?.name }}</p>
          </div>
          <button @click="viewModal.open = false" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
        </div>
        <div v-if="viewModal.grn" class="p-6 space-y-5">
          <div class="grid grid-cols-3 gap-4 text-sm">
            <div class="bg-gray-50 rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">GRN Date</p>
              <p class="font-medium">{{ fmtDate(viewModal.grn.purchased_at) }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Status</p>
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="statusClass(viewModal.grn.status)">
                {{ viewModal.grn.status }}
              </span>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Total Value</p>
              <p class="font-bold text-green-700">{{ numFmt(viewModal.grn.total) }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Journal Entry</p>
              <p class="font-mono text-xs">{{ viewModal.grn.journal_entry?.entry_number ?? 'Not posted' }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Payment Method</p>
              <p class="capitalize">{{ viewModal.grn.payment_method ?? '—' }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Supplier Ref</p>
              <p>{{ viewModal.grn.supplier_ref ?? '—' }}</p>
            </div>
          </div>
          <table class="min-w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-left">Product</th>
                <th class="px-3 py-2 text-right">Ordered</th>
                <th class="px-3 py-2 text-right">Received</th>
                <th class="px-3 py-2 text-left">Batch No.</th>
                <th class="px-3 py-2 text-left">Expiry</th>
                <th class="px-3 py-2 text-right">Unit Cost</th>
                <th class="px-3 py-2 text-right">Line Total</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="item in viewModal.grn.items" :key="item.id">
                <td class="px-3 py-2 font-medium">{{ item.product?.name }}</td>
                <td class="px-3 py-2 text-right text-gray-500">{{ item.ordered_quantity ?? item.quantity }}</td>
                <td class="px-3 py-2 text-right">
                  <span :class="(item.received_quantity ?? item.quantity) >= (item.ordered_quantity ?? item.quantity)
                    ? 'text-green-700 font-semibold' : 'text-amber-600 font-semibold'">
                    {{ item.received_quantity ?? item.quantity }}
                  </span>
                </td>
                <td class="px-3 py-2">
                  <span v-if="item.batch_number" class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs">
                    {{ item.batch_number }}
                  </span>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="px-3 py-2 text-gray-600 text-xs">{{ item.expiry_date ?? '—' }}</td>
                <td class="px-3 py-2 text-right">{{ numFmt(item.unit_cost) }}</td>
                <td class="px-3 py-2 text-right font-semibold">{{ numFmt(item.total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="px-6 py-4 border-t flex justify-end">
          <button @click="viewModal.open = false" class="px-4 py-2 rounded-lg border text-gray-600 hover:bg-gray-50">Close</button>
        </div>
      </div>
    </div>

    <!-- ── RECEIVE MODAL (against PO) ── -->
    <div v-if="receiveModal.open" class="fixed inset-0 z-50 flex items-start justify-center bg-black/40 overflow-y-auto py-8">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <div>
            <h3 class="text-lg font-semibold">Create GRN — {{ receiveModal.po?.purchase_number }}</h3>
            <p class="text-sm text-gray-500">{{ receiveModal.po?.supplier?.name }}</p>
          </div>
          <button @click="receiveModal.open = false" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
        </div>
        <div class="p-6 space-y-4">
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm border rounded-lg overflow-hidden">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 py-2 text-left">Product</th>
                  <th class="px-3 py-2 text-right w-20">Ordered</th>
                  <th class="px-3 py-2 text-right w-28">Receive Qty</th>
                  <th class="px-3 py-2 text-right w-28">Unit Cost</th>
                  <th class="px-3 py-2 text-left w-32">Batch No.</th>
                  <th class="px-3 py-2 text-left w-32">Expiry Date</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="row in receiveModal.items" :key="row.id">
                  <td class="px-3 py-2 font-medium">{{ row.product?.name }}</td>
                  <td class="px-3 py-2 text-right text-gray-500">{{ row.ordered_quantity ?? row.quantity }}</td>
                  <td class="px-3 py-2">
                    <input v-model.number="row._qty" type="number" min="0" :max="row.ordered_quantity ?? row.quantity"
                      class="form-input text-right w-full" />
                  </td>
                  <td class="px-3 py-2">
                    <input v-model.number="row._cost" type="number" min="0" step="0.01" class="form-input text-right w-full" />
                  </td>
                  <td class="px-3 py-2">
                    <input v-model="row._batch" class="form-input" placeholder="Optional" />
                  </td>
                  <td class="px-3 py-2">
                    <input v-model="row._expiry" type="date" class="form-input" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div>
            <label class="form-label">Notes</label>
            <textarea v-model="receiveModal.notes" class="form-input" rows="2" />
          </div>
          <div v-if="receiveModal.error" class="text-red-600 text-sm bg-red-50 px-4 py-2 rounded">{{ receiveModal.error }}</div>
        </div>
        <div class="px-6 py-4 border-t flex justify-end gap-3">
          <button @click="receiveModal.open = false" class="px-4 py-2 rounded-lg border text-gray-600 hover:bg-gray-50">Cancel</button>
          <button @click="submitReceive" :disabled="receiveModal.submitting" class="btn-primary">
            {{ receiveModal.submitting ? 'Saving…' : 'Confirm GRN' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ── DIRECT GRN MODAL ── -->
    <div v-if="directGRN.open" class="fixed inset-0 z-50 flex items-start justify-center bg-black/40 overflow-y-auto py-8">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h3 class="text-lg font-semibold">New Direct GRN (Walk-in Delivery)</h3>
          <button @click="directGRN.open = false" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
        </div>
        <div class="p-6 space-y-5">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Supplier *</label>
              <SearchableSelect v-model="directGRN.supplier_id" :options="suppliers" placeholder="Search supplier…" />
            </div>
            <div>
              <label class="form-label">Supplier Invoice / Ref No.</label>
              <input v-model="directGRN.supplier_ref" class="form-input" placeholder="e.g. INV-2024-001" />
            </div>
            <div>
              <label class="form-label">GRN Date</label>
              <input v-model="directGRN.grn_date" type="date" class="form-input" />
            </div>
            <div>
              <label class="form-label">Payment Method</label>
              <select v-model="directGRN.payment_method" class="form-input">
                <option value="cash">Cash</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cheque">Cheque</option>
                <option value="credit">Credit (Pay Later)</option>
              </select>
            </div>
            <div v-if="directGRN.payment_method === 'cheque'" class="col-span-2 grid grid-cols-3 gap-4">
              <div>
                <label class="form-label">Cheque Number *</label>
                <input v-model="directGRN.cheque_number" class="form-input" />
              </div>
              <div>
                <label class="form-label">Cheque Date *</label>
                <input v-model="directGRN.cheque_date" type="date" class="form-input" />
              </div>
              <div>
                <label class="form-label">Bank Name *</label>
                <input v-model="directGRN.cheque_bank_name" class="form-input" />
              </div>
            </div>
            <div class="col-span-2">
              <label class="form-label">Notes</label>
              <textarea v-model="directGRN.notes" class="form-input" rows="2" />
            </div>
          </div>

          <!-- Items -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <h4 class="font-semibold text-gray-700">Items Received</h4>
              <button @click="directGRN.items.push(blankDGItem())" type="button"
                class="text-sm text-blue-600 hover:underline">+ Add Item</button>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full text-sm border rounded-lg overflow-hidden">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-3 py-2 text-left">Product</th>
                    <th class="px-3 py-2 text-right w-24">Qty Received</th>
                    <th class="px-3 py-2 text-right w-28">Unit Cost</th>
                    <th class="px-3 py-2 text-right w-28">Selling Price</th>
                    <th class="px-3 py-2 text-left w-28">Batch No.</th>
                    <th class="px-3 py-2 text-left w-28">Expiry</th>
                    <th class="w-8"></th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr v-for="(item, idx) in directGRN.items" :key="idx">
                    <td class="px-3 py-2">
                      <SearchableSelect v-model="item.product_id" :options="productOptions"
                        placeholder="Select product…" @update:modelValue="() => prefillDG(item)" />
                    </td>
                    <td class="px-3 py-2">
                      <input v-model.number="item.ordered_quantity" type="number" min="1" class="form-input text-right" />
                    </td>
                    <td class="px-3 py-2">
                      <input v-model.number="item.unit_cost" type="number" min="0" step="0.01" class="form-input text-right" />
                    </td>
                    <td class="px-3 py-2">
                      <input v-model.number="item.selling_price" type="number" min="0" step="0.01" class="form-input text-right" />
                    </td>
                    <td class="px-3 py-2">
                      <input v-model="item.batch_number" class="form-input" placeholder="Optional" />
                    </td>
                    <td class="px-3 py-2">
                      <input v-model="item.expiry_date" type="date" class="form-input" />
                    </td>
                    <td class="px-3 py-2 text-center">
                      <button v-if="directGRN.items.length > 1" @click="directGRN.items.splice(idx,1)"
                        class="text-red-400 hover:text-red-600">✕</button>
                    </td>
                  </tr>
                </tbody>
                <tfoot class="bg-gray-50">
                  <tr>
                    <td colspan="2" class="px-3 py-2 text-right font-semibold text-gray-700">Total:</td>
                    <td colspan="5" class="px-3 py-2 text-right font-bold text-blue-700">
                      {{ numFmt(directGRN.items.reduce((s,i) => s + (i.unit_cost||0)*(i.ordered_quantity||0), 0)) }}
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <div v-if="directGRN.error" class="text-red-600 text-sm bg-red-50 px-4 py-2 rounded">{{ directGRN.error }}</div>
        </div>
        <div class="px-6 py-4 border-t flex justify-end gap-3">
          <button @click="directGRN.open = false" class="px-4 py-2 rounded-lg border text-gray-600 hover:bg-gray-50">Cancel</button>
          <button @click="submitDirectGRN" :disabled="directGRN.submitting" class="btn-primary">
            {{ directGRN.submitting ? 'Saving…' : 'Post GRN' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import SearchableSelect from '@/components/SearchableSelect.vue'

const activeTab    = ref('grn')
const grns         = ref([])
const pendingPOs   = ref([])
const suppliers    = ref([])
const products     = ref([])
const loadingGRN   = ref(false)
const loadingPending = ref(false)
const grnPage      = ref(1)
const grnMeta      = ref({ current_page: 1, last_page: 1 })

const tabs = [
  { label: 'GRN Records', value: 'grn' },
  { label: 'Pending POs',  value: 'pending' },
]

const viewModal    = reactive({ open: false, grn: null })
const receiveModal = reactive({ open: false, po: null, items: [], notes: '', error: '', submitting: false })
const directGRN    = reactive({
  open: false, supplier_id: '', supplier_ref: '',
  grn_date: new Date().toISOString().slice(0, 10),
  payment_method: 'cash', cheque_number: '', cheque_date: '', cheque_bank_name: '',
  notes: '', items: [], error: '', submitting: false,
})

const productOptions = computed(() => products.value.map(p => ({ ...p, sub: p.sku })))

const blankDGItem = () => ({
  product_id: '', ordered_quantity: 1, unit_cost: 0, selling_price: 0,
  batch_number: '', expiry_date: '',
})

async function fetchGRNs() {
  loadingGRN.value = true
  const { data } = await axios.get('/api/purchases', {
    params: { 'statuses[]': ['received', 'partial'], page: grnPage.value, per_page: 20 },
  })
  grns.value    = data.data
  grnMeta.value = data.meta ?? { current_page: 1, last_page: 1 }
  loadingGRN.value = false
}

async function fetchPendingPOs() {
  loadingPending.value = true
  const { data } = await axios.get('/api/purchases', {
    params: { 'statuses[]': ['ordered', 'pending'], per_page: 100 },
  })
  pendingPOs.value     = data.data
  loadingPending.value = false
}

async function fetchSupport() {
  const [sup, prod] = await Promise.all([
    axios.get('/api/suppliers', { params: { per_page: 500 } }),
    axios.get('/api/products',  { params: { per_page: 1000 } }),
  ])
  suppliers.value = sup.data.data ?? sup.data
  products.value  = prod.data.data ?? prod.data
}

async function viewGRN(grn) {
  const { data } = await axios.get(`/api/purchases/${grn.id}`)
  viewModal.grn  = data
  viewModal.open = true
}

function openReceive(po) {
  receiveModal.po    = po
  receiveModal.items = (po.items ?? []).map(i => ({
    ...i,
    _qty:    i.ordered_quantity ?? i.quantity,
    _cost:   i.unit_cost,
    _batch:  i.batch_number ?? '',
    _expiry: i.expiry_date ?? '',
  }))
  receiveModal.notes      = ''
  receiveModal.error      = ''
  receiveModal.submitting = false
  receiveModal.open       = true
}

async function submitReceive() {
  receiveModal.error      = ''
  receiveModal.submitting = true
  try {
    await axios.post(`/api/purchases/${receiveModal.po.id}/receive`, {
      notes: receiveModal.notes,
      items: receiveModal.items.map(i => ({
        id:           i.id,
        quantity:     i._qty,
        unit_cost:    i._cost,
        selling_price: i.selling_price,
        batch_number: i._batch || null,
        expiry_date:  i._expiry || null,
      })),
    })
    receiveModal.open = false
    fetchGRNs()
    fetchPendingPOs()
  } catch (e) {
    receiveModal.error = e.response?.data?.message ?? 'Failed to receive goods.'
  } finally {
    receiveModal.submitting = false
  }
}

function openDirectGRN() {
  Object.assign(directGRN, {
    open: true, supplier_id: '', supplier_ref: '',
    grn_date: new Date().toISOString().slice(0, 10),
    payment_method: 'cash', cheque_number: '', cheque_date: '', cheque_bank_name: '',
    notes: '', items: [blankDGItem()], error: '', submitting: false,
  })
}

function prefillDG(item) {
  const p = products.value.find(x => x.id === item.product_id)
  if (p) {
    item.unit_cost     = p.purchase_price ?? 0
    item.selling_price = p.selling_price  ?? 0
  }
}

async function submitDirectGRN() {
  directGRN.error = ''
  if (!directGRN.supplier_id) { directGRN.error = 'Please select a supplier.'; return }
  if (directGRN.items.some(i => !i.product_id)) { directGRN.error = 'All items need a product.'; return }
  directGRN.submitting = true
  try {
    await axios.post('/api/purchases', {
      supplier_id:      directGRN.supplier_id,
      supplier_ref:     directGRN.supplier_ref || null,
      payment_method:   directGRN.payment_method,
      cheque_number:    directGRN.cheque_number || null,
      cheque_date:      directGRN.cheque_date || null,
      cheque_bank_name: directGRN.cheque_bank_name || null,
      notes:            directGRN.notes || null,
      status:           'received',
      tax:              0,
      items:            directGRN.items.map(i => ({
        product_id:        i.product_id,
        ordered_quantity:  i.ordered_quantity,
        received_quantity: i.ordered_quantity,
        unit_cost:         i.unit_cost,
        selling_price:     i.selling_price || 0,
        batch_number:      i.batch_number || null,
        expiry_date:       i.expiry_date || null,
      })),
    })
    directGRN.open = false
    fetchGRNs()
  } catch (e) {
    directGRN.error = e.response?.data?.message ?? 'Failed to post GRN.'
  } finally {
    directGRN.submitting = false
  }
}

function isOverdue(dateStr) {
  return dateStr && new Date(dateStr) < new Date()
}

function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}
function numFmt(n) { return Number(n || 0).toLocaleString('en-LK', { minimumFractionDigits: 2 }) }
function statusClass(s) {
  return {
    ordered:   'bg-blue-100 text-blue-700',
    pending:   'bg-yellow-100 text-yellow-700',
    received:  'bg-green-100 text-green-700',
    partial:   'bg-amber-100 text-amber-700',
    cancelled: 'bg-red-100 text-red-600',
  }[s] ?? 'bg-gray-100 text-gray-600'
}

watch(activeTab, (tab) => {
  if (tab === 'pending') fetchPendingPOs()
})

onMounted(() => { fetchGRNs(); fetchSupport() })
</script>
