<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import { Head, Link } from '@inertiajs/vue3'
import {
    TrendingUp, TrendingDown, ArrowRight, Wallet, BedDouble,
    Wrench, ShoppingCart, Scale, Eye, EyeOff,
} from 'lucide-vue-next'
import { computed } from 'vue'
import { useFinancialVisibility } from '@/Composables/useFinancialVisibility'
import { useDarkMode } from '@/Composables/useDarkMode'
import VueApexCharts from 'vue3-apexcharts'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    stats:             Object,
    revenue:           Object,
    expenses:          Object,
    net:               Object,
    monthlyTrend:      Array,
    revenueByProperty: Array,
    paymentBreakdown:  Object,
    statusBreakdown:   Object,
    receivables:       Number,
    approvals:         Object,
})

const { financialsVisible, toggle } = useFinancialVisibility()
const { isDark } = useDarkMode()

const formatPrice = (price) => {
    if (!financialsVisible.value) return '₦ ••••••'
    const num = price || 0
    const sign = num < 0 ? '-' : ''
    const abs = Math.abs(num)
    if (abs >= 1_000_000) return `${sign}₦${(abs / 1_000_000).toFixed(1)}M`
    if (abs >= 1_000) return `${sign}₦${(abs / 1_000).toFixed(0)}K`
    return sign + new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', minimumFractionDigits: 0 }).format(abs)
}
const formatPriceFull = (price) => {
    if (!financialsVisible.value) return '₦ ••••••'
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', minimumFractionDigits: 0 }).format(price || 0)
}
function formatCompact(n) {
    const v = Math.abs(Number(n) || 0)
    if (v >= 1_000_000) return '₦' + (n / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'm'
    if (v >= 1_000)     return '₦' + (n / 1_000).toFixed(0) + 'k'
    return '₦' + (n ?? 0)
}

const todayGreeting = computed(() => {
    const h = new Date().getHours()
    if (h < 12) return 'Good morning'
    if (h < 17) return 'Good afternoon'
    return 'Good evening'
})
const todayDate = computed(() => new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }))

const netPositive    = computed(() => (props.net?.this_month ?? 0) >= 0)
const approvalsCount = computed(() => (props.approvals?.maintenance_count ?? 0) + (props.approvals?.procurement_count ?? 0))

// ── Income vs expense trend (area) ──
const trendSeries = computed(() => [
    { name: 'Income',  data: (props.monthlyTrend ?? []).map(m => Math.round(m.income)) },
    { name: 'Expense', data: (props.monthlyTrend ?? []).map(m => Math.round(m.expense)) },
])
const trendOptions = computed(() => ({
    chart: { type: 'area', toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent', animations: { speed: 400 } },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    colors: ['#10b981', '#f43f5e'],
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.02, stops: [0, 100] } },
    dataLabels: { enabled: false },
    markers: { size: 0, hover: { size: 4 } },
    legend: { show: true, position: 'top', horizontalAlign: 'right', fontSize: '11px', labels: { colors: '#9ca3af' }, markers: { width: 8, height: 8, radius: 4 } },
    grid: { borderColor: isDark.value ? '#1f2937' : '#f3f4f6', yaxis: { lines: { show: false } }, padding: { left: 4, right: 4, top: -8 } },
    xaxis: {
        categories: (props.monthlyTrend ?? []).map(m => m.month),
        labels: { style: { colors: '#9ca3af', fontSize: '11px' } },
        axisBorder: { show: false }, axisTicks: { show: false }, tooltip: { enabled: false },
    },
    yaxis: { labels: { style: { colors: '#9ca3af', fontSize: '11px' }, formatter: formatCompact } },
    tooltip: { theme: isDark.value ? 'dark' : 'light', y: { formatter: (v) => formatPriceFull(v) } },
}))

// ── Booking status (donut) - counts aren't sensitive, always shown ──
const statusMeta = [
    { key: 'confirmed',  label: 'Confirmed',  color: '#10b981' },
    { key: 'checked_in', label: 'Checked in', color: '#3b82f6' },
    { key: 'pending',    label: 'Pending',    color: '#f59e0b' },
    { key: 'completed',  label: 'Completed',  color: '#6b7280' },
    { key: 'paused',     label: 'Paused',     color: '#8b5cf6' },
    { key: 'cancelled',  label: 'Cancelled',  color: '#ef4444' },
]
const activeStatuses = computed(() => statusMeta.filter(s => (props.statusBreakdown?.[s.key] ?? 0) > 0))
const statusSeries = computed(() => activeStatuses.value.map(s => props.statusBreakdown[s.key]))
const statusTotal = computed(() => statusSeries.value.reduce((a, b) => a + b, 0))
const statusOptions = computed(() => ({
    chart: { type: 'donut', fontFamily: 'inherit', background: 'transparent' },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    labels: activeStatuses.value.map(s => s.label),
    colors: activeStatuses.value.map(s => s.color),
    stroke: { width: 2, colors: [isDark.value ? '#111827' : '#ffffff'] },
    dataLabels: { enabled: false },
    legend: { show: false },
    plotOptions: { pie: { donut: { size: '72%', labels: {
        show: true,
        value: { fontSize: '22px', fontWeight: 600, color: isDark.value ? '#fff' : '#111827', offsetY: 4 },
        total: { show: true, label: 'Total', fontSize: '11px', color: '#9ca3af',
            formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0) },
    } } } },
    tooltip: { theme: isDark.value ? 'dark' : 'light' },
}))
</script>

<template>
    <Head title="Dashboard" />

    <div class="p-4 lg:p-6 flex flex-col gap-4 min-h-full">

        <!-- ── Header ── -->
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">{{ todayGreeting }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ todayDate }}</p>
            </div>
            <button @click="toggle"
                    :aria-pressed="!financialsVisible"
                    :aria-label="financialsVisible ? 'Hide financial figures' : 'Show financial figures'"
                    :title="financialsVisible ? 'Hide figures' : 'Figures hidden — click to show'"
                    :class="financialsVisible
                        ? 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800'
                        : 'text-amber-700 dark:text-amber-300 border-amber-300 dark:border-amber-700/60 bg-amber-50 dark:bg-amber-500/10 hover:bg-amber-100 dark:hover:bg-amber-500/15'"
                    class="inline-flex items-center justify-center w-9 h-9 border rounded-lg shadow-sm transition-all shrink-0">
                <Eye v-if="financialsVisible" class="w-4 h-4" /><EyeOff v-else class="w-4 h-4" />
            </button>
        </div>

        <!-- ── Financial pulse ── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">

            <!-- Revenue (hero) -->
            <div class="bg-gray-900 dark:bg-white rounded-lg px-3 py-2">
                <p class="text-[10px] font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Revenue · this month</p>
                <p class="text-lg font-semibold tabular-nums text-white dark:text-gray-900">{{ formatPrice(revenue?.this_month) }}</p>
                <div class="flex items-center gap-1 text-[10px]">
                    <component :is="revenue?.growth_percentage >= 0 ? TrendingUp : TrendingDown" class="w-3 h-3"
                               :class="revenue?.growth_percentage >= 0 ? 'text-green-400 dark:text-green-600' : 'text-red-400 dark:text-red-600'" />
                    <span :class="revenue?.growth_percentage >= 0 ? 'text-green-400 dark:text-green-600' : 'text-red-400 dark:text-red-600'">
                        {{ Math.abs(revenue?.growth_percentage ?? 0) }}%
                    </span>
                    <span class="text-gray-500 dark:text-gray-400">· YTD {{ formatPrice(revenue?.this_year) }}</span>
                </div>
            </div>

            <!-- Expenses -->
            <StatCard label="Expenses · this month" :icon="TrendingDown" label-class="text-rose-600 dark:text-rose-400"
                      :sublabel="`YTD ${formatPrice(expenses?.this_year)}`">
                <template #value><span class="text-rose-600 dark:text-rose-400">{{ formatPrice(expenses?.this_month) }}</span></template>
            </StatCard>

            <!-- Net -->
            <StatCard label="Net · this month" :icon="Scale"
                      :label-class="netPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                      :sublabel="`YTD ${formatPrice(net?.this_year)}`">
                <template #value>
                    <span :class="netPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">{{ formatPrice(net?.this_month) }}</span>
                </template>
            </StatCard>

            <!-- Occupancy (keeps its progress bar) -->
            <div class="bg-white dark:bg-gray-950 border border-gray-200/80 dark:border-gray-800 rounded-lg px-3 py-2">
                <p class="text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500 flex items-center gap-1">
                    <BedDouble class="w-3 h-3 shrink-0" /> Occupancy
                </p>
                <p class="text-lg font-semibold tabular-nums text-gray-900 dark:text-white">{{ stats?.occupancy_rate ?? 0 }}%</p>
                <div class="h-1 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden my-1">
                    <div class="h-full bg-gray-900 dark:bg-white rounded-full transition-all" :style="{ width: (stats?.occupancy_rate ?? 0) + '%' }" />
                </div>
                <p class="text-[10px] text-gray-400 dark:text-gray-500">{{ stats?.occupied_units ?? 0 }} of {{ stats?.total_units ?? 0 }} units</p>
            </div>
        </div>

        <!-- ── The money story ── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">

            <!-- Income vs expenses -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-lg shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Income vs expenses</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Last 12 months</p>
                    </div>
                    <Link :href="route('manage.analytics.index')" class="text-xs text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors flex items-center gap-1">
                        Analytics <ArrowRight class="w-3 h-3" />
                    </Link>
                </div>
                <div v-if="financialsVisible" class="-mx-2">
                    <VueApexCharts type="area" height="240" :options="trendOptions" :series="trendSeries" />
                </div>
                <div v-else class="h-[240px] flex items-center justify-center text-sm text-gray-400 dark:text-gray-500">Figures hidden</div>
            </div>

            <!-- Booking status donut -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-lg shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                <p class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Booking status</p>
                <div v-if="statusTotal > 0">
                    <VueApexCharts type="donut" height="180" :options="statusOptions" :series="statusSeries" />
                    <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 mt-3">
                        <div v-for="s in activeStatuses" :key="s.key" class="flex items-center gap-1.5 min-w-0">
                            <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ background: s.color }" />
                            <span class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ s.label }}</span>
                            <span class="text-xs font-semibold text-gray-900 dark:text-white ml-auto">{{ statusBreakdown[s.key] }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="h-[180px] flex items-center justify-center text-sm text-gray-400 dark:text-gray-500">No bookings yet</div>
            </div>
        </div>

        <!-- ── Portfolio & decisions ── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">

            <!-- Top properties -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-lg shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Top properties</p>
                    <Link :href="route('manage.properties.index')" class="text-xs text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors flex items-center gap-1">
                        View all <ArrowRight class="w-3 h-3" />
                    </Link>
                </div>
                <div v-if="revenueByProperty?.length">
                    <div v-for="(property, index) in revenueByProperty" :key="index"
                         class="flex items-center gap-3 py-2.5 border-b border-gray-100 dark:border-gray-800 last:border-0">
                        <span class="text-xs text-gray-400 dark:text-gray-500 w-4 flex-shrink-0">{{ index + 1 }}</span>
                        <span class="text-xs text-gray-700 dark:text-gray-300 flex-1 truncate">{{ property.property }}</span>
                        <div class="w-16 h-1 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden flex-shrink-0">
                            <div class="h-full bg-emerald-500 rounded-full" :style="{ width: (property.total / (revenueByProperty[0].total || 1) * 100) + '%' }" />
                        </div>
                        <span class="text-xs font-semibold text-gray-900 dark:text-white flex-shrink-0 w-16 text-right tabular-nums">{{ formatPrice(property.total) }}</span>
                    </div>
                </div>
                <p v-else class="text-xs text-gray-400 dark:text-gray-500 py-3">No revenue recorded yet</p>
            </div>

            <!-- Awaiting your approval -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-lg shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                <p class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Awaiting your approval</p>
                <div v-if="approvalsCount > 0" class="space-y-2">
                    <Link v-if="approvals.maintenance_count > 0" :href="route('manage.maintenance.index') + '?status=accountant_approved'"
                          class="flex items-center gap-2.5 p-2.5 rounded-lg bg-amber-50 dark:bg-amber-500/10 hover:bg-amber-100 dark:hover:bg-amber-500/15 transition-colors">
                        <Wrench class="w-4 h-4 text-amber-500 flex-shrink-0" />
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-900 dark:text-white">Maintenance</p>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400">{{ approvals.maintenance_count }} request{{ approvals.maintenance_count !== 1 ? 's' : '' }}</p>
                        </div>
                        <span class="ml-auto text-xs font-semibold tabular-nums text-gray-900 dark:text-white">{{ formatPrice(approvals.maintenance_value) }}</span>
                    </Link>
                    <Link v-if="approvals.procurement_count > 0" :href="route('manage.procurement.index') + '?status=accountant_approved'"
                          class="flex items-center gap-2.5 p-2.5 rounded-lg bg-violet-50 dark:bg-violet-500/10 hover:bg-violet-100 dark:hover:bg-violet-500/15 transition-colors">
                        <ShoppingCart class="w-4 h-4 text-violet-500 flex-shrink-0" />
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-900 dark:text-white">Procurement</p>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400">{{ approvals.procurement_count }} request{{ approvals.procurement_count !== 1 ? 's' : '' }}</p>
                        </div>
                        <span class="ml-auto text-xs font-semibold tabular-nums text-gray-900 dark:text-white">{{ formatPrice(approvals.procurement_value) }}</span>
                    </Link>
                </div>
                <div v-else class="flex flex-col items-center justify-center py-8 text-center">
                    <Scale class="w-7 h-7 text-emerald-400 mb-2" />
                    <p class="text-xs text-gray-500 dark:text-gray-400">Nothing awaiting your sign-off.</p>
                </div>
            </div>

            <!-- Outstanding receivables -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-lg shadow-sm shadow-gray-200/50 dark:shadow-none p-5 flex flex-col">
                <p class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Outstanding receivables</p>
                <div class="flex items-center gap-2 mb-1">
                    <Wallet class="w-4 h-4 text-amber-500" />
                    <span class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ formatPrice(receivables) }}</span>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-500">
                    {{ paymentBreakdown?.pending ?? 0 }} booking{{ (paymentBreakdown?.pending ?? 0) !== 1 ? 's' : '' }} awaiting payment · {{ paymentBreakdown?.paid ?? 0 }} paid
                </p>
                <Link :href="route('manage.bookings.index', { payment_status: 'pending' })"
                      class="mt-auto pt-3 text-xs font-medium text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 inline-flex items-center gap-1">
                    Chase payments <ArrowRight class="w-3 h-3" />
                </Link>
            </div>
        </div>

    </div>
</template>
