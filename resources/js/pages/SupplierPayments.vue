<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Supplier Payments</h2>
        <p class="text-sm text-gray-500 mt-0.5">Record payments made to suppliers against credit purchases or AP balance</p>
      </div>
      <button @click="openModal" class="btn-primary flex items-center gap-2">
        <span class="text-lg leading-none">+</span> Record Payment
      </button>
    </div>

    <!-- Filters & Summary -->
    <div class="card space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
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
        <div>
          <label class="form-label">Payment Method</label>
          <select v-model="filters.payment_method" class="form-input" @change="load">
            <option value="">All Methods</option>
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="cheque">Cheque</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-4 pt-3 border-t">
        <div class="bg-blue-50 rounded-lg p-3 text-center">
          <p class="text-xs text-gray-500 uppercase">Total Paid</p>
          <p class="text-xl font-bold text-blue-700 mt-1">LKR {{ numFmt(totalPaid) }}</p>
        </div>
        <div class="bg-green-50 rounded-lg p-3 text-center">
          <p class="text-xs text-gray-500 uppercase">Payments</p>
          <p class="text-xl font-bold text-gray-800 mt-1">{{ payments.length }}</p>
        </div>
        <div class="bg-amber-50 rounded-lg p-3 text-center">
          <p class="text-xs text-gray-500 uppercase">Suppliers</p>
          <p class="text-xl font-bold text-gray-800 mt-1">{{ uniqueSuppliers }}</p>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Payment #</th>
              <th class="table-th">Date</th>
              <th class="table-th">Supplier</th>
              <th class="table-th">Linked PO</th>
              <th class="table-th text-right">Amount</th>
              <th class="table-th">Method</th>
              <th class="table-th">Cheque Info</th>
              <th class="table-th">GL Entry</th>
              <th class="table-th text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="loading"><td colspan="9" class="table-td text-center text-gray-400 py-8">Loading…</td></tr>
            <tr v-else-if="!payments.length"><td colspan="9" class="table-td text-center text-gray-400 py-8">No payments found</td></tr>
            <tr v-for="p in payments" :key="p.id" class="hover:bg-gray-50">
              <td class="table-td font-mono text-xs text-blue-700 font-semibold">{{ p.payment_number }}</td>
              <td class="table-td text-sm text-gray-600">{{ fmtDate(p.payment_date) }}</td>
              <td class="table-td font-medium">{{ p.supplier?.name }}</td>
              <td class="table-td text-xs text-gray-500">{{ p.purchase?.purchase_number ?? '—' }}</td>
              <td class="table-td text-right font-semibold text-gray-800">{{ numFmt(p.amount) }}</td>
              <td class="table-td">
                <span class="badge text-xs capitalize"
                  :class="methodClass(p.payment_method)">
                  {{ p.payment_method?.replace('_', ' ') }}
                </span>
              </td>
              <td class="table-td text-xs text-gray-500">
                <span v-if="p.cheque_number">{{ p.cheque_number }} / {{ p.cheque_bank_name }}</span>
                <span v-else>—</span>
              </td>
              <td class="table-td text-xs font-mono text-gray-500">{{ p.journal_entry?.entry_number ?? '—' }}</td>
              <td class="table-td text-right">
                <button @click="deletePayment(p)"
                  class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h3 class="font-semibold text-gray-800">Record Supplier Payment</h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
        </div>
        <form @submit.prevent="save" class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="form-label">Supplier *</label>
              <SearchableSelect v-model="form.supplier_id" :options="suppliers"
                placeholder="Select supplier…" @update:modelValue="loadSupplierPOs" />
            </div>
            <div class="col-span-2">
              <label class="form-label">Linked Purchase Order (optional)</label>
              <SearchableSelect v-model="form.purchase_id" :options="supplierPOs"
                placeholder="Select PO…" />
            </div>
            <div>
              <label class="form-label">Payment Date *</label>
              <input v-model="form.payment_date" type="date" required class="form-input" />
            </div>
            <div>
              <label class="form-label">Amount (LKR) *</label>
              <input v-model.number="form.amount" type="number" min="0.01" step="0.01" required class="form-input text-right" />
            </div>
            <div class="col-span-2">
              <label class="form-label">Payment Method *</label>
              <select v-model="form.payment_method" required class="form-input">
                <option value="cash">Cash</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cheque">Cheque</option>
              </select>
            </div>
            <template v-if="form.payment_method === 'cheque'">
              <div>
                <label class="form-label">Cheque Number *</label>
                <input v-model="form.cheque_number" required class="form-input" />
              </div>
              <div>
                <label class="form-label">Cheque Date *</label>
                <input v-model="form.cheque_date" type="date" required class="form-input" />
              </div>
              <div class="col-span-2">
                <label class="form-label">Bank Name</label>
                <input v-model="form.cheque_bank_name" class="form-input" />
              </div>
            </template>
            <div class="col-span-2">
              <label class="form-label">Reference</label>
              <input v-model="form.reference" class="form-input" placeholder="Receipt / transaction ref" />
            </div>
            <div class="col-span-2">
              <label class="form-label">Notes</label>
              <textarea v-model="form.notes" rows="2" class="form-input" />
            </div>
          </div>

          <div class="bg-blue-50 rounded-lg p-3 text-sm text-blue-800">
            GL: <strong>Dr Accounts Payable (2000)</strong> / <strong>Cr {{ form.payment_method === 'cash' ? 'Cash (1000)' : 'Bank (1010)' }}</strong>
          </div>

          <p v-if="formError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ formError }}</p>
          <div class="flex gap-3 pt-2">
            <button type="button" @click="showModal = false" class="btn-secondary flex-1">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary flex-1">
              {{ saving ? 'Saving…' : 'Record Payment' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import SearchableSelect from '@/components/SearchableSelect.vue'

const loading   = ref(false)
const saving    = ref(false)
const payments  = ref([])
const suppliers = ref([])
const supplierPOs = ref([])
const showModal = ref(false)
const formError = ref('')

const filters = reactive({ supplier_id: '', from_date: '', to_date: '', payment_method: '' })
const form    = reactive({
  supplier_id: '', purchase_id: '', payment_date: today(),
  amount: 0, payment_method: 'bank_transfer',
  cheque_number: '', cheque_date: '', cheque_bank_name: '',
  reference: '', notes: '',
})

const totalPaid      = computed(() => payments.value.reduce((s, p) => s + parseFloat(p.amount || 0), 0))
const uniqueSuppliers = computed(() => new Set(payments.value.map(p => p.supplier_id)).size)

async function load() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/supplier-payments', { params: filters })
    payments.value = data.data ?? data
  } finally { loading.value = false }
}

async function loadSupplierPOs(supplierId) {
  supplierPOs.value = []
  form.purchase_id = ''
  if (!supplierId) return
  const { data } = await axios.get('/api/purchases', {
    params: { supplier_id: supplierId, 'statuses[]': ['received', 'partial'], per_page: 100 }
  })
  supplierPOs.value = (data.data ?? []).map(p => ({ id: p.id, name: p.purchase_number }))
}

function openModal() {
  Object.assign(form, {
    supplier_id: '', purchase_id: '', payment_date: today(),
    amount: 0, payment_method: 'bank_transfer',
    cheque_number: '', cheque_date: '', cheque_bank_name: '',
    reference: '', notes: '',
  })
  supplierPOs.value = []
  formError.value   = ''
  showModal.value   = true
}

async function save() {
  saving.value = true; formError.value = ''
  try {
    await axios.post('/api/supplier-payments', form)
    showModal.value = false
    load()
  } catch (e) {
    formError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
  } finally { saving.value = false }
}

async function deletePayment(p) {
  if (!confirm(`Delete payment ${p.payment_number}? This cannot be undone.`)) return
  await axios.delete(`/api/supplier-payments/${p.id}`)
  load()
}

function methodClass(m) {
  return { cash: 'bg-green-100 text-green-700', bank_transfer: 'bg-blue-100 text-blue-700', cheque: 'bg-purple-100 text-purple-700' }[m] ?? 'bg-gray-100 text-gray-600'
}
function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}
function numFmt(n) { return Number(n || 0).toLocaleString('en-LK', { minimumFractionDigits: 2 }) }
function today() { return new Date().toISOString().slice(0, 10) }

onMounted(async () => {
  const { data } = await axios.get('/api/suppliers', { params: { per_page: 500 } })
  suppliers.value = data.data ?? data
  load()
})
</script>
