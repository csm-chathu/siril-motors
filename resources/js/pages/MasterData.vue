<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-xl font-semibold text-gray-800">Master Data</h2>
      <p class="text-sm text-gray-500 mt-0.5">Manage reference data: categories, vehicle types, brands, models, and quality grades</p>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="flex gap-1 overflow-x-auto">
        <button v-for="tab in tabs" :key="tab.id"
          @click="activeTab = tab.id"
          class="px-4 py-2.5 text-sm font-medium border-b-2 whitespace-nowrap transition-colors"
          :class="activeTab === tab.id
            ? 'border-blue-600 text-blue-700'
            : 'border-transparent text-gray-500 hover:text-gray-700'">
          {{ tab.label }}
          <span class="ml-1.5 text-xs px-1.5 py-0.5 rounded-full"
            :class="activeTab === tab.id ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'">
            {{ counts[tab.id] }}
          </span>
        </button>
      </nav>
    </div>

    <!-- Part Categories -->
    <div v-if="activeTab === 'part_categories'">
      <MasterTable
        title="Part Categories"
        :rows="partCategories"
        :columns="[{ key: 'name', label: 'Name' }, { key: 'description', label: 'Description' }]"
        :loading="loading.part_categories"
        @add="openModal('part_categories')"
        @edit="r => openModal('part_categories', r)"
        @delete="r => deleteRow('part_categories', r)"
      />
    </div>

    <!-- Part Brands -->
    <div v-if="activeTab === 'part_brands'">
      <MasterTable
        title="Part Brands"
        :rows="partBrands"
        :columns="[{ key: 'name', label: 'Brand Name' }, { key: 'description', label: 'Description' }]"
        :loading="loading.part_brands"
        @add="openModal('part_brands')"
        @edit="r => openModal('part_brands', r)"
        @delete="r => deleteRow('part_brands', r)"
      />
    </div>

    <!-- Quality Types -->
    <div v-if="activeTab === 'quality_types'">
      <MasterTable
        title="Quality Grades"
        :rows="qualityTypes"
        :columns="[{ key: 'name', label: 'Name' }, { key: 'description', label: 'Description' }]"
        :loading="loading.quality_types"
        @add="openModal('quality_types')"
        @edit="r => openModal('quality_types', r)"
        @delete="r => deleteRow('quality_types', r)"
      />
    </div>

    <!-- Vehicle Types -->
    <div v-if="activeTab === 'vehicle_types'">
      <MasterTable
        title="Vehicle Types"
        :rows="vehicleTypes"
        :columns="[{ key: 'name', label: 'Name' }, { key: 'description', label: 'Description' }]"
        :loading="loading.vehicle_types"
        @add="openModal('vehicle_types')"
        @edit="r => openModal('vehicle_types', r)"
        @delete="r => deleteRow('vehicle_types', r)"
      />
    </div>

    <!-- Brands -->
    <div v-if="activeTab === 'brands'">
      <MasterTable
        title="Brands"
        :rows="brands"
        :columns="[{ key: 'name', label: 'Brand Name' }, { key: 'origin_country', label: 'Country' }, { key: 'description', label: 'Description' }]"
        :loading="loading.brands"
        @add="openModal('brands')"
        @edit="r => openModal('brands', r)"
        @delete="r => deleteRow('brands', r)"
      />
    </div>

    <!-- Vehicle Models -->
    <div v-if="activeTab === 'vehicle_models'">
      <!-- Filters -->
      <div class="flex gap-3 mb-4">
        <SearchableSelect v-model="modelFilters.vehicle_type_id" :options="vehicleTypes"
          placeholder="All Vehicle Types" class="w-48"
          @update:modelValue="modelFilters.brand_id = ''" />
        <SearchableSelect v-model="modelFilters.brand_id" :options="brands"
          placeholder="All Brands" class="w-48" />
      </div>

      <div class="card p-0 overflow-hidden">
        <div class="px-5 py-3 border-b bg-gray-50 flex items-center justify-between">
          <h3 class="font-semibold text-gray-700">Vehicle Models
            <span class="text-sm font-normal text-gray-400 ml-1">({{ filteredModels.length }})</span>
          </h3>
          <button @click="openModal('vehicle_models')" class="btn-primary text-sm py-1.5 px-3">+ Add Model</button>
        </div>
        <div v-if="loading.vehicle_models" class="py-12 text-center text-gray-400 text-sm">Loading…</div>
        <table v-else class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Model Name</th>
              <th class="table-th">Vehicle Type</th>
              <th class="table-th">Brand</th>
              <th class="table-th">Year Range</th>
              <th class="table-th"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="!filteredModels.length">
              <td colspan="5" class="table-td text-center text-gray-400">No models found</td>
            </tr>
            <tr v-for="m in filteredModels" :key="m.id" class="hover:bg-gray-50">
              <td class="table-td font-medium">{{ m.name }}</td>
              <td class="table-td text-sm text-gray-500">{{ m.vehicle_type?.name ?? '—' }}</td>
              <td class="table-td text-sm text-gray-500">{{ m.brand?.name ?? '—' }}</td>
              <td class="table-td text-sm text-gray-500">
                {{ m.year_from && m.year_to ? `${m.year_from} – ${m.year_to}` : (m.year_from || '—') }}
              </td>
              <td class="table-td">
                <div class="flex justify-end gap-1.5">
                  <button @click="openModal('vehicle_models', m)"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-medium bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors">
                    <PencilIcon class="w-3 h-3" /> Edit
                  </button>
                  <button @click="deleteRow('vehicle_models', m)"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-medium bg-red-50 text-red-600 hover:bg-red-100 transition-colors">
                    <TrashIcon class="w-3 h-3" /> Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="modal.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h3 class="font-semibold text-gray-800">{{ modal.row ? 'Edit' : 'Add' }} {{ modalTitle }}</h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form @submit.prevent="saveRow" class="p-6 space-y-4">

          <!-- Common: Name -->
          <div>
            <label class="form-label">Name *</label>
            <input v-model="modalForm.name" required class="form-input" :placeholder="namePlaceholder" />
          </div>

          <!-- Brands extra: country -->
          <div v-if="modal.type === 'brands'">
            <label class="form-label">Origin Country</label>
            <input v-model="modalForm.origin_country" class="form-input" placeholder="e.g. Japan" />
          </div>

          <!-- Vehicle Models extras -->
          <template v-if="modal.type === 'vehicle_models'">
            <div>
              <label class="form-label">Vehicle Type *</label>
              <SearchableSelect v-model="modalForm.vehicle_type_id" :options="vehicleTypes" placeholder="— Select Vehicle Type —" />
            </div>
            <div>
              <label class="form-label">Brand *</label>
              <SearchableSelect v-model="modalForm.brand_id" :options="brands" placeholder="— Select Brand —" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="form-label">Year From</label>
                <input v-model.number="modalForm.year_from" type="number" min="1900" :max="currentYear" class="form-input" placeholder="e.g. 2000" />
              </div>
              <div>
                <label class="form-label">Year To</label>
                <input v-model.number="modalForm.year_to" type="number" min="1900" :max="currentYear + 5" class="form-input" placeholder="e.g. 2010" />
              </div>
            </div>
          </template>

          <!-- Common: Description -->
          <div v-if="modal.type !== 'vehicle_models'">
            <label class="form-label">Description</label>
            <textarea v-model="modalForm.description" rows="2" class="form-input"></textarea>
          </div>

          <p v-if="modal.error" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ modal.error }}</p>

          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="modal.saving" class="btn-primary">
              {{ modal.saving ? 'Saving…' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete confirmation -->
    <div v-if="deleteConfirm.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6 space-y-4">
        <h3 class="font-semibold text-gray-800">Confirm Delete</h3>
        <p class="text-sm text-gray-600">Delete <strong>{{ deleteConfirm.row?.name }}</strong>? This cannot be undone.</p>
        <p v-if="deleteConfirm.error" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ deleteConfirm.error }}</p>
        <div class="flex justify-end gap-3">
          <button @click="deleteConfirm.open = false" class="btn-secondary">Cancel</button>
          <button @click="confirmDelete" :disabled="deleteConfirm.saving" class="btn-danger">
            {{ deleteConfirm.saving ? 'Deleting…' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import { PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import SearchableSelect from '@/components/SearchableSelect.vue'
import MasterTable from '@/components/MasterTable.vue'

// ── state ──────────────────────────────────────────────────
const tabs = [
  { id: 'part_categories', label: 'Part Categories' },
  { id: 'part_brands',     label: 'Part Brands' },
  { id: 'quality_types',   label: 'Quality Grades' },
  { id: 'vehicle_types',   label: 'Vehicle Types' },
  { id: 'brands',          label: 'Vehicle Brands' },
  { id: 'vehicle_models',  label: 'Vehicle Models' },
]

const activeTab = ref('part_categories')
const partCategories = ref([])
const partBrands     = ref([])
const qualityTypes   = ref([])
const vehicleTypes   = ref([])
const brands         = ref([])
const vehicleModels  = ref([])

const loading = reactive({
  part_categories: false,
  part_brands:     false,
  quality_types:   false,
  vehicle_types:   false,
  brands:          false,
  vehicle_models:  false,
})

const modelFilters = reactive({ vehicle_type_id: '', brand_id: '' })

const currentYear = new Date().getFullYear()

const counts = computed(() => ({
  part_categories: partCategories.value.length,
  part_brands:     partBrands.value.length,
  quality_types:   qualityTypes.value.length,
  vehicle_types:   vehicleTypes.value.length,
  brands:          brands.value.length,
  vehicle_models:  vehicleModels.value.length,
}))

const filteredModels = computed(() => vehicleModels.value.filter(m => {
  if (modelFilters.vehicle_type_id && m.vehicle_type_id != modelFilters.vehicle_type_id) return false
  if (modelFilters.brand_id && m.brand_id != modelFilters.brand_id) return false
  return true
}))

// ── modal ──────────────────────────────────────────────────
const modal = reactive({
  open: false, type: '', row: null, saving: false, error: '',
})
const modalForm = reactive({
  name: '', description: '', origin_country: '',
  vehicle_type_id: '', brand_id: '', year_from: '', year_to: '',
})

const modalTitleMap = {
  part_categories: 'Part Category',
  part_brands:     'Part Brand',
  quality_types:   'Quality Grade',
  vehicle_types:   'Vehicle Type',
  brands:          'Vehicle Brand',
  vehicle_models:  'Vehicle Model',
}
const namePlaceholderMap = {
  part_categories: 'e.g. Cooling Parts',
  part_brands:     'e.g. Bosch',
  quality_types:   'e.g. Genuine',
  vehicle_types:   'e.g. Car',
  brands:          'e.g. Toyota',
  vehicle_models:  'e.g. Corolla',
}
const modalTitle      = computed(() => modalTitleMap[modal.type] ?? '')
const namePlaceholder = computed(() => namePlaceholderMap[modal.type] ?? '')

function openModal(type, row = null) {
  modal.type  = type
  modal.row   = row
  modal.error = ''
  Object.assign(modalForm, {
    name: row?.name ?? '',
    description: row?.description ?? '',
    origin_country: row?.origin_country ?? '',
    vehicle_type_id: row?.vehicle_type_id ?? '',
    brand_id: row?.brand_id ?? '',
    year_from: row?.year_from ?? '',
    year_to:   row?.year_to ?? '',
  })
  modal.open = true
}

function closeModal() {
  modal.open = false
}

const apiPath = {
  part_categories: '/api/part-categories',
  part_brands:     '/api/part-brands',
  quality_types:   '/api/quality-types',
  vehicle_types:   '/api/vehicle-types',
  brands:          '/api/brands',
  vehicle_models:  '/api/vehicle-models',
}

async function saveRow() {
  modal.saving = true; modal.error = ''
  try {
    const payload = buildPayload(modal.type)
    if (modal.row) {
      const { data } = await axios.put(`${apiPath[modal.type]}/${modal.row.id}`, payload)
      updateLocal(modal.type, data)
    } else {
      const { data } = await axios.post(apiPath[modal.type], payload)
      pushLocal(modal.type, data)
    }
    closeModal()
  } catch (e) {
    modal.error = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'Save failed'
  } finally { modal.saving = false }
}

function buildPayload(type) {
  if (type === 'vehicle_models') {
    return {
      name: modalForm.name,
      vehicle_type_id: modalForm.vehicle_type_id,
      brand_id: modalForm.brand_id,
      year_from: modalForm.year_from || null,
      year_to:   modalForm.year_to   || null,
    }
  }
  if (type === 'brands') {
    return { name: modalForm.name, description: modalForm.description, origin_country: modalForm.origin_country }
  }
  return { name: modalForm.name, description: modalForm.description }
}

function listRef(type) {
  return { part_categories: partCategories, part_brands: partBrands, quality_types: qualityTypes, vehicle_types: vehicleTypes, brands, vehicle_models: vehicleModels }[type]
}

function updateLocal(type, data) {
  const list = listRef(type)
  const idx = list.value.findIndex(r => r.id === data.id)
  if (idx !== -1) list.value[idx] = data
}

function pushLocal(type, data) {
  listRef(type).value.push(data)
}

// ── delete ─────────────────────────────────────────────────
const deleteConfirm = reactive({ open: false, type: '', row: null, saving: false, error: '' })

function deleteRow(type, row) {
  Object.assign(deleteConfirm, { open: true, type, row, error: '', saving: false })
}

async function confirmDelete() {
  deleteConfirm.saving = true; deleteConfirm.error = ''
  try {
    await axios.delete(`${apiPath[deleteConfirm.type]}/${deleteConfirm.row.id}`)
    const list = listRef(deleteConfirm.type)
    list.value = list.value.filter(r => r.id !== deleteConfirm.row.id)
    deleteConfirm.open = false
  } catch (e) {
    deleteConfirm.error = e.response?.data?.message ?? 'Delete failed — item may be in use'
  } finally { deleteConfirm.saving = false }
}

// ── load ───────────────────────────────────────────────────
async function fetchAll() {
  await Promise.all([
    fetchList('part_categories', '/api/part-categories'),
    fetchList('part_brands',     '/api/part-brands'),
    fetchList('quality_types',   '/api/quality-types'),
    fetchList('vehicle_types',   '/api/vehicle-types'),
    fetchList('brands',          '/api/brands'),
    fetchList('vehicle_models',  '/api/vehicle-models'),
  ])
}

async function fetchList(key, url) {
  loading[key] = true
  try {
    const { data } = await axios.get(url)
    listRef(key).value = data
  } finally { loading[key] = false }
}

onMounted(fetchAll)
</script>
