<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Purchase Returns</h2>
        <p class="text-sm text-gray-500 mt-0.5">Return damaged, wrong, or excess items to suppliers</p>
      </div>
      <button @click="openModal" class="btn-primary flex items-center gap-2">
        <span class="text-lg leading-none">+</span> New Return
      </button>
    </div>

    <!-- Filters -->
    <div class="card flex flex-wrap gap-3">
      <div class="flex-1 min-w-48">
        <label class="form-label">Supplier</label>
        <SearchableSelect v-model="filters.supplier_id" :options="suppliers"
          placeholder="All Suppliers" @update:modelValue="load" />
      </div>
      <div>
        <label class="form-label">From Date</label>
        <input v-model="filters.from_date" type="date" class="form-input" @change="load" />
      </div>
      <div>
        <label class="form-label">To Date</label>
        <input v-model="filters.to_date" type="date" class="form-input" @change="load" />
      </div>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Return #</th>
              <th class="table-th">Date</th>
              <th class="table-th">Supplier</th>
              <th class="table-th">Linked PO</th>
              <th class="table-th">Reason</th>
              <th class="table-th">Items</th>
              <th class="table-th text-right">Total</th>
              <th class="table-th">Credit Method</th>
              <th class="table-th">GL</th>
              <th class="table-th text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="loading"><td colspan="10" class="table-td text-center text-gray-400 py-8">Loading…</td></tr>
            <tr v-else-if="!returns.length"><td colspan="10" class="table-td text-center text-gray-400 py-8">No returns found</td></tr>
            <tr v-for="r in returns" :key="r.id" class="hover:bg-gray-50">
              <td class="table-td font-mono text-xs text-orange-700 font-semibold">{{ r.return_number }}</td>
              <td class="table-td text-sm text-gray-600">{{ fmtDate(r.return_date) }}</td>
              <td class="table-td font-medium">{{ r.supplier?.name }}</td>
              <td class="table-td text-xs text-gray-500">{{ r.purchase?.purchase_number ?? '—' }}</td>
              <td class="table-td">
                <span class="badge text-xs" :class="reasonClass(r.reason)">{{ reasonLabel(r.reason) }}</span>
              </td>
              <td class="table-td text-center text-gray-600">{{ r.items?.length ?? 0 }}</td>
              <td class="table-td text-right font-semibold text-gray-800">{{ numFmt(r.total) }}</td>
              <td class="table-td text-xs">
                <span class="badge" :class="creditClass(r.credit_method)">{{ creditLabel(r.credit_method) }}</span>
              </td>
              <td class="table-td text-xs font-mono text-gray-500">{{ r.journal_entry?.entry_number ?? '—' }}</td>
              <td class="table-td text-right">
                <div class="flex justify-end gap-1.5">
                  <button @click="viewReturn(r)"
                    class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                    View
                  </button>
                  <button @click="deleteReturn(r)"
                    class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-start justify-center bg-black/50 overflow-y-auto py-8 px-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h3 class="font-semibold text-gray-800">New Purchase Return</h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
        </div>
        <form @submit.prevent="save" class="p-6 space-y-5">
          <!-- Header fields -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Supplier *</label>
              <SearchableSelect v-model="form.supplier_id" :options="suppliers"
                placeholder="Select supplier…" @update:modelValue="loadSupplierPOs" />
            </div>
            <div>
              <label class="form-label">Linked PO (optional)</label>
              <SearchableSelect v-model="form.purchase_id" :options="supplierPOs"
                placeholder="Select PO…" @update:modelValue="loadPOItems" />
            </div>
            <div>
              <label class="form-label">Return Date *</label>
              <input v-model="form.return_date" type="date" required class="form-input" />
            </div>
            <div>
              <label class="form-label">Reason *</label>
              <select v-model="form.reason" required class="form-input">
                <option value="damaged">Damaged / Defective</option>
                <option value="wrong_item">Wrong Item Received</option>
                <option value="excess_quantity">Excess Quantity</option>
                <option value="quality_issue">Quality Issue</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div>
              <label class="form-label">Credit Method *</label>
              <select v-model="form.credit_method" required class="form-input">
                <option value="ap_credit">AP Credit (reduce what we owe)</option>
                <option value="cash_refund">Cash Refund</option>
                <option value="bank_refund">Bank Refund</option>
              </select>
            </div>
            <div>
              <label class="form-label">Notes</label>
              <input v-model="form.notes" class="form-input" placeholder="Optional" />
            </div>
          </div>

          <!-- GL note -->
          <div class="bg-orange-50 rounded-lg px-4 py-2 text-xs text-orange-800">
            GL: <strong>Dr {{ creditMethodGL }}</strong> / <strong>Cr Inventory (1200)</strong> — stock and payable both reduce
          </div>

          <!-- Items -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <h4 class="font-semibold text-gray-700 text-sm">Return Items</h4>
              <button type="button" @click="addItem"
                class="text-xs text-blue-600 hover:underline">+ Add Item</button>
            </div>
            <div class="space-y-2">
              <div v-for="(item, idx) in form.items" :key="idx"
                class="grid grid-cols-12 gap-2 items-end border rounded-lg p-3 bg-gray-50">
                <div class="col-span-4">
                  <label class="form-label">Product *</label>
                  <SearchableSelect v-model="item.product_id" :options="productOptions"
                    placeholder="Search part…" @update:modelValue="(id) => prefillItem(item, id)" />
                </div>
                <div class="col-span-2">
                  <label class="form-label">Qty *</label>
                  <input v-model.number="item.quantity" type="number" min="1" class="form-input text-right" />
                </div>
                <div class="col-span-2">
                  <label class="form-label">Unit Cost</label>
                  <input v-model.number="item.unit_cost" type="number" min="0" step="0.01" class="form-input text-right" />
                </div>
                <div class="col-span-3">
                  <label class="form-label">Reason Note</label>
                  <input v-model="item.reason_note" class="form-input" placeholder="Optional" />
                </div>
                <div class="col-span-1 flex justify-end">
                  <button v-if="form.items.length > 1" type="button" @click="form.items.splice(idx, 1)"
                    class="text-red-400 hover:text-red-600 text-lg">✕</button>
                </div>
                <div class="col-span-12 text-right text-xs text-gray-500 -mt-1">
                  Line total: LKR {{ numFmt((item.unit_cost || 0) * (item.quantity || 0)) }}
                </div>
              </div>
            </div>
            <div class="flex justify-end mt-2">
              <span class="text-sm text-gray-600 mr-2 self-center">Return Total:</span>
              <span class="text-lg font-bold text-orange-700">LKR {{ numFmt(returnTotal) }}</span>
            </div>
          </div>

          <p v-if="formError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ formError }}</p>
          <div class="flex gap-3 pt-2 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary flex-1">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary flex-1">
              {{ saving ? 'Saving…' : 'Create Return' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- View Modal -->
    <div v-if="viewModal.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white">
          <h3 class="font-semibold">{{ viewModal.data?.return_number }}</h3>
          <button @click="viewModal.open = false" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
        </div>
        <div v-if="viewModal.data" class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div><p class="text-xs text-gray-500">Supplier</p><p class="font-medium">{{ viewModal.data.supplier?.name }}</p></div>
            <div><p class="text-xs text-gray-500">Return Date</p><p class="font-medium">{{ fmtDate(viewModal.data.return_date) }}</p></div>
            <div><p class="text-xs text-gray-500">Reason</p><p class="font-medium">{{ reasonLabel(viewModal.data.reason) }}</p></div>
            <div><p class="text-xs text-gray-500">Credit Method</p><p class="font-medium">{{ creditLabel(viewModal.data.credit_method) }}</p></div>
            <div><p class="text-xs text-gray-500">GL Entry</p><p class="font-mono text-xs">{{ viewModal.data.journal_entry?.entry_number ?? '—' }}</p></div>
            <div><p class="text-xs text-gray-500">Total</p><p class="font-bold text-orange-700">LKR {{ numFmt(viewModal.data.total) }}</p></div>
          </div>
          <table class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-left">Product</th>
                <th class="px-3 py-2 text-right">Qty</th>
                <th class="px-3 py-2 text-right">Unit Cost</th>
                <th class="px-3 py-2 text-right">Total</th>
                <th class="px-3 py-2 text-left">Note</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="item in viewModal.data.items" :key="item.id">
                <td class="px-3 py-2">{{ item.product?.name }} <span class="text-xs text-gray-400">{{ item.product?.sku }}</span></td>
                <td class="px-3 py-2 text-right">{{ item.quantity }}</td>
                <td class="px-3 py-2 text-right">{{ numFmt(item.unit_cost) }}</td>
                <td class="px-3 py-2 text-right font-semibold">{{ numFmt(item.total) }}</td>
                <td class="px-3 py-2 text-xs text-gray-500">{{ item.reason_note ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import SearchableSelect from '@/components/SearchableSelect.vue'

const loading    = ref(false)
const saving     = ref(false)
const returns    = ref([])
const suppliers  = ref([])
const products   = ref([])
const supplierPOs = ref([])
const showModal  = ref(false)
const formError  = ref('')
const viewModal  = reactive({ open: false, data: null })

const filters = reactive({ supplier_id: '', from_date: '', to_date: '' })

const defaultItem = () => ({ product_id: '', quantity: 1, unit_cost: 0, reason_note: '' })
const form = reactive({
  supplier_id: '', purchase_id: '', return_date: today(),
  reason: 'damaged', credit_method: 'ap_credit', notes: '',
  items: [defaultItem()],
})

const productOptions = computed(() => products.value.map(p => ({ id: p.id, name: p.name, sub: p.sku })))
const returnTotal    = computed(() => form.items.reduce((s, i) => s + (i.unit_cost || 0) * (i.quantity || 0), 0))
const creditMethodGL = computed(() => ({
  ap_credit: 'AP (2000)',
  cash_refund: 'Cash (1000)',
  bank_refund: 'Bank (1010)',
}[form.credit_method] ?? ''))

async function load() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/purchase-returns', { params: filters })
    returns.value = data.data ?? data
  } finally { loading.value = false }
}

async function loadSupplierPOs(supplierId) {
  supplierPOs.value = []; form.purchase_id = ''
  if (!supplierId) return
  const { data } = await axios.get('/api/purchases', {
    params: { supplier_id: supplierId, 'statuses[]': ['received', 'partial'], per_page: 100 }
  })
  supplierPOs.value = (data.data ?? []).map(p => ({ id: p.id, name: p.purchase_number }))
}

async function loadPOItems(purchaseId) {
  if (!purchaseId) return
  const { data } = await axios.get(`/api/purchases/${purchaseId}`)
  form.items = (data.items ?? []).map(i => ({
    product_id: i.product_id,
    quantity: i.received_quantity ?? i.quantity,
    unit_cost: i.unit_cost,
    reason_note: '',
  }))
}

function prefillItem(item, productId) {
  const p = products.value.find(x => x.id == productId)
  if (p) item.unit_cost = p.purchase_price ?? 0
}

function addItem() { form.items.push(defaultItem()) }

function openModal() {
  Object.assign(form, {
    supplier_id: '', purchase_id: '', return_date: today(),
    reason: 'damaged', credit_method: 'ap_credit', notes: '',
    items: [defaultItem()],
  })
  supplierPOs.value = []; formError.value = ''
  showModal.value   = true
}

async function save() {
  saving.value = true; formError.value = ''
  try {
    await axios.post('/api/purchase-returns', form)
    showModal.value = false
    load()
  } catch (e) {
    formError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
  } finally { saving.value = false }
}

async function viewReturn(r) {
  const { data } = await axios.get(`/api/purchase-returns/${r.id}`)
  viewModal.data = data
  viewModal.open = true
}

async function deleteReturn(r) {
  if (!confirm(`Delete return ${r.return_number}? This will restore stock.`)) return
  await axios.delete(`/api/purchase-returns/${r.id}`)
  load()
}

const REASON_LABELS = {
  damaged: 'Damaged', wrong_item: 'Wrong Item', excess_quantity: 'Excess Qty',
  quality_issue: 'Quality Issue', other: 'Other',
}
const CREDIT_LABELS = { ap_credit: 'AP Credit', cash_refund: 'Cash Refund', bank_refund: 'Bank Refund' }

function reasonLabel(r) { return REASON_LABELS[r] ?? r }
function creditLabel(c) { return CREDIT_LABELS[c] ?? c }
function reasonClass(r) {
  return { damaged: 'bg-red-100 text-red-700', wrong_item: 'bg-orange-100 text-orange-700',
    excess_quantity: 'bg-yellow-100 text-yellow-700', quality_issue: 'bg-purple-100 text-purple-700',
    other: 'bg-gray-100 text-gray-600' }[r] ?? 'bg-gray-100 text-gray-600'
}
function creditClass(c) {
  return { ap_credit: 'bg-blue-100 text-blue-700', cash_refund: 'bg-green-100 text-green-700',
    bank_refund: 'bg-indigo-100 text-indigo-700' }[c] ?? 'bg-gray-100 text-gray-600'
}
function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}
function numFmt(n) { return Number(n || 0).toLocaleString('en-LK', { minimumFractionDigits: 2 }) }
function today() { return new Date().toISOString().slice(0, 10) }

onMounted(async () => {
  const [sup, prod] = await Promise.all([
    axios.get('/api/suppliers', { params: { per_page: 500 } }),
    axios.get('/api/stock-ledger/products'),
  ])
  suppliers.value = sup.data.data ?? sup.data
  products.value  = prod.data
  load()
})
</script>
