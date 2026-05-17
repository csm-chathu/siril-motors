<template>
  <div class="max-w-5xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center gap-4">
      <router-link to="/purchases" class="text-gray-400 hover:text-gray-600">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </router-link>
      <h2 class="text-xl font-semibold text-gray-800">New Purchase</h2>
    </div>

    <!-- Step indicator -->
    <div class="flex items-center gap-0">
      <template v-for="(s, i) in steps" :key="s.id">
        <div class="flex items-center gap-2 flex-1" :class="i < steps.length - 1 ? '' : 'flex-none'">
          <div class="flex items-center gap-2 shrink-0">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors"
              :class="step > s.id ? 'bg-green-500 text-white'
                     : step === s.id ? 'bg-blue-600 text-white'
                     : 'bg-gray-200 text-gray-500'">
              <svg v-if="step > s.id" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
              </svg>
              <span v-else>{{ s.id }}</span>
            </div>
            <div>
              <p class="text-xs font-semibold" :class="step === s.id ? 'text-blue-700' : 'text-gray-500'">{{ s.title }}</p>
              <p class="text-xs text-gray-400 hidden sm:block">{{ s.sub }}</p>
            </div>
          </div>
          <div v-if="i < steps.length - 1" class="flex-1 h-0.5 mx-3"
            :class="step > s.id ? 'bg-green-400' : 'bg-gray-200'"></div>
        </div>
      </template>
    </div>

    <!-- ── STEP 1: Order Details ── -->
    <div v-if="step === 1" class="grid grid-cols-3 gap-6">
      <div class="col-span-2 card space-y-4">
        <h3 class="font-semibold text-gray-700 border-b pb-2">Order Information</h3>

        <div>
          <label class="form-label">Supplier *</label>
          <SearchableSelect v-model="form.supplier_id" :options="suppliers" placeholder="Search supplier…" />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Supplier Invoice / Ref No.</label>
            <input v-model="form.supplier_ref" class="form-input" placeholder="e.g. INV-2024-001" />
          </div>
          <div>
            <label class="form-label">Order Date</label>
            <input v-model="form.order_date" type="date" class="form-input" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Expected Delivery</label>
            <input v-model="form.expected_delivery" type="date" class="form-input" />
          </div>
          <div>
            <label class="form-label">Payment Method</label>
            <select v-model="form.payment_method" class="form-input">
              <option value="cash">Cash</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="cheque">Cheque</option>
              <option value="credit">Credit</option>
            </select>
          </div>
        </div>

        <template v-if="form.payment_method === 'cheque'">
          <div class="grid grid-cols-3 gap-3 p-3 bg-amber-50 rounded-xl border border-amber-200">
            <div>
              <label class="form-label">Cheque Number *</label>
              <input v-model="form.cheque_number" class="form-input" />
            </div>
            <div>
              <label class="form-label">Cheque Date *</label>
              <input v-model="form.cheque_date" type="date" class="form-input" />
            </div>
            <div>
              <label class="form-label">Bank Name *</label>
              <input v-model="form.cheque_bank_name" class="form-input" />
            </div>
          </div>
        </template>

        <div>
          <label class="form-label">Notes</label>
          <textarea v-model="form.notes" rows="2" class="form-input" placeholder="Any notes about this order…"></textarea>
        </div>
      </div>

      <div class="space-y-4">
        <div class="card bg-blue-50 border-blue-200 space-y-2">
          <h4 class="font-semibold text-blue-800 text-sm">Steps Overview</h4>
          <ol class="text-xs text-blue-700 space-y-1.5 list-none">
            <li class="flex items-start gap-2"><span class="font-bold">1.</span> Enter supplier and order details</li>
            <li class="flex items-start gap-2"><span class="font-bold">2.</span> Add parts with batch numbers and GRN quantities</li>
            <li class="flex items-start gap-2"><span class="font-bold">3.</span> Review totals and submit</li>
          </ol>
        </div>

        <div v-if="step1Error" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ step1Error }}</div>
        <button @click="goStep2" class="btn-primary w-full">
          Next: Add Items →
        </button>
      </div>
    </div>

    <!-- ── STEP 2: Items + GRN + Batch ── -->
    <div v-if="step === 2" class="space-y-4">
      <div class="card p-0 overflow-hidden">
        <div class="px-5 py-3 border-b bg-gray-50 flex items-center justify-between">
          <div>
            <h3 class="font-semibold text-gray-700">Parts &amp; GRN</h3>
            <p class="text-xs text-gray-400 mt-0.5">Add items, enter batch numbers, and set received quantities</p>
          </div>
          <button @click="addItem" class="btn-primary text-sm py-1.5 px-3">+ Add Part</button>
        </div>

        <div class="divide-y divide-gray-100">
          <div v-for="(item, i) in form.items" :key="i" class="p-4 space-y-3"
            :class="i % 2 === 0 ? 'bg-white' : 'bg-gray-50/50'">

            <!-- Toggle row -->
            <div class="flex items-center justify-between">
              <div class="flex rounded-lg overflow-hidden border border-gray-200 text-xs font-medium">
                <button type="button"
                  @click="item.is_new = false; item.product_id = ''"
                  :class="!item.is_new ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                  class="px-3 py-1.5 transition-colors">Existing Part</button>
                <button type="button"
                  @click="item.is_new = true; item.product_id = null"
                  :class="item.is_new ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                  class="px-3 py-1.5 border-l border-gray-200 transition-colors">+ New Part</button>
              </div>
              <div class="flex items-center gap-3">
                <span class="text-xs text-gray-400 font-mono">Item {{ i + 1 }}</span>
                <button type="button" @click="form.items.splice(i, 1)"
                  class="text-red-400 hover:text-red-600 text-xs font-medium">Remove</button>
              </div>
            </div>

            <!-- Existing product -->
            <div v-if="!item.is_new">
              <label class="form-label">Product *</label>
              <SearchableSelect
                v-model="item.product_id"
                :options="productOptions"
                placeholder="Search by name or SKU…"
                @update:modelValue="val => prefillPrices(item, val)"
              />
            </div>

            <!-- New product fields -->
            <div v-else class="grid grid-cols-2 gap-3">
              <div class="col-span-2">
                <label class="form-label">Part Name *</label>
                <input v-model="item.new_product.name" class="form-input" placeholder="e.g. Water Pump" />
              </div>
              <div>
                <label class="form-label">Part Category</label>
                <SearchableSelect v-model="item.new_product.part_category_id" :options="partCategories" placeholder="Category…" />
              </div>
              <div>
                <label class="form-label">Quality Grade</label>
                <SearchableSelect v-model="item.new_product.quality_type_id" :options="qualityTypes" placeholder="Quality…" />
              </div>
            </div>

            <!-- Batch + Expiry -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="form-label">Batch / Lot Number</label>
                <input v-model="item.batch_number" class="form-input font-mono" placeholder="e.g. LOT-2024-001" />
              </div>
              <div>
                <label class="form-label">Expiry Date <span class="text-gray-400 font-normal">(optional)</span></label>
                <input v-model="item.expiry_date" type="date" class="form-input" />
              </div>
            </div>

            <!-- Quantities + Prices -->
            <div class="grid grid-cols-4 gap-3">
              <div>
                <label class="form-label">Ordered Qty *</label>
                <input v-model.number="item.ordered_qty" type="number" min="1" class="form-input"
                  @input="clampReceived(item)" />
              </div>
              <div>
                <label class="form-label">
                  Received (GRN)
                  <button type="button" @click="item.received_qty = item.ordered_qty"
                    class="ml-1 text-blue-500 hover:text-blue-700 text-xs font-normal underline">= Ordered</button>
                </label>
                <input v-model.number="item.received_qty" type="number" min="0" :max="item.ordered_qty" class="form-input"
                  :class="item.received_qty < item.ordered_qty ? 'border-amber-400' : ''" />
              </div>
              <div>
                <label class="form-label">Buy Price (LKR) *</label>
                <input v-model.number="item.unit_cost" type="number" min="0" step="0.01" class="form-input" />
              </div>
              <div>
                <label class="form-label">Sell Price (LKR)</label>
                <input v-model.number="item.selling_price" type="number" min="0" step="0.01" class="form-input" />
              </div>
            </div>

            <!-- GRN status hint + Line total -->
            <div class="flex items-center justify-between">
              <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                :class="item.received_qty === 0 ? 'bg-gray-100 text-gray-500'
                       : item.received_qty < item.ordered_qty ? 'bg-amber-100 text-amber-700'
                       : 'bg-green-100 text-green-700'">
                {{ item.received_qty === 0 ? 'Not received' : item.received_qty < item.ordered_qty ? `Partial: ${item.received_qty} / ${item.ordered_qty}` : 'Fully received' }}
              </span>
              <span class="text-sm text-gray-500">
                Line total: <span class="font-semibold text-gray-800">LKR {{ (item.unit_cost * item.ordered_qty).toLocaleString() }}</span>
              </span>
            </div>

            <!-- Image for new products -->
            <div v-if="item.is_new" class="border border-gray-200 rounded-lg p-3 bg-white">
              <SmartImageUploader
                :ref="el => { if (el) uploaderRefs[i] = el }"
                v-model="item.new_product.images"
                label="Part Image"
                :multiple="false"
                folder="spare-parts/products"
              />
            </div>
          </div>
        </div>

        <!-- Empty state -->
        <div v-if="!form.items.length" class="py-10 text-center text-gray-400 text-sm">
          No items yet. Click "+ Add Part" to begin.
        </div>

        <!-- Subtotal row -->
        <div v-if="form.items.length" class="px-5 py-3 bg-gray-50 border-t flex justify-end gap-6 text-sm">
          <span class="text-gray-500">{{ form.items.length }} item(s)</span>
          <span class="font-semibold text-gray-800">Subtotal: LKR {{ subtotal.toLocaleString() }}</span>
        </div>
      </div>

      <div v-if="step2Error" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ step2Error }}</div>

      <div class="flex gap-3">
        <button @click="step = 1" class="btn-secondary">← Back</button>
        <button @click="goStep3" class="btn-primary flex-1">Next: Review & Submit →</button>
      </div>
    </div>

    <!-- ── STEP 3: Payment & Review ── -->
    <div v-if="step === 3" class="grid grid-cols-3 gap-6">
      <div class="col-span-2 space-y-4">

        <!-- Status -->
        <div class="card space-y-3">
          <h3 class="font-semibold text-gray-700 border-b pb-2">Order Status</h3>
          <div class="grid grid-cols-4 gap-2">
            <button v-for="s in statusOptions" :key="s.value" type="button"
              @click="form.status = s.value"
              :class="form.status === s.value ? s.activeClass : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
              class="py-2 rounded-lg text-xs font-semibold transition-colors text-center">
              {{ s.label }}
            </button>
          </div>
          <p class="text-xs text-gray-500 bg-gray-50 px-3 py-2 rounded-lg">
            <span class="font-semibold">Auto-suggested:</span>
            <span :class="suggestedStatusClass">{{ suggestedStatusLabel }}</span>
            based on received quantities.
            <button type="button" @click="form.status = suggestedStatus"
              class="ml-1 text-blue-600 underline hover:text-blue-800">Apply</button>
          </p>
        </div>

        <!-- Tax -->
        <div class="card space-y-3">
          <h3 class="font-semibold text-gray-700 border-b pb-2">Additional Charges</h3>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Tax / Levy (LKR)</label>
              <input v-model.number="form.tax" type="number" min="0" step="0.01" class="form-input" />
            </div>
          </div>
        </div>

        <!-- Items summary -->
        <div class="card p-0 overflow-hidden">
          <div class="px-5 py-3 border-b bg-gray-50">
            <h3 class="font-semibold text-gray-700 text-sm">Items Summary</h3>
          </div>
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="table-th">Part</th>
                <th class="table-th">Batch</th>
                <th class="table-th text-center">Ord.</th>
                <th class="table-th text-center">Rcv.</th>
                <th class="table-th text-right">Cost</th>
                <th class="table-th text-right">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(item, i) in form.items" :key="i" class="hover:bg-gray-50">
                <td class="table-td">
                  <span v-if="item.is_new" class="italic text-gray-500">{{ item.new_product.name || '—' }}</span>
                  <span v-else>{{ productLabel(item.product_id) }}</span>
                </td>
                <td class="table-td font-mono text-xs text-gray-500">{{ item.batch_number || '—' }}</td>
                <td class="table-td text-center">{{ item.ordered_qty }}</td>
                <td class="table-td text-center">
                  <span :class="item.received_qty < item.ordered_qty ? 'text-amber-600 font-semibold' : 'text-green-600'">
                    {{ item.received_qty }}
                  </span>
                </td>
                <td class="table-td text-right">{{ item.unit_cost.toLocaleString() }}</td>
                <td class="table-td text-right font-medium">{{ (item.unit_cost * item.ordered_qty).toLocaleString() }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Right: totals + submit -->
      <div class="space-y-4">
        <div class="card space-y-3">
          <h3 class="font-semibold text-gray-700">Order Summary</h3>
          <div class="space-y-1.5 text-sm">
            <div class="flex justify-between text-gray-600">
              <span>Supplier</span>
              <span class="font-medium text-gray-800">{{ supplierName }}</span>
            </div>
            <div v-if="form.supplier_ref" class="flex justify-between text-gray-600">
              <span>Ref/Invoice</span>
              <span class="font-mono text-xs text-gray-800">{{ form.supplier_ref }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
              <span>Payment</span>
              <span class="capitalize">{{ form.payment_method.replace('_', ' ') }}</span>
            </div>
            <div class="border-t pt-2 mt-2 space-y-1">
              <div class="flex justify-between text-gray-600">
                <span>Subtotal</span>
                <span>LKR {{ subtotal.toLocaleString() }}</span>
              </div>
              <div class="flex justify-between text-gray-600">
                <span>Tax</span>
                <span>LKR {{ (form.tax || 0).toLocaleString() }}</span>
              </div>
              <div class="flex justify-between font-bold text-gray-800 text-base border-t pt-1 mt-1">
                <span>Total</span>
                <span class="text-blue-700">LKR {{ total.toLocaleString() }}</span>
              </div>
            </div>
          </div>
        </div>

        <p v-if="error" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ error }}</p>

        <div class="flex flex-col gap-2">
          <button @click="step = 2" class="btn-secondary w-full">← Back to Items</button>
          <button @click="submit" :disabled="saving" class="btn-primary w-full">
            {{ saving ? 'Saving…' : 'Submit Purchase' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import SmartImageUploader from '@/components/SmartImageUploader.vue'
import SearchableSelect from '@/components/SearchableSelect.vue'

const router = useRouter()
const step = ref(1)

const steps = [
  { id: 1, title: 'Order Details', sub: 'Supplier & reference' },
  { id: 2, title: 'Items & GRN',   sub: 'Parts, batch & quantities' },
  { id: 3, title: 'Review',        sub: 'Totals & submit' },
]

const statusOptions = [
  { value: 'ordered',   label: 'Ordered',   activeClass: 'bg-blue-100 text-blue-700' },
  { value: 'received',  label: 'Received',  activeClass: 'bg-green-100 text-green-700' },
  { value: 'partial',   label: 'Partial',   activeClass: 'bg-amber-100 text-amber-700' },
  { value: 'cancelled', label: 'Cancelled', activeClass: 'bg-red-100 text-red-700' },
]

// ── refs ───────────────────────────────────────────────────
const products       = ref([])
const suppliers      = ref([])
const partCategories = ref([])
const qualityTypes   = ref([])
const uploaderRefs   = ref({})
const saving         = ref(false)
const error          = ref('')
const step1Error     = ref('')
const step2Error     = ref('')

// ── form ───────────────────────────────────────────────────
const today = new Date().toISOString().split('T')[0]

const form = reactive({
  supplier_id:       '',
  supplier_ref:      '',
  order_date:        today,
  expected_delivery: '',
  payment_method:    'cash',
  cheque_number:     '',
  cheque_date:       '',
  cheque_bank_name:  '',
  notes:             '',
  status:            'received',
  tax:               0,
  items:             [],
})

function newItem() {
  return {
    is_new:        false,
    product_id:    '',
    new_product:   { name: '', part_category_id: '', quality_type_id: '', images: [] },
    batch_number:  '',
    expiry_date:   '',
    ordered_qty:   1,
    received_qty:  1,
    unit_cost:     0,
    selling_price: 0,
  }
}

function addItem() { form.items.push(newItem()) }

function clampReceived(item) {
  if (item.received_qty > item.ordered_qty) item.received_qty = item.ordered_qty
}

// ── computed ───────────────────────────────────────────────
const productOptions = computed(() =>
  products.value.map(p => ({ id: p.id, name: `${p.name}`, sub: p.sku }))
)

function productLabel(id) {
  const p = products.value.find(p => p.id == id)
  return p ? p.name : '—'
}

const supplierName = computed(() => {
  const s = suppliers.value.find(s => s.id == form.supplier_id)
  return s?.name ?? '—'
})

const subtotal = computed(() =>
  form.items.reduce((a, i) => a + (i.unit_cost * i.ordered_qty), 0)
)
const total = computed(() => subtotal.value + (form.tax || 0))

const suggestedStatus = computed(() => {
  const items = form.items
  if (!items.length) return 'ordered'
  const totalOrdered  = items.reduce((s, i) => s + (i.ordered_qty  || 0), 0)
  const totalReceived = items.reduce((s, i) => s + (i.received_qty || 0), 0)
  if (totalReceived === 0)               return 'ordered'
  if (totalReceived >= totalOrdered)     return 'received'
  return 'partial'
})

const suggestedStatusLabel = computed(() => ({
  ordered:  'Ordered (not yet received)',
  received: 'Fully Received',
  partial:  'Partially Received',
}[suggestedStatus.value] ?? suggestedStatus.value))

const suggestedStatusClass = computed(() => ({
  ordered:  'text-blue-600',
  received: 'text-green-600',
  partial:  'text-amber-600',
}[suggestedStatus.value]))

// ── step navigation ────────────────────────────────────────
function goStep2() {
  step1Error.value = ''
  if (!form.supplier_id) { step1Error.value = 'Please select a supplier.'; return }
  if (form.payment_method === 'cheque') {
    if (!form.cheque_number) { step1Error.value = 'Cheque number is required.'; return }
    if (!form.cheque_date)   { step1Error.value = 'Cheque date is required.'; return }
    if (!form.cheque_bank_name) { step1Error.value = 'Bank name is required.'; return }
  }
  if (!form.items.length) addItem()
  step.value = 2
}

function goStep3() {
  step2Error.value = ''
  if (!form.items.length) { step2Error.value = 'Add at least one item.'; return }
  for (const [i, item] of form.items.entries()) {
    if (!item.is_new && !item.product_id) { step2Error.value = `Item ${i + 1}: select a product.`; return }
    if (item.is_new && !item.new_product.name.trim()) { step2Error.value = `Item ${i + 1}: enter a part name.`; return }
    if (!item.ordered_qty || item.ordered_qty < 1) { step2Error.value = `Item ${i + 1}: ordered qty must be ≥ 1.`; return }
    if (!item.unit_cost || item.unit_cost < 0) { step2Error.value = `Item ${i + 1}: enter a valid buy price.`; return }
  }
  form.status = suggestedStatus.value
  step.value = 3
}

// ── price prefill ──────────────────────────────────────────
function prefillPrices(item, productId) {
  const p = products.value.find(p => p.id == productId)
  if (!p) return
  if (!item.unit_cost)     item.unit_cost     = p.purchase_price ?? 0
  if (!item.selling_price) item.selling_price = p.selling_price  ?? 0
}

// ── submit ─────────────────────────────────────────────────
async function submit() {
  saving.value = true; error.value = ''
  try {
    for (const [i, item] of form.items.entries()) {
      if (item.is_new && uploaderRefs.value[i]) {
        await uploaderRefs.value[i].uploadPending()
      }
    }

    const payload = {
      supplier_id:       form.supplier_id,
      supplier_ref:      form.supplier_ref || null,
      expected_delivery: form.expected_delivery || null,
      order_date:        form.order_date,
      payment_method:    form.payment_method,
      cheque_number:     form.cheque_number || null,
      cheque_date:       form.cheque_date   || null,
      cheque_bank_name:  form.cheque_bank_name || null,
      notes:             form.notes || null,
      status:            form.status,
      tax:               form.tax || 0,
      items: form.items.map(item => {
        const base = {
          ordered_quantity:  item.ordered_qty,
          received_quantity: item.received_qty,
          unit_cost:         item.unit_cost,
          selling_price:     item.selling_price || null,
          batch_number:      item.batch_number  || null,
          expiry_date:       item.expiry_date   || null,
        }
        if (item.is_new) {
          const img = item.new_product.images?.[0] ?? null
          return {
            ...base,
            product_id:  null,
            new_product: {
              name:             item.new_product.name,
              part_category_id: item.new_product.part_category_id || null,
              quality_type_id:  item.new_product.quality_type_id  || null,
              image_url:        img?.url       ?? null,
              image_public_id:  img?.public_id ?? null,
            },
          }
        }
        return { ...base, product_id: item.product_id || null }
      }),
    }

    await axios.post('/api/purchases', payload)
    router.push('/purchases')
  } catch (e) {
    error.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'Error saving purchase'
  } finally { saving.value = false }
}

// ── close dropdowns on outside click ──────────────────────
function closeAll() { form.items.forEach(i => { i._open = false }) }
document.addEventListener('click', closeAll)
onBeforeUnmount(() => document.removeEventListener('click', closeAll))

// ── load reference data ────────────────────────────────────
onMounted(async () => {
  const [p, s, pc, qt] = await Promise.all([
    axios.get('/api/products', { params: { per_page: 1000 } }),
    axios.get('/api/suppliers/all'),
    axios.get('/api/part-categories'),
    axios.get('/api/quality-types'),
  ])
  products.value       = p.data.data
  suppliers.value      = s.data
  partCategories.value = pc.data
  qualityTypes.value   = qt.data
  addItem()
})
</script>
