<script setup>
import { Head, Link } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import {
    CheckCircle2, AlertTriangle, Building2,
    LogIn, LogOut, ShoppingCart, CreditCard,
    TrendingUp, TrendingDown, Wrench, ChevronRight,
    ClipboardList, Clock, Banknote
} from 'lucide-vue-next'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { useDarkMode } from '@/Composables/useDarkMode'

const props = defineProps({
    user:              Object,
    myTasks:           Array,

    // Receptionist
    todayCheckins:      Array,
    todayCheckouts:     Array,
    currentlyOccupied:  Array,
    availability:       Object,

    // Manager
    openComplaints:    Number,
    pendingMaintenance:Number,
    openTasks:         Number,
    recentComplaints:  Array,
    charts:            Object,

    // Accountant
    pendingPayments:   Object,
    monthRevenue:      Number,
    monthExpenses:     Number,

    // Procurement Officer
    procurement:       Object,
})

const page = usePage()
const permissions = computed(() => page.props.auth.user?.permissions ?? [])
const { isDark } = useDarkMode()

// ── Operational charts (manager) — all non-financial ──
const axisColor = '#9ca3af'

const occupancySeries = computed(() => [{
    name: 'Occupancy',
    data: (props.charts?.occupancyTrend ?? []).map(p => p.rate),
}])
const occupancyOptions = computed(() => ({
    chart: { toolbar: { show: false }, sparkline: { enabled: false }, fontFamily: 'inherit', background: 'transparent' },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    colors: ['#3b82f6'],
    stroke: { curve: 'smooth', width: 2.5 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02 } },
    dataLabels: { enabled: false },
    grid: { borderColor: isDark.value ? '#1f2937' : '#f3f4f6', yaxis: { lines: { show: true } }, padding: { left: 4, right: 4, top: -8 } },
    xaxis: {
        categories: (props.charts?.occupancyTrend ?? []).map(p => p.label),
        labels: { style: { colors: axisColor, fontSize: '10px' }, rotate: 0, hideOverlappingLabels: true },
        axisBorder: { show: false }, axisTicks: { show: false },
        tooltip: { enabled: false },
    },
    yaxis: { min: 0, max: 100, tickAmount: 4, labels: { style: { colors: axisColor, fontSize: '11px' }, formatter: (v) => `${Math.round(v)}%` } },
    tooltip: { theme: isDark.value ? 'dark' : 'light', y: { formatter: (v) => `${v}% occupied` } },
}))

const volumeSeries = computed(() => [{
    name: 'Bookings',
    data: (props.charts?.bookingVolume ?? []).map(p => p.count),
}])
const volumeOptions = computed(() => ({
    chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    colors: ['#6366f1'],
    plotOptions: { bar: { borderRadius: 6, columnWidth: '52%' } },
    dataLabels: { enabled: false },
    grid: { borderColor: isDark.value ? '#1f2937' : '#f3f4f6', yaxis: { lines: { show: true } }, padding: { left: 4, right: 4, top: -8 } },
    xaxis: {
        categories: (props.charts?.bookingVolume ?? []).map(p => p.month),
        labels: { style: { colors: axisColor, fontSize: '11px' } },
        axisBorder: { show: false }, axisTicks: { show: false },
    },
    yaxis: { labels: { style: { colors: axisColor, fontSize: '11px' }, formatter: (v) => `${Math.round(v)}` } },
    tooltip: { theme: isDark.value ? 'dark' : 'light', y: { formatter: (v) => `${v} booking${v !== 1 ? 's' : ''}` } },
}))

const statusPalette = { Confirmed: '#10b981', 'Checked in': '#3b82f6', Completed: '#6b7280', Cancelled: '#ef4444' }
const statusMix = computed(() => (props.charts?.statusMix ?? []).filter(s => s.value > 0))
const statusSeries = computed(() => statusMix.value.map(s => s.value))
const statusTotal = computed(() => statusSeries.value.reduce((a, b) => a + b, 0))
const statusOptions = computed(() => ({
    chart: { fontFamily: 'inherit', background: 'transparent' },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    labels: statusMix.value.map(s => s.label),
    colors: statusMix.value.map(s => statusPalette[s.label] ?? '#9ca3af'),
    stroke: { width: 2, colors: [isDark.value ? '#111827' : '#ffffff'] },
    legend: { show: false },
    dataLabels: { enabled: false },
    plotOptions: { pie: { donut: { size: '72%', labels: {
        show: true,
        total: { show: true, label: 'Total', color: axisColor, fontSize: '12px', formatter: () => statusTotal.value },
        value: { fontSize: '22px', fontWeight: 600, color: isDark.value ? '#fff' : '#111827', offsetY: 4 },
    } } } },
    tooltip: { theme: isDark.value ? 'dark' : 'light' },
}))

const greeting = computed(() => {
    const h = new Date().getHours()
    if (h < 12) return 'Good morning'
    if (h < 17) return 'Good afternoon'
    return 'Good evening'
})

const roleLabels = {
    'manager':             'Manager',
    'accountant':          'Accountant',
    'ceo':                 'CEO',
    'head-of-procurement': 'Procurement Officer',
    'receptionist':        'Receptionist',
    'staff':               'Staff',
}

const priorityColors = {
    low:    'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
    medium: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400',
    high:   'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400',
    urgent: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400',
}

function formatAmount(n) {
    return '₦' + Number(n).toLocaleString('en-NG', { minimumFractionDigits: 0 })
}

function formatDate(d) {
    return d ? new Date(d).toLocaleDateString('en-NG', {
        day: 'numeric', month: 'short'
    }) : null
}
</script>

<template>
    <ManageLayout>
        <Head title="Home" />

        <div class="p-6 lg:p-8">

            <!-- Greeting -->
            <div class="mb-8">
                <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-2">
                    {{ greeting }}, {{ user.name.split(' ')[0] }} 👋
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-400">
                    {{ roleLabels[user.role] ?? user.role }}
                    <span v-if="user.building">· {{ user.building }}</span>
                    · {{ new Date().toLocaleDateString('en-NG', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
                </p>
            </div>

            <!-- ── Receptionist: Availability + Today's activity ── -->
            <template v-if="availability">
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Units</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ availability.total }}</p>
                    </div>
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 rounded-2xl p-5">
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mb-1">Available</p>
                        <p class="text-3xl font-bold text-emerald-700 dark:text-emerald-400">
                            {{ availability.total - availability.occupied }}
                        </p>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl p-5">
                        <p class="text-xs text-blue-600 dark:text-blue-400 mb-1">Occupied</p>
                        <p class="text-3xl font-bold text-blue-700 dark:text-blue-400">{{ availability.occupied }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                    <!-- Today's check-ins -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <LogIn class="w-4 h-4 text-emerald-500" />
                                Today's Check-ins ({{ todayCheckins?.length ?? 0 }})
                            </h2>
                            <Link :href="route('manage.availability.index')"
                                  class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                View board →
                            </Link>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                            <div v-for="booking in todayCheckins" :key="booking.id"
                                 class="px-5 py-3 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ booking.guest_name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Unit {{ booking.unit?.unit_number }} · {{ booking.unit_type?.name }}
                                    </p>
                                </div>
                                <span :class="booking.status === 'checked_in'
                                    ? 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-400'
                                    : 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400'"
                                      class="text-xs font-medium px-2 py-0.5 rounded-full shrink-0">
                                    {{ booking.status === 'checked_in' ? 'Checked In' : 'Pending' }}
                                </span>
                            </div>
                            <div v-if="!todayCheckins?.length"
                                 class="px-5 py-6 text-center text-sm text-gray-400">
                                No check-ins today
                            </div>
                        </div>
                    </div>

                    <!-- Today's check-outs -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <LogOut class="w-4 h-4 text-amber-500" />
                                Today's Check-outs ({{ todayCheckouts?.length ?? 0 }})
                            </h2>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                            <div v-for="booking in todayCheckouts" :key="booking.id"
                                 class="px-5 py-3 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ booking.guest_name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Unit {{ booking.unit?.unit_number }}
                                    </p>
                                </div>
                                <Link :href="route('manage.bookings.show', booking.booking_reference)"
                                      class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 shrink-0">
                                    View →
                                </Link>
                            </div>
                            <div v-if="!todayCheckouts?.length"
                                 class="px-5 py-6 text-center text-sm text-gray-400">
                                No check-outs today
                            </div>
                        </div>
                    </div>

                    <!-- Currently Occupied -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden sm:col-span-2">
                        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <Building2 class="w-4 h-4 text-blue-500" />
                                Currently Occupied
                                <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400">
                {{ currentlyOccupied?.length ?? 0 }} unit{{ (currentlyOccupied?.length ?? 0) !== 1 ? 's' : '' }}
            </span>
                            </h2>
                            <Link :href="route('manage.availability.index')"
                                  class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                                View board →
                            </Link>
                        </div>

                        <!-- Empty state -->
                        <div v-if="!currentlyOccupied?.length" class="px-5 py-6 text-center text-sm text-gray-400">
                            No units currently occupied
                        </div>

                        <!-- Table -->
                        <div v-else class="divide-y divide-gray-100 dark:divide-gray-800">
                            <Link
                                v-for="booking in currentlyOccupied"
                                :key="booking.id"
                                :href="route('manage.bookings.show', booking.booking_reference)"
                                class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors">

                                <!-- Unit badge -->
                                <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 flex items-center justify-center shrink-0">
                                    <span class="text-xs font-bold text-blue-700 dark:text-blue-400">{{ booking.unit_number }}</span>
                                </div>

                                <!-- Guest info -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ booking.guest_name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ booking.unit_type }}
                                        <span v-if="currentlyOccupied.length > 1 && booking.building"> · {{ booking.building }}</span>
                                    </p>
                                </div>

                                <!-- Checkout date -->
                                <div class="text-right shrink-0">
                                    <p class="text-xs font-medium text-gray-900 dark:text-white">
                                        Out {{ new Date(booking.check_out).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) }}
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ booking.guest_phone }}</p>
                                </div>
                            </Link>
                        </div>
                    </div>


                </div>
            </template>

            <!-- ── Manager dashboard ── -->
            <template v-if="openComplaints !== undefined">

                <!-- Overview -->
                <h2 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Needs attention</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-8">
                    <Link :href="route('manage.complaints.index') + '?status=open'"
                          class="flex items-center justify-between gap-3 bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5 hover:border-gray-300 dark:hover:border-gray-700 transition-all">
                        <div>
                            <p class="text-xs font-medium text-red-600 dark:text-red-400 uppercase tracking-wider flex items-center gap-1.5 mb-2">
                                <AlertTriangle class="w-3.5 h-3.5" /> Open complaints
                            </p>
                            <p class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ openComplaints }}</p>
                        </div>
                        <ChevronRight class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                    </Link>
                    <Link :href="route('manage.maintenance.index') + '?status=pending'"
                          class="flex items-center justify-between gap-3 bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5 hover:border-gray-300 dark:hover:border-gray-700 transition-all">
                        <div>
                            <p class="text-xs font-medium text-amber-600 dark:text-amber-400 uppercase tracking-wider flex items-center gap-1.5 mb-2">
                                <Wrench class="w-3.5 h-3.5" /> Pending maintenance
                            </p>
                            <p class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ pendingMaintenance }}</p>
                        </div>
                        <ChevronRight class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                    </Link>
                    <Link :href="route('manage.tasks.index') + '?view=all'"
                          class="flex items-center justify-between gap-3 bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5 hover:border-gray-300 dark:hover:border-gray-700 transition-all">
                        <div>
                            <p class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wider flex items-center gap-1.5 mb-2">
                                <CheckCircle2 class="w-3.5 h-3.5" /> Open tasks
                            </p>
                            <p class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ openTasks }}</p>
                        </div>
                        <ChevronRight class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                    </Link>
                </div>

                <!-- Insights (operational charts, no financials) -->
                <template v-if="charts">
                    <h2 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Insights</h2>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
                        <!-- Occupancy trend -->
                        <div class="lg:col-span-2 bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Occupancy trend</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 mb-2">Share of units occupied · last 12 weeks</p>
                            <VueApexCharts type="area" height="220" :options="occupancyOptions" :series="occupancySeries" />
                        </div>

                        <!-- Booking status donut -->
                        <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5 flex flex-col">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Booking status</p>
                            <div v-if="statusTotal > 0" class="flex-1 flex flex-col justify-center">
                                <VueApexCharts type="donut" height="180" :options="statusOptions" :series="statusSeries" />
                                <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 mt-3">
                                    <div v-for="s in statusMix" :key="s.label" class="flex items-center gap-1.5 min-w-0">
                                        <span class="w-2 h-2 rounded-full shrink-0" :style="{ background: statusPalette[s.label] ?? '#9ca3af' }" />
                                        <span class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ s.label }}</span>
                                        <span class="text-xs font-medium text-gray-900 dark:text-white ml-auto tabular-nums">{{ s.value }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex-1 flex items-center justify-center text-sm text-gray-400 dark:text-gray-500">No bookings yet</div>
                        </div>

                        <!-- Booking volume -->
                        <div class="lg:col-span-3 bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Booking volume</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 mb-2">New bookings per month · last 6 months</p>
                            <VueApexCharts type="bar" height="200" :options="volumeOptions" :series="volumeSeries" />
                        </div>
                    </div>
                </template>

                <!-- Recent complaints -->
                <div v-if="recentComplaints?.length"
                     class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden mb-6">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Recent Open Complaints</h2>
                        <Link :href="route('manage.complaints.index')" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            View all →
                        </Link>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        <Link v-for="c in recentComplaints" :key="c.id"
                              :href="route('manage.complaints.show', c.id)"
                              class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ c.title }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ c.submitted_by?.name }} · {{ c.building?.name }}
                                </p>
                            </div>
                            <ChevronRight class="w-4 h-4 text-gray-400 shrink-0" />
                        </Link>
                    </div>
                </div>
            </template>

            <!-- ── Accountant: Financial snapshot ── -->
            <template v-if="pendingPayments !== undefined">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 rounded-2xl p-5">
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mb-1 flex items-center gap-1">
                            <TrendingUp class="w-3.5 h-3.5" /> This Month Income
                        </p>
                        <p class="text-lg font-bold text-emerald-700 dark:text-emerald-400">{{ formatAmount(monthRevenue) }}</p>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 rounded-2xl p-5">
                        <p class="text-xs text-red-600 dark:text-red-400 mb-1 flex items-center gap-1">
                            <TrendingDown class="w-3.5 h-3.5" /> This Month Expenses
                        </p>
                        <p class="text-lg font-bold text-red-700 dark:text-red-400">{{ formatAmount(monthExpenses) }}</p>
                    </div>
                    <Link :href="route('manage.financials.index')"
                          class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 rounded-2xl p-5 hover:opacity-90 transition-all">
                        <p class="text-xs text-amber-600 dark:text-amber-400 mb-1 flex items-center gap-1">
                            <CreditCard class="w-3.5 h-3.5" /> Pending Maintenance
                        </p>
                        <p class="text-3xl font-bold text-amber-700 dark:text-amber-400">{{ pendingPayments.maintenance }}</p>
                    </Link>
                    <Link :href="route('manage.financials.index')"
                          class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 rounded-2xl p-5 hover:opacity-90 transition-all">
                        <p class="text-xs text-amber-600 dark:text-amber-400 mb-1 flex items-center gap-1">
                            <ShoppingCart class="w-3.5 h-3.5" /> Pending Procurement
                        </p>
                        <p class="text-3xl font-bold text-amber-700 dark:text-amber-400">{{ pendingPayments.procurement }}</p>
                    </Link>
                </div>
            </template>

            <!-- ── Procurement Officer dashboard ── -->
            <template v-if="procurement">

                <!-- Stat cards -->
                <h2 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Overview</h2>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-8">
                    <Link :href="route('manage.procurement.index') + '?status=pending'"
                          class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5 hover:border-gray-300 dark:hover:border-gray-700 transition-all"
                          :class="procurement.to_review > 0 ? 'ring-1 ring-amber-300/60 dark:ring-amber-700/40' : ''">
                        <p class="text-xs font-medium text-amber-600 dark:text-amber-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <ClipboardList class="w-3.5 h-3.5" /> Awaiting your review
                        </p>
                        <p class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ procurement.to_review }}</p>
                    </Link>
                    <Link :href="route('manage.procurement.index') + '?status=ceo_approved'"
                          class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5 hover:border-gray-300 dark:hover:border-gray-700 transition-all"
                          :class="procurement.to_purchase > 0 ? 'ring-1 ring-violet-300/60 dark:ring-violet-700/40' : ''">
                        <p class="text-xs font-medium text-violet-600 dark:text-violet-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <ShoppingCart class="w-3.5 h-3.5" /> Ready to purchase
                        </p>
                        <p class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ procurement.to_purchase }}</p>
                    </Link>
                    <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                        <p class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <Clock class="w-3.5 h-3.5" /> In approval
                        </p>
                        <p class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ procurement.in_approval }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                        <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <Banknote class="w-3.5 h-3.5" /> Open value
                        </p>
                        <p class="text-xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ formatAmount(procurement.open_value) }}</p>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">{{ procurement.completed_month }} completed this month</p>
                    </div>
                </div>

                <!-- The two action queues -->
                <h2 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Your queue</h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">

                    <!-- To review -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden flex flex-col">
                        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <ClipboardList class="w-4 h-4 text-amber-500" /> To review
                                <span v-if="procurement.to_review > 0" class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400">{{ procurement.to_review }}</span>
                            </h3>
                            <Link :href="route('manage.procurement.index') + '?status=pending'" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">View all →</Link>
                        </div>
                        <div v-if="procurement.reviewQueue.length" class="divide-y divide-gray-100 dark:divide-gray-800 flex-1">
                            <Link v-for="p in procurement.reviewQueue" :key="p.id" :href="route('manage.procurement.show', p.id)"
                                  class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ p.title }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ p.reference }} · {{ p.building?.name }} · {{ p.submitted_by?.name }}</p>
                                </div>
                                <span class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white shrink-0">{{ formatAmount(p.total_amount) }}</span>
                            </Link>
                        </div>
                        <div v-else class="flex-1 flex flex-col items-center justify-center py-10 text-center">
                            <CheckCircle2 class="w-8 h-8 text-emerald-400 mb-2" />
                            <p class="text-sm text-gray-500 dark:text-gray-400">Nothing to review — all caught up.</p>
                        </div>
                    </div>

                    <!-- To purchase -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden flex flex-col">
                        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <ShoppingCart class="w-4 h-4 text-violet-500" /> Ready to purchase
                                <span v-if="procurement.to_purchase > 0" class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-violet-50 dark:bg-violet-500/10 text-violet-600 dark:text-violet-400">{{ procurement.to_purchase }}</span>
                            </h3>
                            <Link :href="route('manage.procurement.index') + '?status=ceo_approved'" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">View all →</Link>
                        </div>
                        <div v-if="procurement.purchaseQueue.length" class="divide-y divide-gray-100 dark:divide-gray-800 flex-1">
                            <Link v-for="p in procurement.purchaseQueue" :key="p.id" :href="route('manage.procurement.show', p.id)"
                                  class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ p.title }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ p.reference }} · {{ p.building?.name }}</p>
                                </div>
                                <span class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white shrink-0">{{ formatAmount(p.total_amount) }}</span>
                            </Link>
                        </div>
                        <div v-else class="flex-1 flex flex-col items-center justify-center py-10 text-center">
                            <ShoppingCart class="w-8 h-8 text-gray-300 dark:text-gray-700 mb-2" />
                            <p class="text-sm text-gray-500 dark:text-gray-400">No approved requests waiting to be purchased.</p>
                        </div>
                    </div>
                </div>
            </template>

            <!-- ── My Tasks (all roles) ── -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <CheckCircle2 class="w-4 h-4" />
                        My Tasks
                    </h2>
                    <Link :href="route('manage.tasks.index') + '?view=mine'"
                          class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        View all →
                    </Link>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    <Link v-for="task in myTasks" :key="task.id"
                          :href="route('manage.tasks.show', task.id)"
                          class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ task.title }}</p>
                                <span v-if="task.is_overdue"
                                      class="text-xs text-red-500 flex items-center gap-0.5 shrink-0">
                                    <AlertTriangle class="w-3 h-3" /> Overdue
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-400">
                                <span :class="[priorityColors[task.priority], 'px-1.5 py-0.5 rounded-full text-xs font-medium']">
                                    {{ task.priority }}
                                </span>
                                <span v-if="task.due_date">Due {{ formatDate(task.due_date) }}</span>
                                <span v-if="task.progress > 0">· {{ task.progress }}% done</span>
                            </div>
                        </div>
                        <ChevronRight class="w-4 h-4 text-gray-400 shrink-0" />
                    </Link>
                    <div v-if="!myTasks?.length"
                         class="px-5 py-8 text-center text-sm text-gray-400">
                        No tasks assigned to you.
                    </div>
                </div>
            </div>
        </div>
    </ManageLayout>
</template>
