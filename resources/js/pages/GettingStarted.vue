<template>
  <div class="flex gap-0 h-full -m-6">

    <!-- Left sidebar: guide sections -->
    <aside class="w-56 shrink-0 bg-white border-r border-gray-200 flex flex-col">
      <div class="px-4 py-4 border-b border-gray-100">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ t('guideSections') }}</p>
      </div>
      <nav class="flex-1 overflow-y-auto py-2">
        <button
          v-for="section in sections"
          :key="section.id"
          @click="activeSection = section.id"
          class="w-full flex items-center gap-2.5 px-4 py-2.5 text-left text-sm transition-colors"
          :class="activeSection === section.id
            ? 'bg-blue-50 text-blue-700 font-semibold border-r-2 border-blue-600'
            : 'text-gray-600 hover:bg-gray-50'"
        >
          <span class="text-base leading-none">{{ section.icon }}</span>
          <span class="flex-1 leading-snug">{{ section.label[lang] }}</span>
          <span v-if="section.id === 'checklist'" class="text-xs font-medium px-1.5 py-0.5 rounded-full"
            :class="checklistDone === checklist.length ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'">
            {{ checklistDone }}/{{ checklist.length }}
          </span>
        </button>
      </nav>

      <!-- Language toggle -->
      <div class="px-4 py-3 border-t border-gray-100">
        <p class="text-xs text-gray-400 mb-1.5">Language / භාෂාව</p>
        <div class="flex rounded-lg overflow-hidden border border-gray-200">
          <button @click="lang = 'en'" :class="lang === 'en' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'"
            class="flex-1 py-1.5 text-xs font-medium transition-colors">English</button>
          <button @click="lang = 'si'" :class="lang === 'si' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'"
            class="flex-1 py-1.5 text-xs font-medium transition-colors">සිංහල</button>
        </div>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 overflow-y-auto bg-gray-50">
      <div class="max-w-3xl mx-auto px-8 py-8">

        <!-- ── GO-LIVE CHECKLIST ── -->
        <div v-if="activeSection === 'checklist'">
          <div class="flex items-center justify-between mb-1">
            <h2 class="text-2xl font-bold text-gray-900">{{ t('goLiveTitle') }}</h2>
            <button @click="resetAll" class="text-xs text-gray-400 hover:text-gray-600 underline">{{ t('resetAll') }}</button>
          </div>
          <p class="text-gray-500 mb-6">{{ t('goLiveSubtitle') }}</p>

          <!-- Progress bar -->
          <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
            <div class="flex justify-between mb-2">
              <span class="text-sm font-medium text-gray-700">{{ t('setupProgress') }}</span>
              <span class="text-sm font-semibold" :class="progressColor">{{ progressPercent }}%</span>
            </div>
            <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full rounded-full transition-all duration-500"
                :class="progressPercent === 100 ? 'bg-green-500' : 'bg-blue-500'"
                :style="{ width: progressPercent + '%' }"></div>
            </div>
          </div>

          <!-- Checklist groups -->
          <div v-for="group in checklist" :key="group.id" class="bg-white rounded-xl border border-gray-200 mb-4 overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100 bg-gray-50">
              <h3 class="font-semibold text-gray-800">{{ group.title[lang] }}</h3>
            </div>
            <div class="divide-y divide-gray-50">
              <label
                v-for="item in group.items"
                :key="item.id"
                class="flex items-start gap-3 px-5 py-3.5 cursor-pointer hover:bg-blue-50 transition-colors"
              >
                <input type="checkbox" v-model="checked[item.id]"
                  class="mt-0.5 w-4 h-4 rounded border-gray-300 text-blue-600 cursor-pointer shrink-0" />
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-800 leading-snug">{{ item.label[lang] }}</p>
                  <p class="text-xs text-gray-400 mt-0.5">{{ item.hint[lang] }}</p>
                </div>
                <router-link v-if="item.link" :to="item.link"
                  class="text-xs text-blue-600 hover:underline shrink-0 mt-0.5 whitespace-nowrap">
                  {{ t('goTo') }} →
                </router-link>
              </label>
            </div>
          </div>

          <div v-if="checklistDone === totalItems" class="bg-green-50 border border-green-200 rounded-xl p-5 text-center mt-2">
            <p class="text-green-700 font-semibold text-lg">🎉 {{ t('allDone') }}</p>
            <p class="text-green-600 text-sm mt-1">{{ t('allDoneSubtitle') }}</p>
          </div>
        </div>

        <!-- ── SHOP SETUP ── -->
        <div v-else-if="activeSection === 'shop-setup'">
          <h2 class="text-2xl font-bold text-gray-900 mb-1">{{ t('shopSetupTitle') }}</h2>
          <p class="text-gray-500 mb-6">{{ t('shopSetupSubtitle') }}</p>
          <div class="space-y-4">
            <GuideCard
              v-for="card in shopSetupCards"
              :key="card.id"
              :icon="card.icon"
              :title="card.title[lang]"
              :body="card.body[lang]"
              :link="card.link"
              :linkLabel="t('goTo')"
            />
          </div>
        </div>

        <!-- ── DAILY ROUTINE ── -->
        <div v-else-if="activeSection === 'daily-routine'">
          <h2 class="text-2xl font-bold text-gray-900 mb-1">{{ t('dailyTitle') }}</h2>
          <p class="text-gray-500 mb-6">{{ t('dailySubtitle') }}</p>
          <ol class="space-y-4">
            <li v-for="(step, i) in dailySteps" :key="i"
              class="flex gap-4 bg-white rounded-xl border border-gray-200 p-5">
              <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-bold shrink-0">{{ i + 1 }}</div>
              <div>
                <p class="font-semibold text-gray-800">{{ step.title[lang] }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ step.body[lang] }}</p>
              </div>
            </li>
          </ol>
        </div>

        <!-- ── FEATURES OVERVIEW ── -->
        <div v-else-if="activeSection === 'features'">
          <h2 class="text-2xl font-bold text-gray-900 mb-1">{{ t('featuresTitle') }}</h2>
          <p class="text-gray-500 mb-6">{{ t('featuresSubtitle') }}</p>
          <div class="grid grid-cols-2 gap-4">
            <div v-for="f in featureCards" :key="f.id"
              class="bg-white border border-gray-200 rounded-xl p-5">
              <p class="text-2xl mb-2">{{ f.icon }}</p>
              <p class="font-semibold text-gray-800">{{ f.title[lang] }}</p>
              <p class="text-sm text-gray-500 mt-1">{{ f.body[lang] }}</p>
            </div>
          </div>
        </div>

        <!-- ── PURCHASING GUIDE ── -->
        <div v-else-if="activeSection === 'purchasing'">
          <h2 class="text-2xl font-bold text-gray-900 mb-1">{{ t('purchasingTitle') }}</h2>
          <p class="text-gray-500 mb-6">{{ t('purchasingSubtitle') }}</p>
          <div class="space-y-4">
            <GuideCard
              v-for="card in purchasingCards"
              :key="card.id"
              :icon="card.icon"
              :title="card.title[lang]"
              :body="card.body[lang]"
              :link="card.link"
              :linkLabel="t('goTo')"
            />
          </div>
        </div>

        <!-- ── ACCOUNTING & GL ── -->
        <div v-else-if="activeSection === 'accounting'">
          <h2 class="text-2xl font-bold text-gray-900 mb-1">{{ t('accountingTitle') }}</h2>
          <p class="text-gray-500 mb-6">{{ t('accountingSubtitle') }}</p>
          <div class="space-y-4">
            <GuideCard
              v-for="card in accountingCards"
              :key="card.id"
              :icon="card.icon"
              :title="card.title[lang]"
              :body="card.body[lang]"
              :link="card.link"
              :linkLabel="t('goTo')"
            />
          </div>
        </div>

        <!-- ── HR & PAYROLL ── -->
        <div v-else-if="activeSection === 'hr'">
          <h2 class="text-2xl font-bold text-gray-900 mb-1">{{ t('hrTitle') }}</h2>
          <p class="text-gray-500 mb-6">{{ t('hrSubtitle') }}</p>
          <div class="space-y-4">
            <GuideCard
              v-for="card in hrCards"
              :key="card.id"
              :icon="card.icon"
              :title="card.title[lang]"
              :body="card.body[lang]"
              :link="card.link"
              :linkLabel="t('goTo')"
            />
          </div>
        </div>

        <!-- ── IMPORTANT WARNINGS ── -->
        <div v-else-if="activeSection === 'warnings'">
          <h2 class="text-2xl font-bold text-gray-900 mb-1">{{ t('warningsTitle') }}</h2>
          <p class="text-gray-500 mb-6">{{ t('warningsSubtitle') }}</p>
          <div class="space-y-4">
            <div v-for="w in warnings" :key="w.id"
              class="flex gap-4 border rounded-xl p-5"
              :class="w.level === 'danger' ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200'">
              <span class="text-2xl shrink-0">{{ w.icon }}</span>
              <div>
                <p class="font-semibold" :class="w.level === 'danger' ? 'text-red-800' : 'text-amber-800'">{{ w.title[lang] }}</p>
                <p class="text-sm mt-1" :class="w.level === 'danger' ? 'text-red-700' : 'text-amber-700'">{{ w.body[lang] }}</p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { RouterLink } from 'vue-router'
import GuideCard from '@/components/GuideCard.vue'

// ── Language ──────────────────────────────────────────────────────────────────
const lang = ref('en')

const strings = {
  en: {
    guideSections: 'Guide Sections',
    goLiveTitle: 'Go-Live Checklist',
    goLiveSubtitle: 'Tick off each item before you start using the system for real transactions.',
    setupProgress: 'Setup Progress',
    resetAll: 'Reset all',
    goTo: 'Go',
    allDone: 'You\'re all set!',
    allDoneSubtitle: 'Your shop is ready. Start using Siril Motors for daily transactions.',
    shopSetupTitle: 'Shop Setup',
    shopSetupSubtitle: 'Configure your business details, branding and system preferences.',
    dailyTitle: 'Daily Routine',
    dailySubtitle: 'Follow these steps at the start and end of every working day.',
    featuresTitle: 'Features Overview',
    featuresSubtitle: 'A quick look at everything Siril Motors can do for you.',
    purchasingTitle: 'Purchasing Guide',
    purchasingSubtitle: 'How to manage stock orders from purchase order to GRN to payment.',
    accountingTitle: 'Accounting & GL',
    accountingSubtitle: 'Understanding the double-entry ledger, journal entries and reports.',
    hrTitle: 'HR & Payroll',
    hrSubtitle: 'Manage employees, attendance and monthly salary payments.',
    warningsTitle: 'Important Warnings',
    warningsSubtitle: 'Things to be careful about to avoid losing data or causing errors.',
  },
  si: {
    guideSections: 'මාර්ගෝපදේශ කොටස්',
    goLiveTitle: 'ගෝ-ලයිව් පිරික්සුම් ලැයිස්තුව',
    goLiveSubtitle: 'සැබෑ ගනුදෙනු ආරම්භ කිරීමට පෙර සෑම අයිතමයක්ම ටික් කරන්න.',
    setupProgress: 'සකස් කිරීමේ ප්‍රගතිය',
    resetAll: 'සියල්ල යළි සකසන්න',
    goTo: 'යන්න',
    allDone: 'සියල්ල සූදානම්!',
    allDoneSubtitle: 'ඔබේ වෙළඳසැල සූදානම්. දෛනික ගනුදෙනු සඳහා Siril Motors භාවිතා කිරීම ආරම්භ කරන්න.',
    shopSetupTitle: 'වෙළඳසැල් සකස් කිරීම',
    shopSetupSubtitle: 'ව්‍යාපාර විස්තර, බ්‍රෑන්ඩිං සහ සිස්ටම් මනාපයන් සකසන්න.',
    dailyTitle: 'දෛනික චර්යාව',
    dailySubtitle: 'සෑම වැඩ දිනකම ආරම්භයේ සහ අවසානයේ මෙම පියවර අනුගමනය කරන්න.',
    featuresTitle: 'විශේෂාංග දළ විශ්ලේෂණය',
    featuresSubtitle: 'Siril Motors හට ඔබ වෙනුවෙන් කළ හැකි සෑම දෙයකම ඉක්මන් දක්ෂ.',
    purchasingTitle: 'මිලදී ගැනීමේ මාර්ගෝපදේශය',
    purchasingSubtitle: 'ගෝලාර්ත ඇනවුමේ සිට GRN දක්වා ගෙවීම දක්වා ගබඩා ඇණවුම් කළමනාකරණය.',
    accountingTitle: 'ගිණුම්කරණය සහ GL',
    accountingSubtitle: 'ද්විත්ව ප්‍රවේශ ලෙජරය, ජර්නල් ප්‍රවේශ සහ වාර්තා අවබෝධ කරගන්න.',
    hrTitle: 'HR සහ වේතන',
    hrSubtitle: 'සේවකයින්, පැමිණීම සහ මාසික වේතන ගෙවීම් කළමනාකරණය කරන්න.',
    warningsTitle: 'වැදගත් අනතුරු ඇඟවීම්',
    warningsSubtitle: 'දත්ත නැතිවීම හෝ දෝෂ ඇතිවීම වළක්වා ගැනීමට සැලකිලිමත් විය යුතු කරුණු.',
  },
}

function t(key) {
  return strings[lang.value][key] ?? key
}

// ── Sections sidebar ──────────────────────────────────────────────────────────
const sections = [
  { id: 'checklist',     icon: '✅', label: { en: 'Go-Live Checklist', si: 'ගෝ-ලයිව් ලැයිස්තුව' } },
  { id: 'shop-setup',    icon: '🏪', label: { en: 'Shop Setup',        si: 'වෙළඳසැල් සැකසුම' } },
  { id: 'daily-routine', icon: '📅', label: { en: 'Daily Routine',     si: 'දෛනික චර්යාව' } },
  { id: 'features',      icon: '⚙️', label: { en: 'Features Overview', si: 'විශේෂාංග දළ විශ්ලේෂණය' } },
  { id: 'purchasing',    icon: '📦', label: { en: 'Purchasing Guide',  si: 'මිලදී ගැනීමේ මාර්ගෝපදේශය' } },
  { id: 'accounting',    icon: '📊', label: { en: 'Accounting & GL',   si: 'ගිණුම්කරණය සහ GL' } },
  { id: 'hr',            icon: '👥', label: { en: 'HR & Payroll',      si: 'HR සහ වේතන' } },
  { id: 'warnings',      icon: '⚠️', label: { en: 'Important Warnings',si: 'වැදගත් අනතුරු ඇඟවීම්' } },
]
const activeSection = ref('checklist')

// ── Go-Live Checklist ─────────────────────────────────────────────────────────
const checklist = [
  {
    id: 'shop-info',
    title: { en: '1 — Shop Information', si: '1 — වෙළඳසැල් තොරතුරු' },
    items: [
      {
        id: 'shop-name',
        label: { en: 'Enter shop name, address, phone & BR number', si: 'වෙළඳසැල් නම, ලිපිනය, දූරකථන සහ BR අංකය ඇතුළු කරන්න' },
        hint:  { en: 'Settings → Shop Settings', si: 'සැකසුම් → වෙළඳසැල් සැකසුම්' },
        link:  '/shop-settings',
      },
      {
        id: 'shop-logo',
        label: { en: 'Upload your shop logo', si: 'ඔබේ වෙළඳසැල් ලාංඡනය උඩුගත කරන්න' },
        hint:  { en: 'Appears on all invoices and receipts', si: 'සියලු ඉන්වොයිස් සහ රිසිට් පත් මත දිස්වේ' },
        link:  '/shop-settings',
      },
      {
        id: 'print-mode',
        label: { en: 'Set print mode (POS thermal or A5 paper)', si: 'මුද්‍රණ ප්‍රකාරය සකසන්න (POS thermal හෝ A5 කඩදාසිය)' },
        hint:  { en: 'Settings → Shop Settings', si: 'සැකසුම් → වෙළඳසැල් සැකසුම්' },
        link:  '/shop-settings',
      },
    ],
  },
  {
    id: 'products-setup',
    title: { en: '2 — Parts & Inventory', si: '2 — අමතර කොටස් සහ ගබඩාව' },
    items: [
      {
        id: 'categories',
        label: { en: 'Create product categories (Engine, Body, Electrical…)', si: 'නිෂ්පාදන කාණ්ඩ සාදන්න (එන්ජිම, ශරීරය, විද්‍යුත්...)' },
        hint:  { en: 'Products → Categories', si: 'නිෂ්පාදන → කාණ් ඩ' },
        link:  '/products',
      },
      {
        id: 'add-products',
        label: { en: 'Add your initial parts inventory', si: 'ඔබේ ආරම්භක අමතර කොටස් ගබඩාව එක් කරන්න' },
        hint:  { en: 'Include part number, brand, cost and selling price', si: 'කොටස් අංකය, බ්‍රෑන්ඩ්, පිරිවැය සහ විකිණුම් මිල ඇතුළත් කරන්න' },
        link:  '/products',
      },
      {
        id: 'opening-stock',
        label: { en: 'Enter opening stock balances', si: 'විවෘත ගබඩා ශේෂ ඇතුළු කරන්න' },
        hint:  { en: 'Accounting → Opening Balances', si: 'ගිණුම්කරණය → විවෘත ශේෂ' },
        link:  '/opening-balances',
      },
    ],
  },
  {
    id: 'suppliers-setup',
    title: { en: '3 — Suppliers', si: '3 — සැපයුම්කරුවන්' },
    items: [
      {
        id: 'add-suppliers',
        label: { en: 'Add your key parts suppliers', si: 'ඔබේ ප්‍රධාන කොටස් සැපයුම්කරුවන් එක් කරන්න' },
        hint:  { en: 'Include name, phone, company and payment terms', si: 'නම, දූරකථන, සමාගම සහ ගෙවීමේ කොන්දේසි ඇතුළත් කරන්න' },
        link:  '/suppliers',
      },
    ],
  },
  {
    id: 'customers-setup',
    title: { en: '4 — Customers', si: '4 — පාරිභෝගිකයින්' },
    items: [
      {
        id: 'add-customers',
        label: { en: 'Import or add regular workshop / retail customers', si: 'නිතිපතා රැකියා ශාලා / සිල්ලර පාරිභෝගිකයින් ආයාත කරන්න හෝ එක් කරන්න' },
        hint:  { en: 'Customers page', si: 'පාරිභෝගිකයින් පිටුව' },
        link:  '/customers',
      },
    ],
  },
  {
    id: 'users-setup',
    title: { en: '5 — User Accounts', si: '5 — පරිශීලක ගිණුම්' },
    items: [
      {
        id: 'create-users',
        label: { en: 'Create staff accounts with correct roles', si: 'නිවැරදි භූමිකා සහිත කාර්ය මණ්ඩල ගිණුම් සාදන්න' },
        hint:  { en: 'Admin → Users (cashier, manager, accountant…)', si: 'පරිපාලක → පරිශීලකයින් (රේඛිකරු, කළමනාකරු, ගිණුම්කරු...)' },
        link:  '/users',
      },
      {
        id: 'change-password',
        label: { en: 'Change default admin password', si: 'පෙරනිමි පරිපාලක මුරපදය වෙනස් කරන්න' },
        hint:  { en: 'Admin → Users', si: 'පරිපාලක → පරිශීලකයින්' },
        link:  '/users',
      },
    ],
  },
  {
    id: 'accounting-setup',
    title: { en: '6 — Accounting Setup', si: '6 — ගිණුම්කරණ සැකසුම' },
    items: [
      {
        id: 'chart-of-accounts',
        label: { en: 'Review and customise the Chart of Accounts', si: 'ගිණුම් ප්‍රස්ථාරය සමාලෝචනය කර සකස් කරන්න' },
        hint:  { en: 'Accounting → Chart of Accounts', si: 'ගිණුම්කරණය → ගිණුම් ප්‍රස්ථාරය' },
        link:  '/accounts',
      },
      {
        id: 'opening-balances',
        label: { en: 'Enter opening balances for all accounts', si: 'සියලු ගිණුම් සඳහා විවෘත ශේෂ ඇතුළු කරන්න' },
        hint:  { en: 'Accounting → Opening Balances', si: 'ගිණුම්කරණය → විවෘත ශේෂ' },
        link:  '/opening-balances',
      },
    ],
  },
  {
    id: 'test-run',
    title: { en: '7 — Test Run', si: '7 — පරීක්ෂා කිරීම' },
    items: [
      {
        id: 'test-sale',
        label: { en: 'Process a test sale and print a receipt', si: 'පරීක්ෂණ විකිණුමක් සකසා රිසිට් පතක් මුද්‍රණය කරන්න' },
        hint:  { en: 'Sales → New Sale', si: 'විකුණු → නව විකිණීම' },
        link:  '/sales/new',
      },
      {
        id: 'test-purchase',
        label: { en: 'Process a test purchase order and GRN', si: 'පරීක්ෂණ ගෝලාර්ත ඇනවුමක් සහ GRN සකසන්න' },
        hint:  { en: 'Purchasing → Purchase Orders', si: 'මිලදී ගැනීම → ගෝලාර්ත ඇනවුම්' },
        link:  '/purchase-orders',
      },
      {
        id: 'day-end-test',
        label: { en: 'Run a Day-End reconciliation', si: 'දිනය-අවසාන ගැළපීමක් ක්‍රියාත්මක කරන්න' },
        hint:  { en: 'Admin → Day End', si: 'පරිපාලක → දිනය අවසානය' },
        link:  '/day-end',
      },
    ],
  },
]

const checked = reactive({})
const totalItems = computed(() => checklist.reduce((s, g) => s + g.items.length, 0))
const checklistDone = computed(() => Object.values(checked).filter(Boolean).length)
const progressPercent = computed(() => Math.round((checklistDone.value / totalItems.value) * 100))
const progressColor = computed(() => progressPercent.value === 100 ? 'text-green-600' : 'text-blue-600')

function resetAll() {
  Object.keys(checked).forEach(k => { checked[k] = false })
}

// ── Shop Setup cards ──────────────────────────────────────────────────────────
const shopSetupCards = [
  {
    id: 'branding',
    icon: '🏪',
    title: { en: 'Business Information & Branding', si: 'ව්‍යාපාර තොරතුරු සහ බ්‍රෑන්ඩිං' },
    body:  { en: 'Enter your shop name, address, phone number and business registration number. Upload your logo — it will appear on every invoice and receipt you print.', si: 'ඔබේ වෙළඳසැල් නම, ලිපිනය, දූරකථන අංකය සහ ව්‍යාපාර ලියාපදිංචි අංකය ඇතුළු කරන්න. ඔබේ ලාංඡනය උඩුගත කරන්න — එය ඔබ මුද්‍රණය කරන සෑම ඉන්වොයිසියකම සහ රිසිට් පතකම දිස්වේ.' },
    link:  '/shop-settings',
  },
  {
    id: 'print',
    icon: '🖨️',
    title: { en: 'Print Mode', si: 'මුද්‍රණ ප්‍රකාරය' },
    body:  { en: 'Choose POS/Thermal for 80mm roll receipts or A5 for full-page invoices. You can switch at any time in Shop Settings.', si: '80mm රෝල් රිසිට් සඳහා POS/Thermal හෝ සම්පූර්ණ පිටු ඉන්වොයිස් සඳහා A5 තෝරන්න. ඔබට ඕනෑම විටෙක Shop Settings හි මාරු කළ හැකිය.' },
    link:  '/shop-settings',
  },
  {
    id: 'categories',
    icon: '🗂️',
    title: { en: 'Parts Categories', si: 'කොටස් කාණ්ඩ' },
    body:  { en: 'Set up categories before adding products — Engine Parts, Body Parts, Electrical, Filters, Lubricants, Tyres & Batteries are good starting points.', si: 'නිෂ්පාදන එකතු කිරීමට පෙර කාණ්ඩ සාදන්න — එන්ජිම් කොටස්, ශරීර කොටස්, විද්‍යුත්, පෙරහන්, ලිහිසිකාරක, රේගු සහ බැටරි හොඳ ආරම්භක ස්ථාන වේ.' },
    link:  '/products',
  },
  {
    id: 'master-data',
    icon: '⚙️',
    title: { en: 'Master Data (Vehicle Makes & Models)', si: 'ප්‍රධාන දත්ත (වාහන නිෂ්පාදකයින් සහ ආකෘති)' },
    body:  { en: 'Add vehicle makes and models so you can link parts to specific vehicles. This helps with faster lookup during sales.', si: 'කොටස් නිශ්චිත වාහන සමඟ සම්බන්ධ කිරීමට වාහන නිෂ්පාදකයින් සහ ආකෘති එකතු කරන්න. මෙය විකිණීම් අතරතුර ඉක්මන් සෙවුමට උපකාරී වේ.' },
    link:  '/master-data',
  },
]

// ── Daily Routine steps ───────────────────────────────────────────────────────
const dailySteps = [
  {
    title: { en: 'Check stock levels', si: 'ගබඩා මට්ටම් පරීක්ෂා කරන්න' },
    body:  { en: 'Review low-stock parts each morning and raise purchase orders for items running low.', si: 'සෑම උදෑසනකම අඩු ගබඩා කොටස් සමාලෝචනය කර අඩු කරන ලද අයිතම සඳහා ගෝලාර්ත ඇනවුම් ඉදිරිපත් කරන්න.' },
  },
  {
    title: { en: 'Process incoming parts (GRN)', si: 'ලැබෙන කොටස් සකසන්න (GRN)' },
    body:  { en: 'When a delivery arrives, create a GRN to update stock. Verify quantities and supplier invoice details.', si: 'භාරයක් ලැබෙන විට, ගබඩාව යාවත්කාලීන කිරීමට GRN සාදන්න. ප්‍රමාණ සහ සැපයුම්කරු ඉන්වොයිස් විස්තර සත්‍යාපනය කරන්න.' },
  },
  {
    title: { en: 'Process sales', si: 'විකිණීම් සකසන්න' },
    body:  { en: 'Use Sales → New Sale for every counter sale. Select the customer, add parts, and print a receipt.', si: 'සෑම කවුන්ටර විකිණීමකටම Sales → New Sale භාවිතා කරන්න. පාරිභෝගිකයා තෝරා, කොටස් එකතු කරන්න, සහ රිසිට් පතක් මුද්‍රණය කරන්න.' },
  },
  {
    title: { en: 'Record expenses', si: 'වියදම් වාර්තා කරන්න' },
    body:  { en: 'Log any cash expenses (courier, cleaning, stationery) under Admin → Expenses throughout the day.', si: 'ඕනෑම මුදල් වියදම් (කුරියර්, පිරිසිදු කිරීම, ලිපිද්‍රව්‍ය) Admin → Expenses යටතේ දිවා කාලය පුරා ලොග් කරන්න.' },
  },
  {
    title: { en: 'Day-End reconciliation', si: 'දිනය-අවසාන ගැළපීම' },
    body:  { en: 'At close of business, run Day End to reconcile the cash drawer and confirm the day\'s totals.', si: 'ව්‍යාපාර වසා දැමීමේදී, මුදල් ලාච්චු ගැළපීමට සහ දිනය\'ස් සම්පූර්ණ කිරීම් තහවුරු කිරීමට Day End ක්‍රියාත්මක කරන්න.' },
  },
]

// ── Features cards ────────────────────────────────────────────────────────────
const featureCards = [
  { id: 'sales',      icon: '🛒', title: { en: 'Point of Sale',        si: 'විකිණුම් ස්ථානය'       }, body: { en: 'Fast counter sales with part search, customer credit terms and thermal/A5 receipt printing.', si: 'කොටස් සෙවීම, පාරිභෝගික ණය කොන්දේසි සහ thermal/A5 රිසිට් මුද්‍රණය සමඟ ඉක්මන් කවුන්ටර් විකිණීම.' } },
  { id: 'purchasing', icon: '📦', title: { en: 'Full Purchasing Cycle', si: 'සම්පූර්ණ මිලදී ගැනීමේ චක්‍රය' }, body: { en: 'Purchase Orders → GRN → Supplier Invoice → Payment in a structured workflow.', si: 'ව්‍යුහගත කාර්ය ප්‍රවාහයක Purchase Orders → GRN → Supplier Invoice → Payment.' } },
  { id: 'inventory',  icon: '🔩', title: { en: 'Parts Inventory',       si: 'කොටස් ගබඩාව'          }, body: { en: 'Track stock by part number, brand, vehicle compatibility, with real-time quantity updates.', si: 'කොටස් අංකය, බ්‍රෑන්ඩ්, වාහන අනුකූලතාව අනුව ගබඩාව track කරන්න, real-time ප්‍රමාණ යාවත්කාලීන කිරීම් සමඟ.' } },
  { id: 'accounting', icon: '📊', title: { en: 'Double-Entry Accounting', si: 'ද්විත්ව-ප්‍රවේශ ගිණුම්කරණය' }, body: { en: 'Auto-posted journal entries for every sale, purchase and payment. Full P&L and Balance Sheet.', si: 'සෑම විකිණීමක්, මිලදී ගැනීමක් සහ ගෙවීමක් සඳහා ස්වයංක්‍රීයව post කළ ජර්නල් ප්‍රවේශ. සම්පූර්ණ P&L සහ Balance Sheet.' } },
  { id: 'hr',         icon: '👥', title: { en: 'HR & Payroll',           si: 'HR සහ වේතන'           }, body: { en: 'Employee profiles, salary structures and monthly payroll with printable pay slips.', si: 'සේවක පැතිකඩ, වේතන ව්‍යුහයන් සහ මාසික ශ්‍රම වේතන printable pay slips සමඟ.' } },
  { id: 'reports',    icon: '📈', title: { en: 'Reports',                si: 'වාර්තා'               }, body: { en: 'Sales, purchases, stock, profit & loss, supplier ageing and more — export to PDF.', si: 'විකිණීම්, මිලදී ගැනීම්, ගබඩාව, ලාභ සහ අලාභ, සැපයුම්කරු වයස්ගත වීම සහ තවත් — PDF ලෙස නිර්යාත කරන්න.' } },
  { id: 'sms',        icon: '📱', title: { en: 'SMS Centre',             si: 'SMS මධ්‍යස්ථානය'      }, body: { en: 'Send promotional or transactional SMS to customers directly from the system.', si: 'සිස්ටමයෙන් සෘජුවම පාරිභෝගිකයින්ට ප්‍රවර්ධන හෝ ගනුදෙනු SMS යවන්න.' } },
  { id: 'audit',      icon: '🔒', title: { en: 'Audit Log',              si: 'විගණන ලොගය'           }, body: { en: 'Every action is logged with timestamp and user — full traceability for managers and auditors.', si: 'සෑම ක්‍රියාවක්ම timestamp සහ පරිශීලකය සමඟ ලොග් කෙරේ — කළමනාකරුවන් සහ විගණකයින් සඳහා සම්පූර්ණ ට්‍රේස් කිරීමේ හැකියාව.' } },
]

// ── Purchasing Guide cards ────────────────────────────────────────────────────
const purchasingCards = [
  {
    id: 'po',
    icon: '📋',
    title: { en: 'Step 1 — Create a Purchase Order', si: 'පියවර 1 — ගෝලාර්ත ඇනවුමක් සාදන්න' },
    body:  { en: 'Go to Purchasing → Purchase Orders and raise a PO for the parts you need. Select the supplier, add line items, and save.', si: 'Purchasing → Purchase Orders වෙත ගොස් ඔබට අවශ්‍ය කොටස් සඳහා PO ඉදිරිපත් කරන්න. සැපයුම්කරු තෝරා, line items එකතු කරන්න, සහ සුරකින්න.' },
    link:  '/purchase-orders',
  },
  {
    id: 'grn',
    icon: '📥',
    title: { en: 'Step 2 — Receive Goods (GRN)', si: 'පියවර 2 — භාණ්ඩ ලබා ගන්න (GRN)' },
    body:  { en: 'When parts arrive, create a GRN against the PO. Verify quantities received. Stock is updated automatically.', si: 'කොටස් ලැබෙන විට, PO හිරෙහිව GRN සාදන්න. ලැබුණු ප්‍රමාණ සත්‍යාපනය කරන්න. ගබඩාව ස්වයංක්‍රීයව යාවත්කාලීන වේ.' },
    link:  '/grn',
  },
  {
    id: 'invoice',
    icon: '🧾',
    title: { en: 'Step 3 — Record Supplier Invoice', si: 'පියවර 3 — සැපයුම්කරු ඉන්වොයිසිය සටහන් කරන්න' },
    body:  { en: 'Enter the supplier\'s invoice number and amount under Goods Invoices. This creates a payable in the ledger.', si: 'Goods Invoices යටතේ සැපයුම්කරු\'ස් ඉන්වොයිස් අංකය සහ මුදල ඇතුළු කරන්න. මෙය ලෙජරයේ ගෙවිය යුතු දෙයක් සාදයි.' },
    link:  '/goods-invoices',
  },
  {
    id: 'payment',
    icon: '💳',
    title: { en: 'Step 4 — Pay the Supplier', si: 'පියවර 4 — සැපයුම්කරුට ගෙවන්න' },
    body:  { en: 'Record payment under Purchasing → Supplier Payments. Partial payments are supported.', si: 'Purchasing → Supplier Payments යටතේ ගෙවීම සටහන් කරන්න. අර්ධ ගෙවීම් සහාය දක්වයි.' },
    link:  '/supplier-payments',
  },
  {
    id: 'returns',
    icon: '↩️',
    title: { en: 'Purchase Returns', si: 'මිලදී ගැනීමේ ආපසු' },
    body:  { en: 'If parts need to be returned to the supplier, use Purchase Returns to adjust stock and the payable balance.', si: 'කොටස් සැපයුම්කරු වෙත ආපසු ලබා දීමට අවශ්‍ය නම්, ගබඩාව සහ ගෙවිය යුතු ශේෂය සකස් කිරීමට Purchase Returns භාවිතා කරන්න.' },
    link:  '/purchase-returns',
  },
]

// ── Accounting cards ──────────────────────────────────────────────────────────
const accountingCards = [
  {
    id: 'coa',
    icon: '📒',
    title: { en: 'Chart of Accounts', si: 'ගිණුම් ප්‍රස්ථාරය' },
    body:  { en: 'All accounts are pre-configured for a motor parts business. You can rename or add accounts but do not delete the system defaults.', si: 'සියලු ගිණුම් මෝටර් කොටස් ව්‍යාපාරයක් සඳහා පූර්ව-සකස් කර ඇත. ඔබට ගිණුම් නැවත නම් කළ හෝ එකතු කළ හැකිය, නමුත් සිස්ටම් defaults මකන්න එපා.' },
    link:  '/accounts',
  },
  {
    id: 'journal',
    icon: '📝',
    title: { en: 'Journal Entries', si: 'ජර්නල් ප්‍රවේශ' },
    body:  { en: 'Sales, purchases and payments post journal entries automatically. Use manual journal entries only for adjustments like depreciation or owner drawings.', si: 'විකිණීම්, මිලදී ගැනීම් සහ ගෙවීම් ස්වයංක්‍රීයව ජර්නල් ප්‍රවේශ post කරයි. depreciation හෝ owner drawings වැනි සකස් කිරීම් සඳහා පමණක් manual journal entries භාවිතා කරන්න.' },
    link:  '/journal-entries',
  },
  {
    id: 'gl',
    icon: '📊',
    title: { en: 'General Ledger', si: 'සාමාන්‍ය ලෙජරය' },
    body:  { en: 'View a full transaction history for any account. Use this to investigate discrepancies or reconcile bank statements.', si: 'ඕනෑම ගිණුමක් සඳහා සම්පූර්ණ ගනුදෙනු ඉතිහාසයක් බලන්න. විෂමතා විමර්ශනය කිරීමට හෝ බැංකු ප්‍රකාශ ගැළපීමට මෙය භාවිතා කරන්න.' },
    link:  '/general-ledger',
  },
  {
    id: 'ob',
    icon: '⚖️',
    title: { en: 'Opening Balances', si: 'විවෘත ශේෂ' },
    body:  { en: 'Enter starting balances for all accounts and stock when you first go live. This can only be done once — verify everything carefully.', si: 'ඔබ පළමු වරට go live වන විට සියලු ගිණුම් සහ ගබඩාව සඳහා ආරම්භක ශේෂ ඇතුළු කරන්න. මෙය කළ හැක්කේ එක් වරක් පමණි — සෑම දෙයක්ම ප්‍රවේශමෙන් සත්‍යාපනය කරන්න.' },
    link:  '/opening-balances',
  },
]

// ── HR cards ──────────────────────────────────────────────────────────────────
const hrCards = [
  {
    id: 'employees',
    icon: '👤',
    title: { en: 'Employee Profiles', si: 'සේවක පැතිකඩ' },
    body:  { en: 'Add all permanent and part-time staff with their NIC, contact number, bank account details and basic salary.', si: 'සියලු ස්ථිර සහ අර්ධකාලීන කාර්ය මණ්ඩලය ඔවුන්ගේ NIC, සම්බන්ධ කර ගැනීමේ අංකය, බැංකු ගිණුම් විස්තර සහ මූලික වේතනය සමඟ එකතු කරන්න.' },
    link:  '/employees',
  },
  {
    id: 'salary',
    icon: '💰',
    title: { en: 'Monthly Salary Payments', si: 'මාසික වේතන ගෙවීම්' },
    body:  { en: 'Each month go to HR → Salary Payments to generate and record payroll. Printable pay slips are available.', si: 'සෑම මාසයකම HR → Salary Payments වෙත ගොස් payroll generate කර සටහන් කරන්න. Printable pay slips ලබා ගත හැකිය.' },
    link:  '/salary-payments',
  },
]

// ── Warnings ──────────────────────────────────────────────────────────────────
const warnings = [
  {
    id: 'opening-once',
    icon: '⚠️',
    level: 'warn',
    title: { en: 'Opening Balances can only be entered once', si: 'විවෘත ශේෂ ඇතුළු කළ හැක්කේ එක් වරක් පමණි' },
    body:  { en: 'Double-check all stock quantities and account balances before saving Opening Balances. Mistakes here require manual journal corrections.', si: 'Opening Balances සුරැකීමට පෙර සියලු ගබඩා ප්‍රමාණ සහ ගිණුම් ශේෂ දෙවරක් පරීක්ෂා කරන්න. මෙහි වැරදි manual journal නිවැරදි කිරීම් අවශ්‍ය වේ.' },
  },
  {
    id: 'day-end-daily',
    icon: '🕐',
    level: 'warn',
    title: { en: 'Run Day-End every day before closing', si: 'වසා දැමීමට පෙර සෑම දිනකම Day-End ක්‍රියාත්මක කරන්න' },
    body:  { en: 'Skipping Day-End means the cash reconciliation will be off. Make it part of the closing routine.', si: 'Day-End මඟ හරිනවා නම් මුදල් ගැළපීම අවුල් වේ. එය වසා දැමීමේ චර්යාවේ කොටසක් කරන්න.' },
  },
  {
    id: 'delete-products',
    icon: '🚫',
    level: 'danger',
    title: { en: 'Never delete products that have been sold', si: 'විකිණී ඇති නිෂ්පාදන කිසිවිටෙකත් මකන්න එපා' },
    body:  { en: 'Deleting a product removes its history from reports and ledger. Mark items as inactive instead.', si: 'නිෂ්පාදනයක් මකා දැමීම වාර්තා සහ ලෙජරයෙන් එහි ඉතිහාසය ඉවත් කරයි. ඒ වෙනුවට items inactive ලෙස සලකුණු කරන්න.' },
  },
  {
    id: 'backups',
    icon: '💾',
    level: 'warn',
    title: { en: 'Take regular database backups', si: 'නිතිපතා දත්තාදාර backup ගන්න' },
    body:  { en: 'Ask your system administrator to schedule daily backups. The system does not back up automatically.', si: 'දෛනික backup නියමිත කාලසටහනකට ගැනීමට ඔබේ system administrator ගෙන් ඉල්ලා සිටින්න. System ස්වයංක්‍රීයව backup නොකරයි.' },
  },
  {
    id: 'roles',
    icon: '🔑',
    level: 'warn',
    title: { en: 'Assign the correct role to every user', si: 'සෑම පරිශීලකයෙකුටම නිවැරදි භූමිකාව පවරන්න' },
    body:  { en: 'Cashiers should not have admin access. Managers cannot see accounting unless the accountant role is assigned. Set roles carefully.', si: 'Cashiers ට admin access නොතිබිය යුතු ය. accountant role නොගෙනහිර නම් Managers ට ගිණුම්කරණය දැකගත නොහැකිය. භූමිකා ප்‍රවේශමෙන් සකසන්න.' },
  },
]
</script>

