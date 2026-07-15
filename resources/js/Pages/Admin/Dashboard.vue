<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import {
    TrendingUp, TrendingDown, Calendar, ArrowRight, AlertCircle,
    Eye, EyeOff, Plus, BedDouble, CalendarCheck, Wallet,
} from 'lucide-vue-next'
import { computed } from 'vue'
import { useFinancialVisibility } from '@/Composables/useFinancialVisibility'
import { useDarkMode } from '@/Composables/useDarkMode'
import VueApexCharts from 'vue3-apexcharts'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    stats: Object,
    revenue: Object,
    monthlyRevenue: Array,
    revenueByProperty: Array,
    paymentBreakdown: Object,
    statusBreakdown: Object,
    recentBookings: Array,
    upcomingCheckIns: Array,
})

const { financialsVisible, toggle } = useFinancialVisibility()
const { isDark } = useDarkMode()

const formatPrice = (price) => {
    if (!financialsVisible.value) return '₦ ••••••'
    const num = price || 0
    if (num >= 1_000_000) return `₦${(num / 1_000_000).toFixed(1)}M`
    if (num >= 1_000) return `₦${(num / 1_000).toFixed(0)}K`
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', minimumFractionDigits: 0 }).format(num)
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
const formatDateShort = (date) => new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' })

const todayGreeting = computed(() => {
    const h = new Date().getHours()
    if (h < 12) return 'Good morning'
    if (h < 17) return 'Good afternoon'
    return 'Good evening'
})
const todayDate = computed(() => new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }))

const initials = (name) => (name ?? '').split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()

// ── Revenue trend (area) ──────────────────────────────────────
const revenueSeries = computed(() => [{ name: 'Income', data: (props.monthlyRevenue ?? []).map(m => Math.round(m.total)) }])
const revenueOptions = computed(() => ({
    chart: { type: 'area', height: 240, toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent', animations: { speed: 400 } },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    colors: ['#10b981'],
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 100] } },
    dataLabels: { enabled: false },
    markers: { size: 0, hover: { size: 4 } },
    grid: { borderColor: isDark.value ? '#1f2937' : '#f3f4f6', strokeDashArray: 0, yaxis: { lines: { show: false } }, padding: { left: 4, right: 4, top: -8 } },
    xaxis: {
        categories: (props.monthlyRevenue ?? []).map(m => m.month.split(' ')[0]),
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
const statusTotal = computed(() => statusSeries.value.reduce((a, b) => a + b, 0))
</script>

<template>
    <Head title="Dashboard" />

    <div class="p-4 lg:p-6 flex flex-col gap-4 min-h-full">

        <!-- ── Header ── -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">{{ todayGreeting }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ todayDate }} · Here's what's happening</p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="toggle"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                    <EyeOff v-if="financialsVisible" class="w-3.5 h-3.5" /><Eye v-else class="w-3.5 h-3.5" />
                    {{ financialsVisible ? 'Hide figures' : 'Show figures' }}
                </button>
                <Link :href="route('manage.bookings.calendar')"
                      class="inline-flex items-center gap-2 px-3 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                    <Calendar class="w-3.5 h-3.5" /> Calendar
                </Link>
                <Link :href="route('manage.bookings.create')"
                      class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg shadow-sm transition-all">
                    <Plus class="w-3.5 h-3.5" /> New Booking
                </Link>
            </div>
        </div>

        <!-- ── KPI Strip ── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">

            <!-- Hero: This month revenue -->
            <div class="bg-gray-900 dark:bg-white rounded-xl p-5">
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">This month</p>
                <p class="text-2xl font-semibold text-white dark:text-gray-900 tracking-tight mb-1">{{ formatPrice(revenue?.this_month) }}</p>
                <div class="flex items-center gap-1 text-xs">
                    <component :is="revenue?.growth_percentage >= 0 ? TrendingUp : TrendingDown" class="w-3 h-3"
                               :class="revenue?.growth_percentage >= 0 ? 'text-green-400 dark:text-green-600' : 'text-red-400 dark:text-red-600'" />
                    <span :class="revenue?.growth_percentage >= 0 ? 'text-green-400 dark:text-green-600' : 'text-red-400 dark:text-red-600'">
                        {{ Math.abs(revenue?.growth_percentage ?? 0) }}%
                    </span>
                    <span class="text-gray-500 dark:text-gray-400">vs last month</span>
                </div>
            </div>

            <!-- Occupancy -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Occupancy</p>
                    <BedDouble class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                </div>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight mb-1.5">{{ stats?.occupancy_rate ?? 0 }}%</p>
                <div class="h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden mb-1.5">
                    <div class="h-full bg-gray-900 dark:bg-white rounded-full transition-all" :style="{ width: (stats?.occupancy_rate ?? 0) + '%' }" />
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ stats?.occupied_units ?? 0 }} of {{ stats?.total_units ?? 0 }} units</p>
            </div>

            <!-- Active bookings -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Active stays</p>
                    <CalendarCheck class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                </div>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight mb-1">{{ stats?.active_bookings ?? 0 }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ stats?.total_bookings ?? 0 }} total · {{ stats?.total_guests ?? 0 }} guests</p>
            </div>

            <!-- Awaiting payment (actionable) -->
            <Link :href="route('manage.bookings.index', { payment_status: 'pending' })"
                  class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5 hover:border-amber-300 dark:hover:border-amber-700 transition-colors">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-medium text-amber-500 uppercase tracking-wider">Awaiting payment</p>
                    <Wallet class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                </div>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight mb-1">{{ paymentBreakdown?.pending ?? 0 }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ paymentBreakdown?.paid ?? 0 }} paid</p>
            </Link>
        </div>

        <!-- ── Charts row ── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">

            <!-- Revenue trend -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Revenue trend</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Income, last 12 months</p>
                    </div>
                    <Link :href="route('manage.analytics.index')" class="text-xs text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors flex items-center gap-1">
                        Analytics <ArrowRight class="w-3 h-3" />
                    </Link>
                </div>
                <div v-if="financialsVisible" class="-mx-2">
                    <VueApexCharts type="area" height="240" :options="revenueOptions" :series="revenueSeries" />
                </div>
                <div v-else class="h-[240px] flex items-center justify-center text-sm text-gray-400 dark:text-gray-500">Figures hidden</div>
            </div>

            <!-- Booking status donut -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
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

        <!-- ── Secondary row ── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">

            <!-- Needs attention -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Needs attention</p>
                    <Link :href="route('manage.bookings.index')" class="text-xs text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors flex items-center gap-1">
                        All bookings <ArrowRight class="w-3 h-3" />
                    </Link>
                </div>

                <Link v-if="stats?.late_checkouts > 0" :href="route('manage.bookings.late-checkout.index')"
                      class="flex items-center gap-2.5 p-2.5 bg-red-50 dark:bg-red-900/20 rounded-lg mb-3 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                    <AlertCircle class="w-4 h-4 text-red-500 flex-shrink-0" />
                    <span class="text-xs font-medium text-red-700 dark:text-red-400">
                        {{ stats.late_checkouts }} late checkout{{ stats.late_checkouts !== 1 ? 's' : '' }} pending
                    </span>
                    <ArrowRight class="w-3 h-3 text-red-400 ml-auto flex-shrink-0" />
                </Link>

                <p class="text-[10px] font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Upcoming check-ins</p>
                <div v-if="upcomingCheckIns?.length > 0" class="space-y-1">
                    <Link v-for="booking in upcomingCheckIns.slice(0, 5)" :key="booking.id" :href="route('manage.bookings.show', booking.booking_reference)"
                          class="flex items-center gap-2.5 py-2 border-b border-gray-100 dark:border-gray-800 last:border-0 hover:opacity-70 transition-opacity">
                        <div class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center flex-shrink-0 text-[10px] font-semibold text-gray-600 dark:text-gray-400">{{ initials(booking.guest_name) }}</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-900 dark:text-white truncate">{{ booking.guest_name }}</p>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 truncate">{{ booking.building?.name }} · {{ booking.unit_type?.name }}</p>
                        </div>
                        <span class="text-[10px] font-medium text-blue-600 dark:text-blue-400 flex-shrink-0">{{ formatDateShort(booking.check_in) }}</span>
                    </Link>
                </div>
                <p v-else class="text-xs text-gray-400 dark:text-gray-500 py-3">No check-ins in the next 7 days</p>
            </div>

            <!-- Top properties -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
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

            <!-- Recent bookings -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Recent bookings</p>
                    <Link :href="route('manage.bookings.index')" class="text-xs text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors flex items-center gap-1">
                        View all <ArrowRight class="w-3 h-3" />
                    </Link>
                </div>

                <div class="grid grid-cols-2 gap-2 mb-4">
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                        <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Year to date</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white tracking-tight tabular-nums">{{ formatPrice(revenue?.this_year) }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                        <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">All time</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white tracking-tight tabular-nums">{{ formatPrice(revenue?.total) }}</p>
                    </div>
                </div>

                <div v-if="recentBookings?.length">
                    <Link v-for="booking in recentBookings.slice(0, 4)" :key="booking.id" :href="route('manage.bookings.show', booking.booking_reference)"
                          class="flex items-center gap-3 py-2.5 border-b border-gray-100 dark:border-gray-800 last:border-0 hover:opacity-70 transition-opacity">
                        <div class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center flex-shrink-0 text-[10px] font-semibold text-gray-600 dark:text-gray-400">{{ initials(booking.guest_name) }}</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-900 dark:text-white truncate">{{ booking.guest_name }}</p>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 truncate">{{ formatDateShort(booking.check_in) }} – {{ formatDateShort(booking.check_out) }}</p>
                        </div>
                        <span class="text-xs font-semibold text-gray-900 dark:text-white flex-shrink-0 tabular-nums">{{ formatPriceFull(booking.total_amount) }}</span>
                    </Link>
                </div>
                <p v-else class="text-xs text-gray-400 dark:text-gray-500 py-3">No bookings yet</p>
            </div>
        </div>

    </div>
</template>
