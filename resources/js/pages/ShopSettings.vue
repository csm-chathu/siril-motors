<template>
  <div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between mb-2">
      <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
        <Cog6ToothIcon class="w-6 h-6 text-amber-500" /> Shop Settings
      </h2>
    </div>

    <div class="card space-y-5">
      <h3 class="font-semibold text-gray-700 border-b border-gray-100 pb-2">Business Information</h3>

      <!-- Logo upload -->
      <div>
        <label class="form-label">Shop Logo</label>
        <div class="flex items-center gap-4">
          <!-- Preview -->
          <div class="w-24 h-24 border-2 rounded-xl flex items-center justify-center bg-gray-50 shrink-0 overflow-hidden transition-colors"
            :class="dragOver ? 'border-amber-400 bg-amber-50' : 'border-dashed border-gray-200'"
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop.prevent="onDrop">
            <img v-if="form.logo_url" :src="form.logo_url" alt="Logo" class="w-full h-full object-contain p-1" />
            <PhotoIcon v-else class="w-8 h-8 text-gray-300" />
          </div>

          <div class="flex-1 space-y-2">
            <input ref="logoInput" type="file" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml" class="hidden" @change="onLogoChange" />

            <button type="button" @click="logoInput.click()" :disabled="logoUploading"
              class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60 transition-colors">
              <ArrowPathIcon v-if="logoUploading" class="w-4 h-4 animate-spin text-amber-500" />
              <ArrowUpTrayIcon v-else class="w-4 h-4" />
              {{ logoUploading ? 'Uploading to Cloudinary…' : 'Upload Image' }}
            </button>

            <!-- Progress bar -->
            <div v-if="logoUploading" class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full bg-amber-400 rounded-full animate-pulse" style="width:70%"></div>
            </div>

            <p class="text-xs text-gray-400">PNG, JPG, GIF, WebP or SVG · max 2 MB · drag &amp; drop supported</p>
            <p class="text-xs text-gray-400 flex items-center gap-1">
              <CloudIcon class="w-3.5 h-3.5" /> Stored on Cloudinary CDN
            </p>

            <div v-if="form.logo_url" class="flex items-center gap-2">
              <span class="text-xs text-green-600 flex items-center gap-1">
                <CheckCircleIcon class="w-3.5 h-3.5" /> Logo uploaded
              </span>
              <button type="button" @click="removeLogo" class="text-xs text-red-500 hover:text-red-700 underline">Remove</button>
            </div>

            <p v-if="logoError" class="text-xs text-red-500 flex items-center gap-1">
              <ExclamationTriangleIcon class="w-3.5 h-3.5" /> {{ logoError }}
            </p>
          </div>
        </div>
      </div>

      <div>
        <label class="form-label">Shop Name <span class="text-red-400">*</span></label>
        <input v-model="form.shop_name" type="text" placeholder="e.g. Siril Motors" class="form-input" maxlength="200" />
      </div>

      <div>
        <label class="form-label">Address</label>
        <textarea v-model="form.address" rows="3" placeholder="Street, City, Province" class="form-input resize-none"></textarea>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="form-label">Phone Number</label>
          <input v-model="form.phone" type="text" placeholder="+94 XX XXX XXXX" class="form-input" maxlength="50" />
        </div>
        <div>
          <label class="form-label">BR / Business Reg. No.</label>
          <input v-model="form.br_number" type="text" placeholder="BR/SP/XXXXXXXXXX" class="form-input" maxlength="100" />
        </div>
      </div>
    </div>

    <!-- Print Settings -->
    <div class="card space-y-4">
      <h3 class="font-semibold text-gray-700 border-b border-gray-100 pb-2">Print Settings</h3>
      <div>
        <label class="form-label">Default Print Format</label>
        <div class="grid grid-cols-2 gap-3 mt-1">
          <button type="button"
            @click="form.print_mode = 'pos'"
            :class="form.print_mode === 'pos'
              ? 'border-amber-500 bg-amber-50 text-amber-800 ring-2 ring-amber-400'
              : 'border-gray-200 hover:border-gray-300 text-gray-600'"
            class="flex flex-col items-center gap-2 p-4 border-2 rounded-xl transition-all text-sm font-medium">
            <PrinterIcon class="w-7 h-7" />
            <span>POS / Thermal</span>
            <span class="text-xs font-normal text-gray-400">76mm roll receipt</span>
          </button>
          <button type="button"
            @click="form.print_mode = 'a5'"
            :class="form.print_mode === 'a5'
              ? 'border-amber-500 bg-amber-50 text-amber-800 ring-2 ring-amber-400'
              : 'border-gray-200 hover:border-gray-300 text-gray-600'"
            class="flex flex-col items-center gap-2 p-4 border-2 rounded-xl transition-all text-sm font-medium">
            <DocumentTextIcon class="w-7 h-7" />
            <span>A5 Paper Invoice</span>
            <span class="text-xs font-normal text-gray-400">148 × 210mm full bill</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Save -->
    <div class="flex items-center gap-3">
      <button @click="save" :disabled="saving"
        class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-600 disabled:opacity-60 text-white rounded-lg font-medium transition-colors">
        <ArrowPathIcon v-if="saving" class="w-4 h-4 animate-spin" />
        <CheckCircleIcon v-else class="w-4 h-4" />
        {{ saving ? 'Saving…' : 'Save Settings' }}
      </button>
      <span v-if="saved" class="text-sm text-green-600 flex items-center gap-1">
        <CheckCircleIcon class="w-4 h-4" /> Saved!
      </span>
      <span v-if="error" class="text-sm text-red-600">{{ error }}</span>
    </div>

    <!-- Database Backup -->
    <div class="card space-y-3">
      <h3 class="font-semibold text-gray-700 border-b border-gray-100 pb-2 flex items-center gap-2">
        <CircleStackIcon class="w-4 h-4 text-gray-500" /> Database Backup
      </h3>
      <p class="text-sm text-gray-500">
        Download a full SQL dump of the database. Keep this file in a safe place — it can be used to restore all data.
      </p>
      <div class="flex items-center gap-3">
        <button @click="downloadBackup" :disabled="backingUp"
          class="inline-flex items-center gap-2 px-4 py-2 bg-gray-800 hover:bg-gray-900 disabled:opacity-60 text-white rounded-lg text-sm font-medium transition-colors">
          <ArrowDownTrayIcon v-if="!backingUp" class="w-4 h-4" />
          <ArrowPathIcon v-else class="w-4 h-4 animate-spin" />
          {{ backingUp ? 'Preparing…' : 'Download Backup (.sql)' }}
        </button>
        <span v-if="backupError" class="text-sm text-red-600 flex items-center gap-1">
          <ExclamationTriangleIcon class="w-4 h-4" /> {{ backupError }}
        </span>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import {
  Cog6ToothIcon, PrinterIcon, DocumentTextIcon,
  ArrowPathIcon, CheckCircleIcon, PhotoIcon, ArrowUpTrayIcon,
  CloudIcon, ExclamationTriangleIcon, CircleStackIcon, ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline'

const form = ref({
  shop_name:  '',
  address:    '',
  phone:      '',
  br_number:  '',
  logo_url:   '',
  print_mode: 'pos',
})
const saving        = ref(false)
const saved         = ref(false)
const error         = ref('')
const logoInput     = ref(null)
const logoUploading = ref(false)
const logoError     = ref('')
const dragOver      = ref(false)

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/shop-settings')
    Object.keys(form.value).forEach(k => {
      if (data[k] !== undefined && data[k] !== null) form.value[k] = data[k]
    })
  } catch {
    // settings table may be empty on first load
  }
})

async function uploadFile(file) {
  if (!file) return
  const maxMb = 2
  if (file.size > maxMb * 1024 * 1024) {
    logoError.value = `File too large. Maximum size is ${maxMb} MB.`
    return
  }
  if (!file.type.startsWith('image/')) {
    logoError.value = 'Only image files are allowed.'
    return
  }
  logoError.value     = ''
  logoUploading.value = true
  try {
    const fd = new FormData()
    fd.append('logo', file)
    const { data } = await axios.post('/api/shop-settings/logo', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    form.value.logo_url = data.logo_url
  } catch (e) {
    logoError.value = e.response?.data?.message ?? 'Upload failed. Please try again.'
  } finally {
    logoUploading.value = false
  }
}

async function onLogoChange(e) {
  await uploadFile(e.target.files[0])
  e.target.value = ''
}

async function onDrop(e) {
  dragOver.value = false
  await uploadFile(e.dataTransfer.files[0])
}

function removeLogo() {
  form.value.logo_url = ''
}

const backingUp   = ref(false)
const backupError = ref('')

async function downloadBackup() {
  backingUp.value   = true
  backupError.value = ''
  try {
    const token = document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]
    const resp  = await fetch('/api/backup/download', {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        Accept: 'application/octet-stream',
      },
    })
    if (!resp.ok) {
      const json = await resp.json().catch(() => ({}))
      backupError.value = json.message ?? `Server error ${resp.status}`
      return
    }
    const filename = resp.headers.get('Content-Disposition')
      ?.match(/filename="([^"]+)"/)?.[1] ?? 'backup.sql'
    const blob = await resp.blob()
    const url  = URL.createObjectURL(blob)
    const a    = document.createElement('a')
    a.href     = url
    a.download = filename
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    backupError.value = 'Download failed: ' + e.message
  } finally {
    backingUp.value = false
  }
}

async function save() {
  saving.value = true
  saved.value  = false
  error.value  = ''
  try {
    await axios.post('/api/shop-settings', form.value)
    saved.value = true
    setTimeout(() => { saved.value = false }, 3000)
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to save settings.'
  } finally {
    saving.value = false
  }
}
</script>
