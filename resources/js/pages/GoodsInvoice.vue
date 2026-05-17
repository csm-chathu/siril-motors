<template>
  <div class="space-y-4">

    <!-- Filters -->
    <div class="card p-4 flex flex-wrap gap-3 items-end">
      <div>
        <label class="form-label">Supplier</label>
        <SearchableSelect v-model="filters.supplier_id" :options="suppliers"
          placeholder="All Suppliers" class="w-44" @update:modelValue="() => { page = 1; fetchInvoices() }" />
      </div>
      <div>
        <label class="form-label">Payment Method</label>
        <select v-model="filters.payment_method" class="form-input w-40" @change="fetchInvoices">
          <option value="">All Methods</option>
          <option value="cash">Cash</option>
          <option value="bank_transfer">Bank Transfer</option>
          <option value="cheque">Cheque</option>
          <option value="credit">Credit</option>
        </select>
      </div>
      <div>
        <label class="form-label">Status</label>
        <select v-model="filters.status" class="form-input w-36" @change="fetchInvoices">
          <option value="">All Statuses</option>
          <option value="ordered">Ordered</option>
          <option value="received">Received</option>
          <option value="partial">Partial</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <div>
        <label class="form-label">Date From</label>
        <input v-model="filters.date_from" type="date" class="form-input w-36" @change="fetchInvoices" />
      </div>
      <div>
        <label class="form-label">Date To</label>
        <input v-model="filters.date_to" type="date" class="form-input w-36" @change="fetchInvoices" />
      </div>
      <button @click="clearFilters" class="px-3 py-2 rounded-lg border text-gray-600 hover:bg-gray-50 text-sm">
        Clear
      </button>
    </div>

    <!-- Summary strip -->
    <div class="grid grid-cols-4 gap-4">
      <div class="card p-4">
        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Total Invoices</p>
        <p class="text-2xl font-bold text-gray-800">{{ meta.total ?? 0 }}</p>
      </div>
      <div class="card p-4">
        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Total Value (LKR)</p>
        <p class="text-2xl font-bold text-blue-700">{{ numFmt(summary.total) }}</p>
      </div>
      <div class="card p-4">
        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Cheques Pending</p>
        <p class="text-2xl font-bold text-amber-600">{{ summary.chequePending }}</p>
      </div>
      <div class="card p-4">
        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Credit Payable</p>
        <p class="text-2xl font-bold text-red-600">{{ numFmt(summary.creditTotal) }}</p>
      </div>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden p-0">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Invoice / PO No.</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Supplier</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Date</th>
              <th class="px-4 py-3 text-right font-semibold text-gray-600">Total (LKR)</th>
              <th class="px-4 py-3 text-center font-semibold text-gray-600">Payment</th>
              <th class="px-4 py-3 text-center font-semibold text-gray-600">GRN Status</th>
              <th class="px-4 py-3 text-center font-semibold text-gray-600">Cheque</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">GL Entry</th>
              <th class="px-4 py-3 text-center font-semibold text-gray-600">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="loading"><td colspan="9" class="py-8 text-center text-gray-400">Loading…</td></tr>
            <tr v-else-if="!invoices.length"><td colspan="9" class="py-8 text-center text-gray-400">No invoices found.</td></tr>
            <tr v-for="inv in invoices" :key="inv.id" class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <div class="font-mono font-semibold text-gray-800">{{ inv.purchase_number }}</div>
                <div v-if="inv.supplier_ref" class="text-xs text-gray-400">{{ inv.supplier_ref }}</div>
              </td>
              <td class="px-4 py-3 font-medium text-gray-700">{{ inv.supplier?.name ?? '—' }}</td>
              <td class="px-4 py-3 text-gray-600">{{ fmtDate(inv.purchased_at) }}</td>
              <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ numFmt(inv.total) }}</td>
              <td class="px-4 py-3 text-center">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="paymentClass(inv.payment_method)">
                  {{ paymentLabel(inv.payment_method) }}
                </span>
              </td>
              <td class="px-4 py-3 text-center">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="statusClass(inv.status)">
                  {{ inv.status }}
                </span>
              </td>
              <td class="px-4 py-3 text-center text-xs">
                <template v-if="inv.payment_method === 'cheque'">
                  <span v-if="inv.cheque_settled_at" class="text-green-700 font-semibold">Settled</span>
                  <span v-else class="text-amber-600 font-semibold"># {{ inv.cheque_number }}</span>
                </template>
                <span v-else class="text-gray-300">—</span>
              </td>
              <td class="px-4 py-3">
                <span v-if="inv.journal_entry" class="font-mono text-xs text-purple-700">
                  {{ inv.journal_entry.entry_number }}
                </span>
                <span v-else class="text-xs text-gray-400">Not posted</span>
              </td>
              <td class="px-4 py-3 text-center">
                <div class="flex justify-center gap-2">
                  <button @click="viewInvoice(inv)" class="text-xs text-blue-600 hover:underline">View</button>
                  <button v-if="inv.payment_method === 'cheque' && !inv.cheque_settled_at && inv.status !== 'ordered'"
                    @click="openSettle(inv)"
                    class="text-xs text-amber-600 hover:underline font-semibold">Settle Cheque</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="meta.last_page > 1" class="px-4 py-3 flex justify-between items-center border-t text-sm text-gray-500">
        <span>Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} records)</span>
        <div class="flex gap-2">
          <button :disabled="meta.current_page <= 1" @click="page--; fetchInvoices()"
            class="px-3 py-1 rounded border disabled:opacity-40">Prev</button>
          <button :disabled="meta.current_page >= meta.last_page" @click="page++; fetchInvoices()"
            class="px-3 py-1 rounded border disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>

    <!-- ── VIEW INVOICE MODAL ── -->
    <div v-if="viewModal.open" class="fixed inset-0 z-50 flex items-start justify-center bg-black/40 overflow-y-auto py-8">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <div>
            <h3 class="text-lg font-semibold">{{ viewModal.inv?.purchase_number }}</h3>
            <p class="text-sm text-gray-500">{{ viewModal.inv?.supplier?.name }}</p>
          </div>
          <button @click="viewModal.open = false" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
        </div>
        <div v-if="viewModal.inv" class="p-6 space-y-5">
          <div class="grid grid-cols-3 gap-3 text-sm">
            <div class="bg-gray-50 rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Invoice Date</p>
              <p class="font-medium">{{ fmtDate(viewModal.inv.purchased_at) }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">GRN Status</p>
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="statusClass(viewModal.inv.status)">
                {{ viewModal.inv.status }}
              </span>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Total (LKR)</p>
              <p class="font-bold text-blue-700 text-lg">{{ numFmt(viewModal.inv.total) }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Payment Method</p>
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="paymentClass(viewModal.inv.payment_method)">
                {{ paymentLabel(viewModal.inv.payment_method) }}
              </span>
            </div>
            <div v-if="viewModal.inv.payment_method === 'cheque'" class="bg-amber-50 rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Cheque Details</p>
              <p class="text-xs">No: {{ viewModal.inv.cheque_number }} | {{ viewModal.inv.cheque_bank_name }}</p>
              <p class="text-xs">Date: {{ fmtDate(viewModal.inv.cheque_date) }}</p>
              <p v-if="viewModal.inv.cheque_settled_at" class="text-xs text-green-700 font-semibold mt-1">
                Settled: {{ fmtDate(viewModal.inv.cheque_settled_at) }}
              </p>
              <p v-else class="text-xs text-amber-600 font-semibold mt-1">Pending Settlement</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">GL Journal Entry</p>
              <p class="font-mono text-xs">{{ viewModal.inv.journal_entry?.entry_number ?? 'Not posted' }}</p>
            </div>
          </div>

          <table class="min-w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-left">Product</th>
                <th class="px-3 py-2 text-right">Ordered</th>
                <th class="px-3 py-2 text-right">Received</th>
                <th class="px-3 py-2 text-left">Batch</th>
                <th class="px-3 py-2 text-right">Unit Cost</th>
                <th class="px-3 py-2 text-right">Selling Price</th>
                <th class="px-3 py-2 text-right">Line Total</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="item in viewModal.inv.items" :key="item.id">
                <td class="px-3 py-2 font-medium">{{ item.product?.name }}</td>
                <td class="px-3 py-2 text-right text-gray-500">{{ item.ordered_quantity ?? item.quantity }}</td>
                <td class="px-3 py-2 text-right">
                  <span :class="(item.received_quantity ?? 0) >= (item.ordered_quantity ?? item.quantity)
                    ? 'text-green-700 font-semibold' : 'text-amber-600'">
                    {{ item.received_quantity ?? 0 }}
                  </span>
                </td>
                <td class="px-3 py-2">
                  <span v-if="item.batch_number" class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs">
                    {{ item.batch_number }}
                  </span>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="px-3 py-2 text-right">{{ numFmt(item.unit_cost) }}</td>
                <td class="px-3 py-2 text-right">{{ numFmt(item.selling_price) }}</td>
                <td class="px-3 py-2 text-right font-semibold">{{ numFmt(item.total) }}</td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
              <tr>
                <td colspan="6" class="px-3 py-2 text-right">Subtotal:</td>
                <td class="px-3 py-2 text-right">{{ numFmt(viewModal.inv.subtotal) }}</td>
              </tr>
              <tr v-if="viewModal.inv.tax">
                <td colspan="6" class="px-3 py-2 text-right">Tax:</td>
                <td class="px-3 py-2 text-right">{{ numFmt(viewModal.inv.tax) }}</td>
              </tr>
              <tr class="text-blue-700">
                <td colspan="6" class="px-3 py-2 text-right">Total:</td>
                <td class="px-3 py-2 text-right font-bold">{{ numFmt(viewModal.inv.total) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div class="px-6 py-4 border-t flex justify-between">
          <button v-if="viewModal.inv?.payment_method === 'cheque' && !viewModal.inv.cheque_settled_at && viewModal.inv.status !== 'ordered'"
            @click="openSettle(viewModal.inv); viewModal.open = false"
            class="btn-primary">Settle Cheque</button>
          <div v-else></div>
          <button @click="viewModal.open = false" class="px-4 py-2 rounded-lg border text-gray-600 hover:bg-gray-50">Close</button>
        </div>
      </div>
    </div>

    <!-- ── SETTLE CHEQUE MODAL ── -->
    <div v-if="settleModal.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h3 class="text-lg font-semibold">Settle Cheque</h3>
          <button @click="settleModal.open = false" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
        </div>
        <div class="p-6 space-y-4">
          <div class="bg-amber-50 rounded-lg p-3 text-sm">
            <p class="font-semibold text-amber-800">{{ settleModal.inv?.purchase_number }}</p>
            <p class="text-amber-700">Cheque #{{ settleModal.inv?.cheque_number }} — {{ settleModal.inv?.cheque_bank_name }}</p>
            <p class="text-amber-700 mt-1 font-bold">Amount: LKR {{ numFmt(settleModal.inv?.total) }}</p>
          </div>
          <div>
            <label class="form-label">Settlement Date</label>
            <input v-model="settleModal.settled_date" type="date" class="form-input" />
          </div>
          <div>
            <label class="form-label">Notes</label>
            <textarea v-model="settleModal.notes" class="form-input" rows="2" />
          </div>
          <div v-if="settleModal.error" class="text-red-600 text-sm bg-red-50 px-4 py-2 rounded">{{ settleModal.error }}</div>
        </div>
        <div class="px-6 py-4 border-t flex justify-end gap-3">
          <button @click="settleModal.open = false" class="px-4 py-2 rounded-lg border text-gray-600 hover:bg-gray-50">Cancel</button>
          <button @click="submitSettle" :disabled="settleModal.submitting" class="btn-primary">
            {{ settleModal.submitting ? 'Processing…' : 'Confirm Settlement' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import SearchableSelect from '@/components/SearchableSelect.vue'

const loading   = ref(false)
const invoices  = ref([])
const suppliers = ref([])
const page      = ref(1)
const meta      = ref({ current_page: 1, last_page: 1, total: 0 })

const filters = reactive({
  supplier_id: '', payment_method: '', status: '', date_from: '', date_to: '',
})

const viewModal   = reactive({ open: false, inv: null })
const settleModal = reactive({
  open: false, inv: null, settled_date: '', notes: '', error: '', submitting: false,
})

const summary = computed(() => ({
  total:         invoices.value.reduce((s, i) => s + (i.total || 0), 0),
  chequePending: invoices.value.filter(i => i.payment_method === 'cheque' && !i.cheque_settled_at).length,
  creditTotal:   invoices.value
    .filter(i => i.payment_method === 'credit' && i.status === 'received')
    .reduce((s, i) => s + (i.total || 0), 0),
}))

async function fetchInvoices() {
  loading.value = true
  const params = { page: page.value, per_page: 20 }
  if (filters.supplier_id)    params.supplier_id    = filters.supplier_id
  if (filters.payment_method) params.payment_method = filters.payment_method
  if (filters.status)         params.status         = filters.status
  if (filters.date_from)      params.date_from      = filters.date_from
  if (filters.date_to)        params.date_to        = filters.date_to
  const { data } = await axios.get('/api/purchases', { params })
  invoices.value = data.data
  meta.value     = data.meta ?? { current_page: 1, last_page: 1, total: data.total }
  loading.value  = false
}

async function fetchSuppliers() {
  const { data } = await axios.get('/api/suppliers', { params: { per_page: 500 } })
  suppliers.value = data.data ?? data
}

function clearFilters() {
  Object.assign(filters, { supplier_id: '', payment_method: '', status: '', date_from: '', date_to: '' })
  page.value = 1
  fetchInvoices()
}

async function viewInvoice(inv) {
  const { data } = await axios.get(`/api/purchases/${inv.id}`)
  viewModal.inv  = data
  viewModal.open = true
}

function openSettle(inv) {
  settleModal.inv          = inv
  settleModal.settled_date = new Date().toISOString().slice(0, 10)
  settleModal.notes        = ''
  settleModal.error        = ''
  settleModal.submitting   = false
  settleModal.open         = true
}

async function submitSettle() {
  settleModal.error      = ''
  settleModal.submitting = true
  try {
    await axios.post(`/api/purchases/${settleModal.inv.id}/settle-cheque`, {
      settled_date: settleModal.settled_date,
      notes:        settleModal.notes,
    })
    settleModal.open = false
    fetchInvoices()
  } catch (e) {
    settleModal.error = e.response?.data?.message ?? 'Failed to settle cheque.'
  } finally {
    settleModal.submitting = false
  }
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
function paymentClass(m) {
  return {
    cash:          'bg-green-100 text-green-700',
    bank_transfer: 'bg-blue-100 text-blue-700',
    cheque:        'bg-amber-100 text-amber-700',
    credit:        'bg-purple-100 text-purple-700',
  }[m] ?? 'bg-gray-100 text-gray-600'
}
function paymentLabel(m) {
  return { cash: 'Cash', bank_transfer: 'Bank Transfer', cheque: 'Cheque', credit: 'Credit' }[m] ?? m
}

onMounted(() => { fetchInvoices(); fetchSuppliers() })
</script>
