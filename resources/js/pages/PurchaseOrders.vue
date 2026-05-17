<template>
  <div class="space-y-4">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex gap-2">
        <button v-for="t in statusTabs" :key="t.value"
          @click="activeTab = t.value; fetchPOs()"
          class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors"
          :class="activeTab === t.value
            ? 'bg-blue-600 text-white border-blue-600'
            : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400'">
          {{ t.label }}
        </button>
      </div>
      <button @click="openNewPO" class="btn-primary flex items-center gap-2">
        <span class="text-lg leading-none">+</span> New Purchase Order
      </button>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden p-0">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">PO Number</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Supplier</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Order Date</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600">Expected Delivery</th>
              <th class="px-4 py-3 text-right font-semibold text-gray-600">Items</th>
              <th class="px-4 py-3 text-right font-semibold text-gray-600">Total (LKR)</th>
              <th class="px-4 py-3 text-center font-semibold text-gray-600">Status</th>
              <th class="px-4 py-3 text-center font-semibold text-gray-600">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="loading"><td colspan="8" class="py-8 text-center text-gray-400">Loading…</td></tr>
            <tr v-else-if="!purchases.length"><td colspan="8" class="py-8 text-center text-gray-400">No purchase orders found.</td></tr>
            <tr v-for="po in purchases" :key="po.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-mono text-blue-700 font-semibold">{{ po.purchase_number }}</td>
              <td class="px-4 py-3">
                <div class="font-medium text-gray-800">{{ po.supplier?.name ?? '—' }}</div>
                <div v-if="po.supplier_ref" class="text-xs text-gray-400">Ref: {{ po.supplier_ref }}</div>
              </td>
              <td class="px-4 py-3 text-gray-600">{{ fmtDate(po.purchased_at) }}</td>
              <td class="px-4 py-3 text-gray-600">{{ po.expected_delivery ? fmtDate(po.expected_delivery) : '—' }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ po.items_count ?? '—' }}</td>
              <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ numFmt(po.total) }}</td>
              <td class="px-4 py-3 text-center">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="statusClass(po.status)">
                  {{ po.status }}
                </span>
              </td>
              <td class="px-4 py-3 text-center">
                <div class="flex justify-center gap-2">
                  <button @click="viewPO(po)" class="text-xs text-blue-600 hover:underline">View</button>
                  <button v-if="['ordered','pending'].includes(po.status)"
                    @click="openReceive(po)"
                    class="text-xs text-green-600 hover:underline font-semibold">Receive</button>
                  <button v-if="['ordered','pending'].includes(po.status)"
                    @click="cancelPO(po)"
                    class="text-xs text-red-500 hover:underline">Cancel</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="px-4 py-3 flex justify-between items-center border-t text-sm text-gray-500">
        <span>Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} records)</span>
        <div class="flex gap-2">
          <button :disabled="meta.current_page <= 1" @click="page--; fetchPOs()"
            class="px-3 py-1 rounded border disabled:opacity-40 hover:bg-gray-50">Prev</button>
          <button :disabled="meta.current_page >= meta.last_page" @click="page++; fetchPOs()"
            class="px-3 py-1 rounded border disabled:opacity-40 hover:bg-gray-50">Next</button>
        </div>
      </div>
    </div>

    <!-- ── NEW PO MODAL ── -->
    <div v-if="showNewPO" class="fixed inset-0 z-50 flex items-start justify-center bg-black/40 overflow-y-auto py-6 px-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-7xl flex flex-col" style="min-height:600px">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b shrink-0">
          <h3 class="text-lg font-semibold text-gray-800">New Purchase Order</h3>
          <button @click="showNewPO = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
        </div>

        <!-- Two-column body -->
        <div class="flex flex-1 min-h-0 divide-x overflow-hidden">

          <!-- LEFT: Order Details -->
          <div class="w-80 shrink-0 flex flex-col overflow-y-auto">
            <div class="px-5 py-4 bg-gray-50 border-b">
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Order Details</p>
            </div>
            <div class="px-5 py-4 space-y-3 flex-1">
              <div>
                <label class="form-label">Supplier *</label>
                <SearchableSelect v-model="form.supplier_id" :options="suppliers" placeholder="Search supplier…" />
              </div>
              <div>
                <label class="form-label">Supplier Ref / Invoice No.</label>
                <input v-model="form.supplier_ref" class="form-input" placeholder="e.g. INV-2024-001" />
              </div>
              <div>
                <label class="form-label">Order Date</label>
                <input v-model="form.order_date" type="date" class="form-input" />
              </div>
              <div>
                <label class="form-label">Expected Delivery</label>
                <input v-model="form.expected_delivery" type="date" class="form-input" />
              </div>

              <div class="pt-1 border-t">
                <label class="form-label">Payment Method</label>
                <select v-model="form.payment_method" class="form-input">
                  <option value="credit">Credit (Pay Later)</option>
                  <option value="cash">Cash</option>
                  <option value="bank_transfer">Bank Transfer</option>
                  <option value="cheque">Cheque</option>
                </select>
              </div>
              <template v-if="form.payment_method === 'cheque'">
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
              </template>

              <div class="pt-1 border-t">
                <label class="form-label">Notes</label>
                <textarea v-model="form.notes" class="form-input" rows="3" placeholder="Optional notes…" />
              </div>

              <!-- Order total summary -->
              <div class="pt-2 border-t">
                <div class="bg-blue-50 rounded-lg p-3 text-center">
                  <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Order Total</p>
                  <p class="text-2xl font-bold text-blue-700">LKR {{ numFmt(poTotal) }}</p>
                  <p class="text-xs text-gray-400 mt-1">{{ form.items.length }} item{{ form.items.length !== 1 ? 's' : '' }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- RIGHT: Order Items -->
          <div class="flex-1 flex flex-col overflow-y-auto min-w-0">
            <div class="px-5 py-4 bg-gray-50 border-b flex items-center justify-between shrink-0">
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Order Items</p>
              <button @click="addItem" type="button"
                class="flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition-colors">
                + Add Item
              </button>
            </div>
            <div class="px-5 py-4 space-y-3 flex-1">
              <div v-for="(item, idx) in form.items" :key="idx"
                class="border rounded-lg bg-white shadow-sm overflow-hidden">

                <!-- Item header bar -->
                <div class="flex items-center justify-between px-3 py-2 bg-gray-50 border-b">
                  <div class="flex gap-1.5">
                    <button type="button"
                      @click="item.is_new = false"
                      :class="!item.is_new ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-gray-500 border hover:bg-gray-50'"
                      class="px-2.5 py-1 rounded text-xs font-semibold transition-colors">
                      Existing Part
                    </button>
                    <button type="button"
                      @click="item.is_new = true; item.product_id = ''"
                      :class="item.is_new ? 'bg-amber-500 text-white shadow-sm' : 'bg-white text-gray-500 border hover:bg-gray-50'"
                      class="px-2.5 py-1 rounded text-xs font-semibold transition-colors">
                      New Part
                    </button>
                  </div>
                  <div class="flex items-center gap-3">
                    <span class="text-xs font-semibold text-gray-600">
                      Line: LKR {{ numFmt((item.unit_cost || 0) * (item.ordered_quantity || 0)) }}
                    </span>
                    <button v-if="form.items.length > 1" type="button" @click="form.items.splice(idx,1)"
                      class="text-red-400 hover:text-red-600 text-base leading-none">✕</button>
                  </div>
                </div>

                <!-- Item body -->
                <div class="p-3 space-y-3">
                  <!-- Existing product select -->
                  <div v-if="!item.is_new">
                    <label class="form-label">Product *</label>
                    <SearchableSelect v-model="item.product_id" :options="productOptions"
                      placeholder="Search by name or SKU…" @update:modelValue="() => prefill(item)" />
                  </div>

                  <!-- New product form -->
                  <div v-else class="grid grid-cols-2 gap-2">
                    <div class="col-span-2">
                      <label class="form-label">Part Name *</label>
                      <input v-model="item.new_product.name" class="form-input"
                        placeholder="e.g. Brake Pad – Toyota Corolla" />
                    </div>
                    <div>
                      <label class="form-label">SKU / Part No.</label>
                      <input v-model="item.new_product.sku" class="form-input" placeholder="Auto-generate if blank" />
                    </div>
                    <div>
                      <label class="form-label">Part Category</label>
                      <SearchableSelect v-model="item.new_product.part_category_id"
                        :options="partCategories" placeholder="— Select —" />
                    </div>
                    <div>
                      <label class="form-label">Vehicle Type</label>
                      <SearchableSelect v-model="item.new_product.vehicle_type_id"
                        :options="vehicleTypes" placeholder="— Select —" />
                    </div>
                    <div>
                      <label class="form-label">Brand</label>
                      <SearchableSelect v-model="item.new_product.brand_id"
                        :options="brands" placeholder="— Select —" />
                    </div>
                    <div>
                      <label class="form-label">Model</label>
                      <SearchableSelect v-model="item.new_product.model_id"
                        :options="vehicleModels" placeholder="— Select —" />
                    </div>
                    <div>
                      <label class="form-label">Quality Type</label>
                      <SearchableSelect v-model="item.new_product.quality_type_id"
                        :options="qualityTypes" placeholder="— Select —" />
                    </div>
                    <div>
                      <label class="form-label">Rack / Location</label>
                      <input v-model="item.new_product.rack_location" class="form-input" placeholder="e.g. A-12" />
                    </div>
                    <div>
                      <label class="form-label">Min Stock Level</label>
                      <input v-model.number="item.new_product.min_stock_level"
                        type="number" min="0" class="form-input" />
                    </div>
                  </div>

                  <!-- Qty & Cost -->
                  <div class="grid grid-cols-2 gap-2 pt-1 border-t">
                    <div>
                      <label class="form-label">Ordered Qty *</label>
                      <input v-model.number="item.ordered_quantity" type="number" min="1"
                        class="form-input text-right" />
                    </div>
                    <div>
                      <label class="form-label">Unit Cost (LKR)</label>
                      <input v-model.number="item.unit_cost" type="number" min="0" step="0.01"
                        class="form-input text-right" />
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="!form.items.length" class="text-center py-8 text-gray-400 text-sm">
                Click "+ Add Item" to start adding parts to this order.
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t shrink-0 flex items-center justify-between bg-gray-50 rounded-b-xl">
          <p v-if="formError" class="text-sm text-red-600 bg-red-50 px-3 py-1.5 rounded-lg">{{ formError }}</p>
          <span v-else></span>
          <div class="flex gap-3">
            <button @click="showNewPO = false" class="px-4 py-2 rounded-lg border text-gray-600 hover:bg-gray-100">Cancel</button>
            <button @click="submitPO" :disabled="submitting" class="btn-primary">
              {{ submitting ? 'Saving…' : 'Create Purchase Order' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── VIEW PO MODAL ── -->
    <div v-if="viewModal.open" class="fixed inset-0 z-50 flex items-start justify-center bg-black/40 overflow-y-auto py-8">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <div>
            <h3 class="text-lg font-semibold">{{ viewModal.po?.purchase_number }}</h3>
            <p class="text-sm text-gray-500">{{ viewModal.po?.supplier?.name }}</p>
          </div>
          <button @click="viewModal.open = false" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
        </div>
        <div v-if="viewModal.po" class="p-6 space-y-5">
          <div class="grid grid-cols-3 gap-4 text-sm">
            <div class="bg-gray-50 rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Order Date</p>
              <p class="font-medium">{{ fmtDate(viewModal.po.purchased_at) }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Expected Delivery</p>
              <p class="font-medium">{{ viewModal.po.expected_delivery ? fmtDate(viewModal.po.expected_delivery) : '—' }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Status</p>
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="statusClass(viewModal.po.status)">
                {{ viewModal.po.status }}
              </span>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Payment Method</p>
              <p class="font-medium capitalize">{{ viewModal.po.payment_method ?? '—' }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Supplier Ref</p>
              <p class="font-medium">{{ viewModal.po.supplier_ref ?? '—' }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Total (LKR)</p>
              <p class="font-bold text-blue-700">{{ numFmt(viewModal.po.total) }}</p>
            </div>
          </div>
          <table class="min-w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-left">Product</th>
                <th class="px-3 py-2 text-right">Ordered</th>
                <th class="px-3 py-2 text-right">Received</th>
                <th class="px-3 py-2 text-right">Unit Cost</th>
                <th class="px-3 py-2 text-right">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="item in viewModal.po.items" :key="item.id">
                <td class="px-3 py-2 font-medium">{{ item.product?.name }}</td>
                <td class="px-3 py-2 text-right">{{ item.ordered_quantity ?? item.quantity }}</td>
                <td class="px-3 py-2 text-right">
                  <span :class="(item.received_quantity ?? 0) > 0 ? 'text-green-700 font-semibold' : 'text-gray-400'">
                    {{ item.received_quantity ?? 0 }}
                  </span>
                </td>
                <td class="px-3 py-2 text-right">{{ numFmt(item.unit_cost) }}</td>
                <td class="px-3 py-2 text-right font-semibold">{{ numFmt(item.total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="px-6 py-4 border-t flex justify-between">
          <button v-if="['ordered','pending'].includes(viewModal.po?.status)"
            @click="openReceive(viewModal.po); viewModal.open = false"
            class="btn-primary">Receive Goods (GRN)</button>
          <button @click="viewModal.open = false" class="px-4 py-2 rounded-lg border text-gray-600 hover:bg-gray-50">Close</button>
        </div>
      </div>
    </div>

    <!-- ── RECEIVE / GRN MODAL ── -->
    <div v-if="receiveModal.open" class="fixed inset-0 z-50 flex items-start justify-center bg-black/40 overflow-y-auto py-8">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <div>
            <h3 class="text-lg font-semibold">Receive Goods — {{ receiveModal.po?.purchase_number }}</h3>
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
                  <th class="px-3 py-2 text-right w-24">Recv Qty</th>
                  <th class="px-3 py-2 text-right w-28">Unit Cost</th>
                  <th class="px-3 py-2 text-right w-28">Selling Price</th>
                  <th class="px-3 py-2 text-left w-28">Batch No.</th>
                  <th class="px-3 py-2 text-left w-32">Expiry</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="row in receiveModal.items" :key="row.id">
                  <td class="px-3 py-2 font-medium text-gray-800">{{ row.product?.name }}</td>
                  <td class="px-3 py-2 text-right text-gray-500">{{ row.ordered_quantity ?? row.quantity }}</td>
                  <td class="px-3 py-2">
                    <input v-model.number="row._qty" type="number" min="0" :max="row.ordered_quantity ?? row.quantity"
                      class="form-input text-right w-full" />
                  </td>
                  <td class="px-3 py-2">
                    <input v-model.number="row._cost" type="number" min="0" step="0.01" class="form-input text-right w-full" />
                  </td>
                  <td class="px-3 py-2">
                    <input v-model.number="row._selling_price" type="number" min="0" step="0.01"
                      class="form-input text-right w-full" placeholder="0.00" />
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
            {{ receiveModal.submitting ? 'Saving…' : 'Confirm Receipt (GRN)' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
import axios from 'axios'
import SearchableSelect from '@/components/SearchableSelect.vue'

const loading        = ref(false)
const purchases      = ref([])
const suppliers      = ref([])
const products       = ref([])
const partCategories = ref([])
const vehicleTypes   = ref([])
const brands         = ref([])
const vehicleModels  = ref([])
const qualityTypes   = ref([])

const activeTab  = ref('all')
const page       = ref(1)
const meta       = ref({ current_page: 1, last_page: 1, total: 0 })
const showNewPO  = ref(false)
const submitting = ref(false)
const formError  = ref('')

const statusTabs = [
  { label: 'All Orders',  value: 'all' },
  { label: 'Ordered',     value: 'ordered' },
  { label: 'Pending',     value: 'pending' },
  { label: 'Cancelled',   value: 'cancelled' },
]

const viewModal    = reactive({ open: false, po: null })
const receiveModal = reactive({ open: false, po: null, items: [], notes: '', error: '', submitting: false })

const defaultNewProduct = () => ({
  name: '', sku: '', part_category_id: '', vehicle_type_id: '',
  brand_id: '', model_id: '', quality_type_id: '', rack_location: '', min_stock_level: 1,
})

const defaultItem = () => ({
  is_new: false,
  product_id: '',
  new_product: defaultNewProduct(),
  ordered_quantity: 1,
  unit_cost: 0,
})

const form = reactive({
  supplier_id: '', supplier_ref: '', order_date: new Date().toISOString().slice(0, 10),
  expected_delivery: '', payment_method: 'credit', cheque_number: '', cheque_date: '',
  cheque_bank_name: '', notes: '', items: [defaultItem()],
})

const poTotal = computed(() =>
  form.items.reduce((s, i) => s + (i.unit_cost || 0) * (i.ordered_quantity || 0), 0)
)

const productOptions = computed(() => products.value.map(p => ({ ...p, sub: p.sku })))

function addItem() { form.items.push(defaultItem()) }

function prefill(item) {
  const p = products.value.find(x => x.id === item.product_id)
  if (p) item.unit_cost = p.purchase_price ?? 0
}

async function fetchPOs() {
  loading.value = true
  const params = { page: page.value, per_page: 20 }
  if (activeTab.value !== 'all') params.status = activeTab.value
  else params['statuses[]'] = ['ordered', 'pending', 'cancelled']
  const { data } = await axios.get('/api/purchases', { params })
  purchases.value = data.data
  meta.value      = data.meta ?? { current_page: 1, last_page: 1, total: data.total }
  loading.value   = false
}

async function fetchSupport() {
  const [sup, prod, cats, vt, br, vm, qt] = await Promise.all([
    axios.get('/api/suppliers',      { params: { per_page: 500 } }),
    axios.get('/api/products',       { params: { per_page: 1000 } }),
    axios.get('/api/part-categories',{ params: { per_page: 500 } }),
    axios.get('/api/vehicle-types',  { params: { per_page: 500 } }),
    axios.get('/api/brands',         { params: { per_page: 500 } }),
    axios.get('/api/vehicle-models', { params: { per_page: 500 } }),
    axios.get('/api/quality-types',  { params: { per_page: 500 } }),
  ])
  suppliers.value     = sup.data.data  ?? sup.data
  products.value      = prod.data.data ?? prod.data
  partCategories.value = cats.data.data ?? cats.data
  vehicleTypes.value   = vt.data.data  ?? vt.data
  brands.value         = br.data.data  ?? br.data
  vehicleModels.value  = vm.data.data  ?? vm.data
  qualityTypes.value   = qt.data.data  ?? qt.data
}

function openNewPO() {
  Object.assign(form, {
    supplier_id: '', supplier_ref: '',
    order_date: new Date().toISOString().slice(0, 10),
    expected_delivery: '', payment_method: 'credit',
    cheque_number: '', cheque_date: '', cheque_bank_name: '',
    notes: '', items: [defaultItem()],
  })
  formError.value = ''
  showNewPO.value = true
}

async function submitPO() {
  formError.value = ''
  if (!form.supplier_id) { formError.value = 'Please select a supplier.'; return }
  for (const i of form.items) {
    if (!i.is_new && !i.product_id)               { formError.value = 'Select an existing product or switch to New Part.'; return }
    if (i.is_new  && !i.new_product.name?.trim()) { formError.value = 'Enter a part name for all new items.'; return }
  }
  submitting.value = true
  try {
    await axios.post('/api/purchases', {
      supplier_id:       form.supplier_id,
      supplier_ref:      form.supplier_ref || null,
      order_date:        form.order_date,
      expected_delivery: form.expected_delivery || null,
      payment_method:    form.payment_method,
      cheque_number:     form.cheque_number || null,
      cheque_date:       form.cheque_date || null,
      cheque_bank_name:  form.cheque_bank_name || null,
      notes:             form.notes || null,
      status:            'ordered',
      tax:               0,
      items: form.items.map(i => i.is_new
        ? {
            new_product: {
              name:             i.new_product.name.trim(),
              sku:              i.new_product.sku?.trim() || null,
              part_category_id: i.new_product.part_category_id || null,
              vehicle_type_id:  i.new_product.vehicle_type_id  || null,
              brand_id:         i.new_product.brand_id          || null,
              model_id:         i.new_product.model_id          || null,
              quality_type_id:  i.new_product.quality_type_id   || null,
              rack_location:    i.new_product.rack_location?.trim() || null,
              min_stock_level:  i.new_product.min_stock_level   || 1,
            },
            ordered_quantity: i.ordered_quantity,
            unit_cost:        i.unit_cost,
            selling_price:    0,
          }
        : {
            product_id:       i.product_id,
            ordered_quantity: i.ordered_quantity,
            unit_cost:        i.unit_cost,
            selling_price:    0,
          }
      ),
    })
    showNewPO.value = false
    fetchPOs()
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to create purchase order.'
  } finally {
    submitting.value = false
  }
}

async function viewPO(po) {
  const { data } = await axios.get(`/api/purchases/${po.id}`)
  viewModal.po   = data
  viewModal.open = true
}

function openReceive(po) {
  receiveModal.po    = po
  receiveModal.items = (po.items ?? []).map(i => ({
    ...i,
    _qty:           i.ordered_quantity ?? i.quantity,
    _cost:          i.unit_cost,
    _selling_price: i.selling_price ?? 0,
    _batch:         i.batch_number ?? '',
    _expiry:        i.expiry_date ?? '',
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
        id:            i.id,
        quantity:      i._qty,
        unit_cost:     i._cost,
        selling_price: i._selling_price || 0,
        batch_number:  i._batch  || null,
        expiry_date:   i._expiry || null,
      })),
    })
    receiveModal.open = false
    fetchPOs()
  } catch (e) {
    receiveModal.error = e.response?.data?.message ?? 'Failed to receive goods.'
  } finally {
    receiveModal.submitting = false
  }
}

async function cancelPO(po) {
  if (!confirm(`Cancel ${po.purchase_number}?`)) return
  try {
    await axios.patch(`/api/purchases/${po.id}/cancel`)
    fetchPOs()
  } catch {
    alert('Could not cancel this purchase order.')
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

onMounted(() => { fetchPOs(); fetchSupport() })
</script>
