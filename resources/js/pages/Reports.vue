<template>
  <div class="space-y-6">
    <!-- Header + date filters -->
    <div class="flex flex-col sm:flex-row sm:items-end gap-3">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Reports</h2>
        <p class="text-sm text-gray-500 mt-0.5">Business insights and summaries</p>
      </div>
      <div class="sm:ml-auto flex flex-wrap items-end gap-2">
        <div>
          <label class="text-xs text-gray-500 block mb-1">From</label>
          <input type="date" v-model="dateFrom" class="form-input text-sm py-1.5" />
        </div>
        <div>
          <label class="text-xs text-gray-500 block mb-1">To</label>
          <input type="date" v-model="dateTo" class="form-input text-sm py-1.5" />
        </div>
        <button @click="applyDates" class="btn-primary text-sm py-1.5 px-4">Apply</button>
        <button @click="printReport" class="btn-secondary text-sm py-1.5 px-3 flex items-center gap-1">
          <PrinterIcon class="w-4 h-4" /> Print
        </button>
      </div>
    </div>

    <!-- Quick range pills -->
    <div class="flex gap-2 flex-wrap">
      <button v-for="r in quickRanges" :key="r.label"
        @click="setQuickRange(r)"
        class="px-3 py-1 rounded-full text-xs font-medium border transition-colors"
        :class="activeRange === r.label
          ? 'bg-blue-600 text-white border-blue-600'
          : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400'">
        {{ r.label }}
      </button>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="flex gap-1 overflow-x-auto">
        <button v-for="tab in tabs" :key="tab.id"
          @click="activeTab = tab.id; loadTab(tab.id)"
          class="px-4 py-2.5 text-sm font-medium border-b-2 whitespace-nowrap transition-colors flex items-center gap-1.5"
          :class="activeTab === tab.id
            ? 'border-blue-600 text-blue-700'
            : 'border-transparent text-gray-500 hover:text-gray-700'">
          <component :is="tab.icon" class="w-4 h-4" />
          {{ tab.label }}
        </button>
      </nav>
    </div>

    <!-- ── SALES REPORT ── -->
    <div v-if="activeTab === 'sales'" class="space-y-4">
      <div v-if="loading.sales" class="py-16 text-center text-gray-400">Loading…</div>
      <template v-else-if="sales">
        <!-- Summary cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Invoices</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ sales.totals?.count ?? 0 }}</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Revenue</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ fmt(sales.totals?.total_revenue) }}</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Collected</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ fmt(sales.totals?.amount_paid) }}</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Outstanding</p>
            <p class="text-2xl font-bold text-orange-500 mt-1">{{ fmt(sales.totals?.outstanding) }}</p>
          </div>
        </div>

        <!-- Breakdown row -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- By payment method -->
          <div class="card">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">By Payment Method</h4>
            <div class="space-y-2">
              <div v-for="r in sales.by_payment_method" :key="r.method" class="flex items-center justify-between text-sm">
                <span class="capitalize text-gray-600">{{ r.method ?? 'Unknown' }}</span>
                <div class="flex items-center gap-4">
                  <span class="text-gray-400 text-xs">{{ r.count }} invoices</span>
                  <span class="font-medium text-gray-800">{{ fmt(r.total) }}</span>
                </div>
              </div>
              <p v-if="!sales.by_payment_method?.length" class="text-gray-400 text-xs">No data</p>
            </div>
          </div>
          <!-- By status -->
          <div class="card">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">By Payment Status</h4>
            <div class="space-y-2">
              <div v-for="r in sales.by_status" :key="r.status" class="flex items-center justify-between text-sm">
                <span class="capitalize px-2 py-0.5 rounded-full text-xs font-medium"
                  :class="statusClass(r.status)">{{ r.status }}</span>
                <div class="flex items-center gap-4">
                  <span class="text-gray-400 text-xs">{{ r.count }}</span>
                  <span class="font-medium text-gray-800">{{ fmt(r.total) }}</span>
                </div>
              </div>
              <p v-if="!sales.by_status?.length" class="text-gray-400 text-xs">No data</p>
            </div>
          </div>
        </div>

        <!-- Detail table -->
        <div class="card p-0 overflow-hidden">
          <div class="px-5 py-3 border-b bg-gray-50 flex items-center justify-between">
            <h4 class="font-semibold text-gray-700 text-sm">Invoice Detail</h4>
            <button @click="exportCsv('sales')" class="text-xs text-blue-600 hover:underline flex items-center gap-1">
              <ArrowDownTrayIcon class="w-3.5 h-3.5" /> Export CSV
            </button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="table-th">Invoice</th>
                  <th class="table-th">Customer</th>
                  <th class="table-th">Vehicle</th>
                  <th class="table-th">Date</th>
                  <th class="table-th">Method</th>
                  <th class="table-th">Status</th>
                  <th class="table-th text-right">Total</th>
                  <th class="table-th text-right">Paid</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-if="!sales.rows?.length">
                  <td colspan="8" class="table-td text-center text-gray-400">No sales in this period</td>
                </tr>
                <tr v-for="r in sales.rows" :key="r.id" class="hover:bg-gray-50">
                  <td class="table-td font-mono text-xs">
                    <RouterLink :to="`/sales/${r.id}`" class="text-blue-600 hover:underline">{{ r.invoice_number }}</RouterLink>
                  </td>
                  <td class="table-td">{{ r.customer?.name ?? '—' }}</td>
                  <td class="table-td font-mono text-xs text-gray-500">{{ r.customer?.vehicle_number ?? '—' }}</td>
                  <td class="table-td text-gray-500 text-xs">{{ fmtDate(r.sold_at) }}</td>
                  <td class="table-td capitalize text-xs">{{ r.payment_method ?? '—' }}</td>
                  <td class="table-td">
                    <span class="px-1.5 py-0.5 rounded text-xs font-medium" :class="statusClass(r.payment_status)">{{ r.payment_status }}</span>
                  </td>
                  <td class="table-td text-right font-medium">{{ fmt(r.total) }}</td>
                  <td class="table-td text-right text-emerald-600">{{ fmt(r.amount_paid) }}</td>
                </tr>
              </tbody>
              <tfoot class="border-t-2 border-gray-200 bg-gray-50 font-semibold">
                <tr>
                  <td colspan="6" class="table-td text-right text-gray-600">Totals</td>
                  <td class="table-td text-right">{{ fmt(sales.totals?.total_revenue) }}</td>
                  <td class="table-td text-right text-emerald-600">{{ fmt(sales.totals?.amount_paid) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </template>
    </div>

    <!-- ── PURCHASES REPORT ── -->
    <div v-if="activeTab === 'purchases'" class="space-y-4">
      <div v-if="loading.purchases" class="py-16 text-center text-gray-400">Loading…</div>
      <template v-else-if="purchases">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Orders</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ purchases.totals?.count ?? 0 }}</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Subtotal</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ fmt(purchases.totals?.subtotal) }}</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Tax</p>
            <p class="text-2xl font-bold text-orange-500 mt-1">{{ fmt(purchases.totals?.total_tax) }}</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Paid</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ fmt(purchases.totals?.total) }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="card">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">By Supplier</h4>
            <div class="space-y-2">
              <div v-for="r in purchases.by_supplier" :key="r.supplier" class="flex items-center justify-between text-sm">
                <span class="text-gray-600 truncate">{{ r.supplier }}</span>
                <div class="flex items-center gap-4 flex-shrink-0">
                  <span class="text-gray-400 text-xs">{{ r.count }} orders</span>
                  <span class="font-medium text-gray-800">{{ fmt(r.total) }}</span>
                </div>
              </div>
              <p v-if="!purchases.by_supplier?.length" class="text-gray-400 text-xs">No data</p>
            </div>
          </div>
          <div class="card">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">By Status</h4>
            <div class="space-y-2">
              <div v-for="r in purchases.by_status" :key="r.status" class="flex items-center justify-between text-sm">
                <span class="capitalize text-gray-600">{{ r.status }}</span>
                <div class="flex items-center gap-4">
                  <span class="text-gray-400 text-xs">{{ r.count }}</span>
                  <span class="font-medium">{{ fmt(r.total) }}</span>
                </div>
              </div>
              <p v-if="!purchases.by_status?.length" class="text-gray-400 text-xs">No data</p>
            </div>
          </div>
        </div>

        <div class="card p-0 overflow-hidden">
          <div class="px-5 py-3 border-b bg-gray-50 flex items-center justify-between">
            <h4 class="font-semibold text-gray-700 text-sm">Purchase Detail</h4>
            <button @click="exportCsv('purchases')" class="text-xs text-blue-600 hover:underline flex items-center gap-1">
              <ArrowDownTrayIcon class="w-3.5 h-3.5" /> Export CSV
            </button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="table-th">PO Number</th>
                  <th class="table-th">Supplier</th>
                  <th class="table-th">Date</th>
                  <th class="table-th">Method</th>
                  <th class="table-th">Status</th>
                  <th class="table-th text-right">Subtotal</th>
                  <th class="table-th text-right">Total</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-if="!purchases.rows?.length">
                  <td colspan="7" class="table-td text-center text-gray-400">No purchases in this period</td>
                </tr>
                <tr v-for="r in purchases.rows" :key="r.id" class="hover:bg-gray-50">
                  <td class="table-td font-mono text-xs">{{ r.purchase_number }}</td>
                  <td class="table-td">{{ r.supplier?.name ?? '—' }}</td>
                  <td class="table-td text-gray-500 text-xs">{{ fmtDate(r.purchased_at) }}</td>
                  <td class="table-td capitalize text-xs">{{ r.payment_method ?? '—' }}</td>
                  <td class="table-td capitalize text-xs">{{ r.status }}</td>
                  <td class="table-td text-right">{{ fmt(r.subtotal) }}</td>
                  <td class="table-td text-right font-medium">{{ fmt(r.total) }}</td>
                </tr>
              </tbody>
              <tfoot class="border-t-2 border-gray-200 bg-gray-50 font-semibold">
                <tr>
                  <td colspan="5" class="table-td text-right text-gray-600">Totals</td>
                  <td class="table-td text-right">{{ fmt(purchases.totals?.subtotal) }}</td>
                  <td class="table-td text-right">{{ fmt(purchases.totals?.total) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </template>
    </div>

    <!-- ── INVENTORY REPORT ── -->
    <div v-if="activeTab === 'inventory'" class="space-y-4">
      <div v-if="loading.inventory" class="py-16 text-center text-gray-400">Loading…</div>
      <template v-else-if="inventory">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Products</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ inventory.totals?.total_products ?? 0 }}</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Stock Cost Value</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ fmt(inventory.totals?.total_stock_value) }}</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Stock Sell Value</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ fmt(inventory.totals?.total_sell_value) }}</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Low Stock</p>
            <p class="text-2xl font-bold text-orange-500 mt-1">{{ inventory.totals?.low_stock_count ?? 0 }}</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Out of Stock</p>
            <p class="text-2xl font-bold text-red-500 mt-1">{{ inventory.totals?.out_of_stock ?? 0 }}</p>
          </div>
        </div>

        <!-- By category breakdown -->
        <div class="card">
          <h4 class="text-sm font-semibold text-gray-700 mb-3">Stock Value by Category</h4>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="table-th">Category</th>
                  <th class="table-th text-right">Products</th>
                  <th class="table-th text-right">Cost Value</th>
                  <th class="table-th text-right">Sell Value</th>
                  <th class="table-th text-right">Margin</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="r in inventory.by_category" :key="r.category" class="hover:bg-gray-50">
                  <td class="table-td font-medium">{{ r.category }}</td>
                  <td class="table-td text-right text-gray-500">{{ r.count }}</td>
                  <td class="table-td text-right">{{ fmt(r.stock_value) }}</td>
                  <td class="table-td text-right text-emerald-600">{{ fmt(r.sell_value) }}</td>
                  <td class="table-td text-right text-blue-600">
                    {{ r.stock_value > 0 ? '+' + Math.round(((r.sell_value - r.stock_value) / r.stock_value) * 100) + '%' : '—' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Full product list -->
        <div class="card p-0 overflow-hidden">
          <div class="px-5 py-3 border-b bg-gray-50 flex items-center justify-between">
            <h4 class="font-semibold text-gray-700 text-sm">All Products</h4>
            <button @click="exportCsv('inventory')" class="text-xs text-blue-600 hover:underline flex items-center gap-1">
              <ArrowDownTrayIcon class="w-3.5 h-3.5" /> Export CSV
            </button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="table-th">SKU</th>
                  <th class="table-th">Name</th>
                  <th class="table-th">Category</th>
                  <th class="table-th">Brand</th>
                  <th class="table-th text-right">Stock</th>
                  <th class="table-th text-right">Min</th>
                  <th class="table-th text-right">Cost Price</th>
                  <th class="table-th text-right">Sell Price</th>
                  <th class="table-th text-right">Stock Value</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-if="!inventory.products?.length">
                  <td colspan="9" class="table-td text-center text-gray-400">No products</td>
                </tr>
                <tr v-for="p in inventory.products" :key="p.id" class="hover:bg-gray-50"
                  :class="{ 'bg-red-50': p.stock_quantity === 0, 'bg-orange-50': p.stock_quantity > 0 && p.stock_quantity <= p.min_stock_level }">
                  <td class="table-td font-mono text-xs">{{ p.sku }}</td>
                  <td class="table-td font-medium">{{ p.name }}</td>
                  <td class="table-td text-xs text-gray-500">{{ p.part_category?.name ?? '—' }}</td>
                  <td class="table-td text-xs text-gray-500">{{ p.brand?.name ?? '—' }}</td>
                  <td class="table-td text-right font-mono"
                    :class="p.stock_quantity === 0 ? 'text-red-600 font-bold' : p.stock_quantity <= p.min_stock_level ? 'text-orange-600 font-bold' : ''">
                    {{ p.stock_quantity }}
                  </td>
                  <td class="table-td text-right text-gray-400 font-mono">{{ p.min_stock_level }}</td>
                  <td class="table-td text-right">{{ fmt(p.purchase_price) }}</td>
                  <td class="table-td text-right text-emerald-600">{{ fmt(p.selling_price) }}</td>
                  <td class="table-td text-right font-medium">{{ fmt(p.purchase_price * p.stock_quantity) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>

    <!-- ── TOP PRODUCTS REPORT ── -->
    <div v-if="activeTab === 'top-products'" class="space-y-4">
      <div v-if="loading['top-products']" class="py-16 text-center text-gray-400">Loading…</div>
      <template v-else-if="topProducts">
        <div class="card p-0 overflow-hidden">
          <div class="px-5 py-3 border-b bg-gray-50 flex items-center justify-between">
            <h4 class="font-semibold text-gray-700 text-sm">Top Selling Parts — {{ fmtDate(topProducts.from) }} to {{ fmtDate(topProducts.to) }}</h4>
            <button @click="exportCsv('top-products')" class="text-xs text-blue-600 hover:underline flex items-center gap-1">
              <ArrowDownTrayIcon class="w-3.5 h-3.5" /> Export CSV
            </button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="table-th w-8">#</th>
                  <th class="table-th">SKU</th>
                  <th class="table-th">Part Name</th>
                  <th class="table-th">Category</th>
                  <th class="table-th text-right">Qty Sold</th>
                  <th class="table-th text-right">Avg Price</th>
                  <th class="table-th text-right">Revenue</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-if="!topProducts.rows?.length">
                  <td colspan="7" class="table-td text-center text-gray-400">No sales data in this period</td>
                </tr>
                <tr v-for="(r, i) in topProducts.rows" :key="r.id" class="hover:bg-gray-50">
                  <td class="table-td text-gray-400 font-mono text-xs">{{ i + 1 }}</td>
                  <td class="table-td font-mono text-xs text-gray-500">{{ r.sku }}</td>
                  <td class="table-td font-medium">{{ r.name }}</td>
                  <td class="table-td text-xs text-gray-500">{{ r.category ?? '—' }}</td>
                  <td class="table-td text-right font-bold text-blue-600">{{ r.qty_sold }}</td>
                  <td class="table-td text-right text-gray-600">{{ fmt(r.avg_price) }}</td>
                  <td class="table-td text-right font-medium text-emerald-600">{{ fmt(r.revenue) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>

    <!-- ── PROFIT & LOSS ── -->
    <div v-if="activeTab === 'profit-loss'" class="space-y-4">
      <div v-if="loading['profit-loss']" class="py-16 text-center text-gray-400">Loading…</div>
      <template v-else-if="profitLoss">
        <!-- P&L Summary -->
        <div class="card max-w-lg">
          <h4 class="font-semibold text-gray-700 mb-4">Profit & Loss — {{ fmtDate(profitLoss.from) }} to {{ fmtDate(profitLoss.to) }}</h4>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between py-1.5 border-b border-gray-100">
              <span class="text-gray-600">Parts Revenue</span>
              <span class="font-medium text-gray-800">{{ fmt(profitLoss.revenue - profitLoss.maintenance) }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-gray-100">
              <span class="text-gray-600">Service / Maintenance</span>
              <span class="font-medium text-gray-800">{{ fmt(profitLoss.maintenance) }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-gray-200 font-semibold">
              <span class="text-gray-800">Total Revenue</span>
              <span class="text-gray-800">{{ fmt(profitLoss.revenue) }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-gray-100">
              <span class="text-gray-600">Cost of Goods Sold (COGS)</span>
              <span class="text-red-500">− {{ fmt(profitLoss.cogs) }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-gray-200 font-semibold"
              :class="profitLoss.gross_profit >= 0 ? 'text-emerald-700' : 'text-red-600'">
              <span>Gross Profit</span>
              <span>{{ fmt(profitLoss.gross_profit) }} <span class="font-normal text-xs">({{ profitLoss.gross_margin }}%)</span></span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-gray-100">
              <span class="text-gray-600">Operating Expenses</span>
              <span class="text-red-500">− {{ fmt(profitLoss.expenses) }}</span>
            </div>
            <div class="flex justify-between py-2 rounded-lg px-2 mt-1 font-bold text-base"
              :class="profitLoss.net_profit >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'">
              <span>Net Profit</span>
              <span>{{ fmt(profitLoss.net_profit) }}</span>
            </div>
          </div>
        </div>

        <!-- Daily trend table -->
        <div class="card p-0 overflow-hidden">
          <div class="px-5 py-3 border-b bg-gray-50">
            <h4 class="font-semibold text-gray-700 text-sm">Daily Revenue Trend</h4>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="table-th">Date</th>
                  <th class="table-th text-right">Invoices</th>
                  <th class="table-th text-right">Revenue</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-if="!profitLoss.trend?.length">
                  <td colspan="3" class="table-td text-center text-gray-400">No data</td>
                </tr>
                <tr v-for="r in profitLoss.trend" :key="r.date" class="hover:bg-gray-50">
                  <td class="table-td text-gray-600">{{ r.date }}</td>
                  <td class="table-td text-right text-gray-500">{{ r.invoices }}</td>
                  <td class="table-td text-right font-medium text-emerald-600">{{ fmt(r.revenue) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>

    <!-- ── EXPENSES REPORT ── -->
    <div v-if="activeTab === 'expenses'" class="space-y-4">
      <div v-if="loading.expenses" class="py-16 text-center text-gray-400">Loading…</div>
      <template v-else-if="expenses">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Transactions</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ expenses.totals?.count ?? 0 }}</p>
          </div>
          <div class="card text-center md:col-span-2">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Expenses</p>
            <p class="text-2xl font-bold text-red-500 mt-1">{{ fmt(expenses.totals?.total_amount) }}</p>
          </div>
        </div>

        <div class="card">
          <h4 class="text-sm font-semibold text-gray-700 mb-3">By Category</h4>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="table-th">Category</th>
                  <th class="table-th text-right">Count</th>
                  <th class="table-th text-right">Total</th>
                  <th class="table-th text-right">% of Total</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="r in expenses.by_category" :key="r.category" class="hover:bg-gray-50">
                  <td class="table-td capitalize font-medium">{{ r.category }}</td>
                  <td class="table-td text-right text-gray-500">{{ r.count }}</td>
                  <td class="table-td text-right font-medium">{{ fmt(r.total) }}</td>
                  <td class="table-td text-right text-gray-500">
                    {{ expenses.totals?.total_amount > 0 ? Math.round((r.total / expenses.totals.total_amount) * 100) + '%' : '—' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card p-0 overflow-hidden">
          <div class="px-5 py-3 border-b bg-gray-50 flex items-center justify-between">
            <h4 class="font-semibold text-gray-700 text-sm">Expense Detail</h4>
            <button @click="exportCsv('expenses')" class="text-xs text-blue-600 hover:underline flex items-center gap-1">
              <ArrowDownTrayIcon class="w-3.5 h-3.5" /> Export CSV
            </button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="table-th">Date</th>
                  <th class="table-th">Category</th>
                  <th class="table-th">Description</th>
                  <th class="table-th">Method</th>
                  <th class="table-th">Ref</th>
                  <th class="table-th text-right">Amount</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-if="!expenses.rows?.length">
                  <td colspan="6" class="table-td text-center text-gray-400">No expenses in this period</td>
                </tr>
                <tr v-for="r in expenses.rows" :key="r.id" class="hover:bg-gray-50">
                  <td class="table-td text-gray-500 text-xs">{{ r.expense_date }}</td>
                  <td class="table-td capitalize text-xs">{{ r.category }}</td>
                  <td class="table-td text-gray-600">{{ r.description ?? '—' }}</td>
                  <td class="table-td capitalize text-xs">{{ r.payment_method ?? '—' }}</td>
                  <td class="table-td font-mono text-xs text-gray-400">{{ r.reference_number ?? '—' }}</td>
                  <td class="table-td text-right font-medium text-red-500">{{ fmt(r.amount) }}</td>
                </tr>
              </tbody>
              <tfoot class="border-t-2 border-gray-200 bg-gray-50 font-semibold">
                <tr>
                  <td colspan="5" class="table-td text-right text-gray-600">Total</td>
                  <td class="table-td text-right text-red-500">{{ fmt(expenses.totals?.total_amount) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </template>
    </div>

    <!-- ── SALARY REPORT ── -->
    <div v-if="activeTab === 'salary'" class="space-y-4">
      <div v-if="loading.salary" class="py-16 text-center text-gray-400">Loading…</div>
      <template v-else-if="salary">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Payments</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ salary.totals?.count ?? 0 }}</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Basic</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ fmt(salary.totals?.total_basic) }}</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Allowances</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ fmt(salary.totals?.total_allowances) }}</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Net Paid</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ fmt(salary.totals?.total_net) }}</p>
          </div>
        </div>

        <div class="card p-0 overflow-hidden">
          <div class="px-5 py-3 border-b bg-gray-50">
            <h4 class="font-semibold text-gray-700 text-sm">Salary Payment Detail</h4>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="table-th">Payment #</th>
                  <th class="table-th">Employee</th>
                  <th class="table-th">Designation</th>
                  <th class="table-th">Period</th>
                  <th class="table-th">Date</th>
                  <th class="table-th text-right">Basic</th>
                  <th class="table-th text-right">Allowances</th>
                  <th class="table-th text-right">Deductions</th>
                  <th class="table-th text-right">Net</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-if="!salary.rows?.length">
                  <td colspan="9" class="table-td text-center text-gray-400">No salary payments in this period</td>
                </tr>
                <tr v-for="r in salary.rows" :key="r.id" class="hover:bg-gray-50">
                  <td class="table-td font-mono text-xs">{{ r.payment_number }}</td>
                  <td class="table-td font-medium">{{ r.employee?.name ?? '—' }}</td>
                  <td class="table-td text-xs text-gray-500">{{ r.employee?.designation ?? '—' }}</td>
                  <td class="table-td text-xs text-gray-500">{{ r.period_from }} – {{ r.period_to }}</td>
                  <td class="table-td text-xs text-gray-500">{{ r.payment_date }}</td>
                  <td class="table-td text-right">{{ fmt(r.basic_salary) }}</td>
                  <td class="table-td text-right text-emerald-600">{{ fmt(r.allowances) }}</td>
                  <td class="table-td text-right text-red-500">{{ fmt(r.deductions) }}</td>
                  <td class="table-td text-right font-bold text-blue-600">{{ fmt(r.net_salary) }}</td>
                </tr>
              </tbody>
              <tfoot class="border-t-2 border-gray-200 bg-gray-50 font-semibold">
                <tr>
                  <td colspan="5" class="table-td text-right text-gray-600">Totals</td>
                  <td class="table-td text-right">{{ fmt(salary.totals?.total_basic) }}</td>
                  <td class="table-td text-right text-emerald-600">{{ fmt(salary.totals?.total_allowances) }}</td>
                  <td class="table-td text-right text-red-500">{{ fmt(salary.totals?.total_deductions) }}</td>
                  <td class="table-td text-right text-blue-600">{{ fmt(salary.totals?.total_net) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import axios from 'axios'
import {
  ShoppingCartIcon,
  TruckIcon,
  CubeIcon,
  StarIcon,
  ChartBarIcon,
  ReceiptPercentIcon,
  UserGroupIcon,
  PrinterIcon,
  ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline'

// ── date state ──────────────────────────────────────────────
const today     = new Date().toISOString().slice(0, 10)
const monthStart = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10)
const dateFrom  = ref(monthStart)
const dateTo    = ref(today)
const activeRange = ref('This Month')

const quickRanges = [
  { label: 'Today',      days: 0, type: 'today' },
  { label: 'This Week',  days: 6, type: 'back' },
  { label: 'This Month', days: null, type: 'month' },
  { label: 'Last 3 Months', days: 89, type: 'back' },
  { label: 'This Year',  days: null, type: 'year' },
]

function setQuickRange(r) {
  activeRange.value = r.label
  const now = new Date()
  if (r.type === 'today') {
    dateFrom.value = dateTo.value = today
  } else if (r.type === 'month') {
    dateFrom.value = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10)
    dateTo.value   = today
  } else if (r.type === 'year') {
    dateFrom.value = new Date(now.getFullYear(), 0, 1).toISOString().slice(0, 10)
    dateTo.value   = today
  } else {
    const d = new Date(now)
    d.setDate(d.getDate() - r.days)
    dateFrom.value = d.toISOString().slice(0, 10)
    dateTo.value   = today
  }
  loadTab(activeTab.value)
}

function applyDates() {
  activeRange.value = ''
  loadTab(activeTab.value)
}

// ── tabs ────────────────────────────────────────────────────
const tabs = [
  { id: 'sales',        label: 'Sales',         icon: ShoppingCartIcon  },
  { id: 'purchases',    label: 'Purchases',      icon: TruckIcon         },
  { id: 'inventory',    label: 'Inventory',      icon: CubeIcon          },
  { id: 'top-products', label: 'Top Parts',      icon: StarIcon          },
  { id: 'profit-loss',  label: 'Profit & Loss',  icon: ChartBarIcon      },
  { id: 'expenses',     label: 'Expenses',       icon: ReceiptPercentIcon },
  { id: 'salary',       label: 'Salary',         icon: UserGroupIcon     },
]

const activeTab = ref('sales')

// ── data ────────────────────────────────────────────────────
const loading     = reactive({})
const sales       = ref(null)
const purchases   = ref(null)
const inventory   = ref(null)
const topProducts = ref(null)
const profitLoss  = ref(null)
const expenses    = ref(null)
const salary      = ref(null)

const dataMap = { sales, purchases, inventory, 'top-products': topProducts, 'profit-loss': profitLoss, expenses, salary }
const apiMap  = {
  sales:          () => `/api/reports/sales?date_from=${dateFrom.value}&date_to=${dateTo.value}`,
  purchases:      () => `/api/reports/purchases?date_from=${dateFrom.value}&date_to=${dateTo.value}`,
  inventory:      () => `/api/reports/inventory`,
  'top-products': () => `/api/reports/top-products?date_from=${dateFrom.value}&date_to=${dateTo.value}`,
  'profit-loss':  () => `/api/reports/profit-loss?date_from=${dateFrom.value}&date_to=${dateTo.value}`,
  expenses:       () => `/api/reports/expenses?date_from=${dateFrom.value}&date_to=${dateTo.value}`,
  salary:         () => `/api/reports/salary?date_from=${dateFrom.value}&date_to=${dateTo.value}`,
}

async function loadTab(tab) {
  loading[tab] = true
  try {
    const { data } = await axios.get(apiMap[tab]())
    dataMap[tab].value = data
  } catch (e) {
    console.error('Report load failed', e)
  } finally {
    loading[tab] = false
  }
}

// ── helpers ─────────────────────────────────────────────────
const currency = new Intl.NumberFormat('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
function fmt(v) { return 'Rs ' + currency.format(v ?? 0) }
function fmtDate(d) { return d ? new Date(d).toLocaleDateString('en-GB') : '—' }
function statusClass(s) {
  return {
    paid:    'bg-emerald-100 text-emerald-700',
    partial: 'bg-yellow-100 text-yellow-700',
    pending: 'bg-gray-100 text-gray-600',
    unpaid:  'bg-red-100 text-red-600',
    credit:  'bg-blue-100 text-blue-700',
  }[s] ?? 'bg-gray-100 text-gray-600'
}

// ── CSV export ───────────────────────────────────────────────
function exportCsv(tab) {
  const d = dataMap[tab]?.value
  if (!d) return

  let rows = []
  let headers = []

  if (tab === 'sales') {
    headers = ['Invoice', 'Customer', 'Vehicle', 'Date', 'Method', 'Status', 'Total', 'Paid']
    rows = (d.rows ?? []).map(r => [
      r.invoice_number, r.customer?.name ?? '', r.customer?.vehicle_number ?? '',
      fmtDate(r.sold_at), r.payment_method ?? '', r.payment_status,
      r.total, r.amount_paid,
    ])
  } else if (tab === 'purchases') {
    headers = ['PO Number', 'Supplier', 'Date', 'Method', 'Status', 'Subtotal', 'Total']
    rows = (d.rows ?? []).map(r => [
      r.purchase_number, r.supplier?.name ?? '', fmtDate(r.purchased_at),
      r.payment_method ?? '', r.status, r.subtotal, r.total,
    ])
  } else if (tab === 'inventory') {
    headers = ['SKU', 'Name', 'Category', 'Brand', 'Stock', 'Min Stock', 'Cost Price', 'Sell Price', 'Stock Value']
    rows = (d.products ?? []).map(p => [
      p.sku, p.name, p.part_category?.name ?? '', p.brand?.name ?? '',
      p.stock_quantity, p.min_stock_level, p.purchase_price, p.selling_price,
      (p.purchase_price * p.stock_quantity).toFixed(2),
    ])
  } else if (tab === 'top-products') {
    headers = ['#', 'SKU', 'Name', 'Category', 'Qty Sold', 'Avg Price', 'Revenue']
    rows = (d.rows ?? []).map((r, i) => [i + 1, r.sku, r.name, r.category ?? '', r.qty_sold, r.avg_price, r.revenue])
  } else if (tab === 'expenses') {
    headers = ['Date', 'Category', 'Description', 'Method', 'Reference', 'Amount']
    rows = (d.rows ?? []).map(r => [
      r.expense_date, r.category, r.description ?? '', r.payment_method ?? '', r.reference_number ?? '', r.amount,
    ])
  }

  const csv = [headers, ...rows].map(r => r.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href     = url
  a.download = `${tab}-report-${dateFrom.value}-to-${dateTo.value}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

function printReport() {
  window.print()
}

onMounted(() => loadTab('sales'))
</script>
