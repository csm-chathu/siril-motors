<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <input v-model="search" type="search" placeholder="Search parts…" class="form-input w-64" @input="debouncedFetch" />
        <SearchableSelect v-model="partCategoryFilter" :options="partCategories"
          placeholder="All categories" class="w-44" @update:modelValue="fetchProducts" />
        <SearchableSelect v-model="vehicleTypeFilter" :options="vehicleTypes"
          placeholder="All vehicles" class="w-36" @update:modelValue="fetchProducts" />
        <label class="flex items-center gap-1 text-sm text-gray-600 cursor-pointer">
          <input type="checkbox" v-model="lowStockOnly" @change="fetchProducts" class="rounded text-blue-600" />
          Low stock only
        </label>
      </div>
      <button @click="openCreate" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> Add Part
      </button>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="table-th">Part Number</th>
              <th class="table-th">Part Name</th>
              <th class="table-th">Part Category</th>
              <th class="table-th">Vehicle / Brand / Model</th>
              <th class="table-th">Quality</th>
              <th class="table-th">Rack</th>
              <th class="table-th">Stock</th>
              <th class="table-th">Buy Price</th>
              <th class="table-th">Sell Price</th>
              <th class="table-th">Status</th>
              <th class="table-th">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="p in products.data" :key="p.id" class="hover:bg-gray-50">
              <td class="table-td font-mono text-xs">{{ p.sku }}</td>
              <td class="table-td">
                <div>
                  <span class="font-medium">{{ p.name }}</span>
                  <p v-if="p.image" class="text-xs text-gray-400 font-mono">{{ p.image.split('/').pop() }}</p>
                </div>
              </td>
              <td class="table-td text-gray-500">{{ p.part_category?.name || '—' }}</td>
              <td class="table-td text-xs text-gray-600">
                <span v-if="p.vehicle_type">{{ p.vehicle_type.name }}</span>
                <span v-if="p.brand"> · {{ p.brand.name }}</span>
                <span v-if="p.model"> · {{ p.model.name }}</span>
                <span v-if="!p.vehicle_type && !p.brand && !p.model">—</span>
              </td>
              <td class="table-td text-xs text-gray-600">{{ p.quality_type?.name || '—' }}</td>
              <td class="table-td text-xs font-mono text-gray-500">{{ p.rack_location || '—' }}</td>
              <td class="table-td">
                <span :class="p.stock_quantity <= p.min_stock_level ? 'badge bg-red-100 text-red-700' : 'badge bg-green-100 text-green-700'">
                  {{ p.stock_quantity }}
                </span>
              </td>
              <td class="table-td">LKR {{ Number(p.purchase_price).toLocaleString() }}</td>
              <td class="table-td font-semibold text-blue-700">LKR {{ Number(p.selling_price).toLocaleString() }}</td>
              <td class="table-td">
                <span :class="p.is_active ? 'badge bg-green-100 text-green-700' : 'badge bg-gray-100 text-gray-500'">
                  {{ p.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="table-td">
                <div class="flex items-center gap-2">
                  <div class="inline-flex items-center rounded-md overflow-hidden border border-emerald-200">
                    <input type="number" min="1" max="100"
                      :value="printQty[p.id] ?? 1"
                      @change="printQty[p.id] = Math.max(1, Math.min(100, Number($event.target.value)))"
                      class="w-10 text-center text-xs py-1 border-none outline-none bg-white text-gray-700 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                    <button @click="reprintBarcode(p)" :disabled="printingId === p.id"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium bg-emerald-100 text-emerald-700 hover:bg-emerald-200 whitespace-nowrap disabled:opacity-60">
                      <svg v-if="printingId === p.id" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                      </svg>
                      <PrinterIcon v-else class="w-3.5 h-3.5" />
                      {{ printingId === p.id ? 'Printing…' : 'Print' }}
                    </button>
                  </div>
                  <button @click="openEdit(p)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                    <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
                  </button>
                  <button @click="deleteProduct(p)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                    <TrashIcon class="w-3.5 h-3.5" /> Delete
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!products.data?.length">
              <td colspan="11" class="table-td text-center text-gray-400 py-8">No parts found</td>
            </tr>
          </tbody>
        </table>
      </div>
      <!-- Pagination -->
      <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
        <span>{{ products.from }}–{{ products.to }} of {{ products.total }}</span>
        <div class="flex gap-2">
          <button @click="page--; fetchProducts()" :disabled="page <= 1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Prev</button>
          <button @click="page++; fetchProducts()" :disabled="page >= products.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <ProductModal
      v-if="showModal"
      :product="editing"
      :suppliers="suppliers"
      :vehicle-types="vehicleTypes"
      :brands="brands"
      :vehicle-models="vehicleModels"
      :part-categories="partCategories"
      :part-brands="partBrands"
      :quality-types="qualityTypes"
      @close="showModal = false"
      @saved="onSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { PencilSquareIcon, PlusIcon, PrinterIcon, TrashIcon } from '@heroicons/vue/24/outline'
import JsBarcode from 'jsbarcode'
import ProductModal from '@/components/ProductModal.vue'
import SearchableSelect from '@/components/SearchableSelect.vue'

const products         = ref({ data: [] })
const suppliers        = ref([])
const vehicleTypes     = ref([])
const brands           = ref([])
const vehicleModels    = ref([])
const partCategories   = ref([])
const partBrands       = ref([])
const qualityTypes     = ref([])
const search           = ref('')
const partCategoryFilter = ref('')
const vehicleTypeFilter  = ref('')
const lowStockOnly     = ref(false)
const page             = ref(1)
const showModal        = ref(false)
const printingId       = ref(null)
const printQty         = ref({})
const editing          = ref(null)

let debounceTimer = null
function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => { page.value = 1; fetchProducts() }, 400)
}

async function fetchProducts() {
  const params = {
    page: page.value,
    search: search.value,
    part_category_id: partCategoryFilter.value,
    vehicle_type_id:  vehicleTypeFilter.value,
  }
  if (lowStockOnly.value) params.low_stock = 1
  const { data } = await axios.get('/api/products', { params })
  products.value = data
}

async function fetchRefs() {
  const [s, vt, b, vm, pc, pb, qt] = await Promise.all([
    axios.get('/api/suppliers/all'),
    axios.get('/api/vehicle-types'),
    axios.get('/api/brands'),
    axios.get('/api/vehicle-models'),
    axios.get('/api/part-categories'),
    axios.get('/api/part-brands'),
    axios.get('/api/quality-types'),
  ])
  suppliers.value      = s.data
  vehicleTypes.value   = vt.data
  brands.value         = b.data
  vehicleModels.value  = vm.data
  partCategories.value = pc.data
  partBrands.value     = pb.data
  qualityTypes.value   = qt.data
}

function openCreate() { editing.value = null; showModal.value = true }
function openEdit(p)   { editing.value = p;    showModal.value = true }

function createBarcodeSvg(value) {
  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg')
  JsBarcode(svg, value, {
    format: 'CODE128',
    width: 2,
    height: 60,
    margin: 0,
    marginLeft: 3,
    marginRight: 3,
    displayValue: false,
  })
  svg.setAttribute('preserveAspectRatio', 'none')
  return svg.outerHTML
}

function printProductBarcode(product, qty = 1) {
  if (!product?.sku) return
  const barcodeValue = product.barcode?.trim() || product.sku
  const barcodeSvg   = createBarcodeSvg(barcodeValue)
  const safeName     = (product.name ?? '').replace(/</g, '&lt;').replace(/>/g, '&gt;')
  const safeBarcode  = barcodeValue.replace(/</g, '&lt;').replace(/>/g, '&gt;')
  const safeVehicleType = (product.vehicle_type?.name ?? '').replace(/</g, '&lt;').replace(/>/g, '&gt;')
  const safeModel       = (product.model?.name ?? '').replace(/</g, '&lt;').replace(/>/g, '&gt;')
  const brandModel      = [safeVehicleType, safeModel].filter(Boolean).join(' · ')
  const safePrice    = Number(product.selling_price).toLocaleString('en-LK', { minimumFractionDigits: 2 })

  const labelHtml = `
  <div class="label">
    <div class="name">${safeName}</div>
    ${brandModel ? `<div class="brand-model">${brandModel}</div>` : ''}
    ${barcodeSvg}
    <div class="sku">${safeBarcode}</div>
    <div class="price">LKR ${safePrice}</div>
  </div>`

  const html = `<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    @media print { @page { size: 1.181in 0.787in landscape; margin: 0; } }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { width: 30mm; background: #fff;
      font-family: Arial, Helvetica, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .label { width: 30mm; height: 20mm; padding: 2mm 1.5mm 0.5mm;
      display: flex; flex-direction: column; align-items: center; justify-content: space-between; overflow: hidden;
      page-break-after: always; }
    .name { font-size: 6.5pt; font-weight: 700; white-space: nowrap; overflow: hidden;
      text-overflow: ellipsis; width: 100%; text-align: center; line-height: 1; flex-shrink: 0; }
    .brand-model { font-size: 5.5pt; color: #555; white-space: nowrap; overflow: hidden;
      text-overflow: ellipsis; width: 100%; text-align: center; line-height: 1.2; flex-shrink: 0; }
    svg { width: 80%; height: 7mm; display: block; flex-shrink: 0; margin: 0 auto; }
    .sku { font-size: 7pt; font-weight: 700; letter-spacing: 1px; text-align: center; margin-top: 0.3mm; line-height: 1; }
    .price { font-size: 7pt; font-weight: 700; text-align: center; line-height: 1; margin-top: 0.5mm; }
  </style>
</head>
<body>
  ${Array(qty).fill(labelHtml).join('')}
</body>
</html>`

  if (window.electronAPI?.printBarcode) {
    window.electronAPI.printBarcode(html)
    return
  }

  const iframe = document.createElement('iframe')
  iframe.style.cssText = 'position:fixed;top:0;left:0;width:1px;height:1px;opacity:0;border:none;'
  document.body.appendChild(iframe)
  iframe.contentDocument.open()
  iframe.contentDocument.write(html)
  iframe.contentDocument.close()
  iframe.contentWindow.addEventListener('load', () => {
    iframe.contentWindow.print()
    setTimeout(() => document.body.removeChild(iframe), 2000)
  })
}

function reprintBarcode(product) {
  printingId.value = product.id
  setTimeout(() => { printingId.value = null }, 3000)
  printProductBarcode(product, printQty.value[product.id] ?? 1)
}

async function deleteProduct(p) {
  if (!confirm(`Delete "${p.name}"?`)) return
  await axios.delete(`/api/products/${p.id}`)
  fetchProducts()
}

async function onSaved(payload) {
  showModal.value = false
  await fetchProducts()
  if (payload?.isNew && payload?.product) {
    printProductBarcode(payload.product)
  }
}

onMounted(() => { fetchProducts(); fetchRefs() })
</script>
