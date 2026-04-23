<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import { BarChart3, TrendingUp, TrendingDown, Building2, Calendar, DollarSign, Users, Clock, ArrowUpRight, ArrowDownRight, Minus } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    tab:                String,
    buildings:          Array,
    filters:            Object,
    canViewAnalytics:   Boolean,
    canViewFinancials:  Boolean,
    // Occupancy tab
    occupancy:          Object,
    // Revenue tab
    revenue:            Object,
    // Financial tab
    financial:          Object,
})

// ── Filters ───────────────────────────────────────────────────
const activeTab    = ref(props.tab)
const selectedYear = ref(props.filters.year)
const selectedMonth = ref(props.filters.month)
const selectedBuilding = ref(props.filters.building_id || '')

const months = [
    { value: 1, label: 'January' }, { value: 2, label: 'February' },
    { value: 3, label: 'March' },   { value: 4, label: 'April' },
    { value: 5, label: 'May' },     { value: 6, label: 'June' },
    { value: 7, label: 'July' },    { value: 8, label: 'August' },
    { value: 9, label: 'September' },{ value: 10, label: 'October' },
    { value: 11, label: 'November' },{ value: 12, label: 'December' },
]

const years = computed(() => {
    const y = new Date().getFullYear()
    return Array.from({ length: 4 }, (_, i) => y - i)
})

function navigate(tab = activeTab.value) {
    router.get(route('manage.analytics.index'), {
        tab,
        year:        selectedYear.value,
        month:       selectedMonth.value,
        building_id: selectedBuilding.value || undefined,
    }, { preserveState: true, replace: true })
}

watch([selectedYear, selectedMonth, selectedBuilding], () => navigate())

function setTab(tab) {
    activeTab.value = tab
    navigate(tab)
}

// ── Formatters ────────────────────────────────────────────────
const fmt = (n) => new Intl.NumberFormat('en-NG', {
    style: 'currency', currency: 'NGN',
    minimumFractionDigits: 0, maximumFractionDigits: 0,
}).format(n || 0)

const fmtNum = (n) => new Intl.NumberFormat('en-NG').format(n || 0)

const selectedMonthLabel = computed(() => months.find(m => m.value === selectedMonth.value)?.label ?? '')

// ── Color helpers ─────────────────────────────────────────────
function occColor(rate) {
    if (rate >= 80) return { text: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-500' }
    if (rate >= 60) return { text: 'text-blue-600 dark:text-blue-400',       bg: 'bg-blue-500' }
    if (rate >= 40) return { text: 'text-amber-600 dark:text-amber-400',     bg: 'bg-amber-500' }
    return           { text: 'text-red-600 dark:text-red-400',               bg: 'bg-red-500' }
}

// ── Occupancy computed ────────────────────────────────────────
const maxTrendRate = computed(() =>
    Math.max(...(props.occupancy?.monthly_trend?.map(t => t.rate) ?? [0]), 1)
)

// ── Revenue computed ──────────────────────────────────────────
const maxMonthlyRevenue = computed(() =>
    Math.max(...(props.revenue?.monthly_trend?.map(t => t.revenue) ?? [0]), 1)
)

const maxYoy = computed(() =>
    Math.max(...(props.revenue?.yoy?.flatMap(t => [t.this_year, t.last_year]) ?? [0]), 1)
)

// ── Financial computed ────────────────────────────────────────
const maxTrendIncome = computed(() =>
    Math.max(...(props.financial?.trend?.map(t => t.income) ?? [0]), 1)
)

const totalExpenseCategory = computed(() =>
    props.financial?.expense_by_category?.reduce((s, c) => s + c.total, 0) ?? 0
)

const selectClass = "pl-3 pr-8 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
</script>

<template>
    <Head title="Analytics" />

    <div class="p-6 lg:p-8">

        <!-- ── Header ─────────────────────────────────────────── -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Analytics</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ selectedMonthLabel }} {{ selectedYear }}
                    <span v-if="selectedBuilding"> · {{ buildings.find(b => b.id == selectedBuilding)?.name }}</span>
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <select v-model="selectedBuilding" :class="selectClass">
                    <option value="">All properties</option>
                    <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <select v-model="selectedMonth" :class="selectClass">
                    <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                </select>
                <select v-model="selectedYear" :class="selectClass">
                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>
        </div>

        <!-- ── Tabs ───────────────────────────────────────────── -->
        <div class="flex gap-1 mb-6 border-b border-gray-200 dark:border-gray-800">
            <button v-if="canViewAnalytics"
                    @click="setTab('occupancy')"
                    :class="['px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors',
                    activeTab === 'occupancy'
                        ? 'border-gray-900 dark:border-white text-gray-900 dark:text-white'
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300']">
                Occupancy
            </button>
            <button v-if="canViewAnalytics"
                    @click="setTab('revenue')"
                    :class="['px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors',
                    activeTab === 'revenue'
                        ? 'border-gray-900 dark:border-white text-gray-900 dark:text-white'
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300']">
                Revenue
            </button>
            <button v-if="canViewFinancials"
                    @click="setTab('financial')"
                    :class="['px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors',
                    activeTab === 'financial'
                        ? 'border-gray-900 dark:border-white text-gray-900 dark:text-white'
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300']">
                Financial Summary
            </button>
        </div>

        <!-- ══════════════════════════════════════════════════════
             OCCUPANCY TAB
        ══════════════════════════════════════════════════════ -->
        <template v-if="activeTab === 'occupancy' && occupancy">

            <!-- KPI strip -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                <div class="bg-gray-900 dark:bg-white rounded-xl p-5">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Occupancy Rate</p>
                    <p class="text-3xl font-semibold text-white dark:text-gray-900 tracking-tight mb-1">{{ occupancy.overall.rate }}%</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ occupancy.overall.booked_nights }} / {{ occupancy.overall.available_nights }} nights</p>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Avg Stay</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight mb-1">{{ occupancy.avg_length_of_stay }} <span class="text-sm font-normal text-gray-400">nights</span></p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Average length of stay</p>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Lead Time</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight mb-1">{{ occupancy.avg_lead_time }} <span class="text-sm font-normal text-gray-400">days</span></p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Avg booking advance</p>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Cancellation Rate</p>
                    <p class="text-2xl font-semibold tracking-tight mb-1"
                       :class="occupancy.cancellation_rate > 15 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'">
                        {{ occupancy.cancellation_rate }}%
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">This month</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-3 mb-3">
                <!-- 6-month trend -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mb-5">6-Month Trend</p>
                    <div class="space-y-3">
                        <div v-for="t in occupancy.monthly_trend" :key="t.month" class="flex items-center gap-3">
                            <span class="text-xs text-gray-400 w-14 flex-shrink-0">{{ t.month }}</span>
                            <div class="flex-1 h-5 bg-gray-100 dark:bg-gray-800 rounded overflow-hidden">
                                <div class="h-full rounded transition-all" :class="occColor(t.rate).bg"
                                     :style="{ width: (t.rate / maxTrendRate * 100) + '%', minWidth: t.rate > 0 ? '4px' : '0' }"/>
                            </div>
                            <span class="text-xs font-semibold w-10 text-right flex-shrink-0" :class="occColor(t.rate).text">
                                {{ t.rate }}%
                            </span>
                        </div>
                    </div>
                </div>

                <!-- By property table -->
                <div class="lg:col-span-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mb-5">By Property</p>
                    <table class="w-full">
                        <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="pb-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Property</th>
                            <th class="pb-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Booked</th>
                            <th class="pb-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Available</th>
                            <th class="pb-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider w-32">Rate</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800/50">
                        <tr v-for="p in occupancy.by_property" :key="p.property">
                            <td class="py-3 text-sm text-gray-900 dark:text-white">{{ p.property }}</td>
                            <td class="py-3 text-right text-sm text-gray-500 dark:text-gray-400">{{ p.booked_nights }}</td>
                            <td class="py-3 text-right text-sm text-gray-500 dark:text-gray-400">{{ p.available_nights }}</td>
                            <td class="py-3">
                                <div class="flex items-center gap-2 justify-end">
                                    <div class="w-16 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full" :class="occColor(p.occupancy_rate).bg" :style="{ width: p.occupancy_rate + '%' }"/>
                                    </div>
                                    <span class="text-xs font-semibold w-9 text-right" :class="occColor(p.occupancy_rate).text">{{ p.occupancy_rate }}%</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!occupancy.by_property?.length">
                            <td colspan="4" class="py-8 text-center text-sm text-gray-400">No data for this period.</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <!-- ══════════════════════════════════════════════════════
             REVENUE TAB
        ══════════════════════════════════════════════════════ -->
        <template v-if="activeTab === 'revenue' && revenue">

            <!-- KPI strip -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                <div class="bg-gray-900 dark:bg-white rounded-xl p-5">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Total Revenue</p>
                    <p class="text-2xl font-semibold text-white dark:text-gray-900 tracking-tight mb-1">{{ fmt(revenue.total_revenue) }}</p>
                    <p class="text-xs flex items-center gap-1 mt-1"
                       :class="revenue.yoy_growth === null ? 'text-gray-400' : revenue.yoy_growth >= 0 ? 'text-emerald-400' : 'text-red-400'">
                        <component :is="revenue.yoy_growth === null ? Minus : revenue.yoy_growth >= 0 ? ArrowUpRight : ArrowDownRight" class="w-3 h-3"/>
                        {{ revenue.yoy_growth === null ? 'No prior year data' : Math.abs(revenue.yoy_growth) + '% vs last year' }}
                    </p>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Total Bookings</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight mb-1">{{ fmtNum(revenue.total_bookings) }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Paid bookings</p>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">ADR</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight mb-1">{{ fmt(revenue.adr) }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Average daily rate</p>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">YoY Growth</p>
                    <p class="text-2xl font-semibold tracking-tight mb-1"
                       :class="revenue.yoy_growth === null ? 'text-gray-400 dark:text-gray-500' : revenue.yoy_growth >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                        {{ revenue.yoy_growth === null ? '—' : (revenue.yoy_growth >= 0 ? '+' : '') + revenue.yoy_growth + '%' }}
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">vs {{ selectedYear - 1 }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-3">

                <!-- 12-month revenue bars -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mb-5">12-Month Revenue</p>
                    <div class="flex items-end gap-1 h-32 mb-3">
                        <div v-for="t in revenue.monthly_trend" :key="t.month" class="flex-1 flex flex-col items-center gap-1 h-full justify-end">
                            <div class="w-full bg-gray-900 dark:bg-white rounded-sm transition-all"
                                 :style="{ height: maxMonthlyRevenue > 0 ? Math.max((t.revenue / maxMonthlyRevenue) * 100, t.revenue > 0 ? 4 : 0) + '%' : '0%' }"/>
                        </div>
                    </div>
                    <div class="flex gap-1">
                        <div v-for="t in revenue.monthly_trend" :key="t.month" class="flex-1 text-center">
                            <span class="text-[9px] text-gray-400 leading-none">{{ t.month.split(' ')[0] }}</span>
                        </div>
                    </div>
                </div>

                <!-- YoY comparison -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Year-on-Year</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">{{ selectedYear }} vs {{ selectedYear - 1 }}</p>
                    <div class="flex items-end gap-1.5 h-32 mb-3">
                        <div v-for="t in revenue.yoy" :key="t.month" class="flex-1 flex gap-0.5 items-end h-full">
                            <div class="flex-1 bg-gray-900 dark:bg-white rounded-sm transition-all"
                                 :style="{ height: maxYoy > 0 ? Math.max((t.this_year / maxYoy) * 100, t.this_year > 0 ? 4 : 0) + '%' : '0%' }"/>
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-sm transition-all"
                                 :style="{ height: maxYoy > 0 ? Math.max((t.last_year / maxYoy) * 100, t.last_year > 0 ? 4 : 0) + '%' : '0%' }"/>
                        </div>
                    </div>
                    <div class="flex gap-1.5">
                        <div v-for="t in revenue.yoy" :key="t.month" class="flex-1 text-center">
                            <span class="text-[9px] text-gray-400 leading-none">{{ t.month }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 mt-3">
                        <div class="flex items-center gap-1.5"><div class="w-3 h-3 rounded-sm bg-gray-900 dark:bg-white"/><span class="text-xs text-gray-500">{{ selectedYear }}</span></div>
                        <div class="flex items-center gap-1.5"><div class="w-3 h-3 rounded-sm bg-gray-200 dark:bg-gray-700"/><span class="text-xs text-gray-500">{{ selectedYear - 1 }}</span></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                <!-- By building -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Revenue by Property</p>
                    <table class="w-full">
                        <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="pb-2 text-left text-xs font-medium text-gray-400 uppercase">Property</th>
                            <th class="pb-2 text-right text-xs font-medium text-gray-400 uppercase">Revenue</th>
                            <th class="pb-2 text-right text-xs font-medium text-gray-400 uppercase">ADR</th>
                            <th class="pb-2 text-right text-xs font-medium text-gray-400 uppercase">Bookings</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800/50">
                        <tr v-for="r in revenue.by_building" :key="r.property">
                            <td class="py-2.5 text-sm text-gray-900 dark:text-white">{{ r.property }}</td>
                            <td class="py-2.5 text-right text-sm font-medium text-gray-900 dark:text-white">{{ fmt(r.revenue) }}</td>
                            <td class="py-2.5 text-right text-sm text-gray-500 dark:text-gray-400">{{ fmt(r.adr) }}</td>
                            <td class="py-2.5 text-right text-sm text-gray-500 dark:text-gray-400">{{ r.bookings }}</td>
                        </tr>
                        <tr v-if="!revenue.by_building?.length">
                            <td colspan="4" class="py-8 text-center text-sm text-gray-400">No data for {{ selectedYear }}.</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <!-- By unit type -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Revenue by Unit Type</p>
                    <table class="w-full">
                        <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="pb-2 text-left text-xs font-medium text-gray-400 uppercase">Type</th>
                            <th class="pb-2 text-right text-xs font-medium text-gray-400 uppercase">Revenue</th>
                            <th class="pb-2 text-right text-xs font-medium text-gray-400 uppercase">ADR</th>
                            <th class="pb-2 text-right text-xs font-medium text-gray-400 uppercase">Bookings</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800/50">
                        <tr v-for="r in revenue.by_unit_type" :key="r.unit_type">
                            <td class="py-2.5">
                                <p class="text-sm text-gray-900 dark:text-white">{{ r.unit_type }}</p>
                                <p class="text-xs text-gray-400">{{ r.bedroom }}</p>
                            </td>
                            <td class="py-2.5 text-right text-sm font-medium text-gray-900 dark:text-white">{{ fmt(r.revenue) }}</td>
                            <td class="py-2.5 text-right text-sm text-gray-500 dark:text-gray-400">{{ fmt(r.adr) }}</td>
                            <td class="py-2.5 text-right text-sm text-gray-500 dark:text-gray-400">{{ r.bookings }}</td>
                        </tr>
                        <tr v-if="!revenue.by_unit_type?.length">
                            <td colspan="4" class="py-8 text-center text-sm text-gray-400">No data for {{ selectedYear }}.</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <!-- ══════════════════════════════════════════════════════
             FINANCIAL SUMMARY TAB
        ══════════════════════════════════════════════════════ -->
        <template v-if="activeTab === 'financial' && financial">

            <!-- KPI strip -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                <div class="bg-gray-900 dark:bg-white rounded-xl p-5">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Net Profit</p>
                    <p class="text-2xl font-semibold tracking-tight mb-1"
                       :class="financial.year_net >= 0 ? 'text-white dark:text-gray-900' : 'text-red-400 dark:text-red-600'">
                        {{ fmt(Math.abs(financial.year_net)) }}
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ financial.year_net < 0 ? 'Net loss' : 'Net profit' }} {{ selectedYear }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Total Income</p>
                    <p class="text-2xl font-semibold text-emerald-600 dark:text-emerald-400 tracking-tight mb-1">{{ fmt(financial.year_income) }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ selectedYear }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Total Expenses</p>
                    <p class="text-2xl font-semibold text-red-600 dark:text-red-400 tracking-tight mb-1">{{ fmt(financial.year_expenses) }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ selectedYear }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Profit Margin</p>
                    <p class="text-2xl font-semibold tracking-tight mb-1"
                       :class="financial.profit_margin >= 0 ? 'text-gray-900 dark:text-white' : 'text-red-600 dark:text-red-400'">
                        {{ financial.profit_margin }}%
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ selectedYear }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-3">

                <!-- Income vs Expense trend -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Income vs Expenses</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-5">Last 12 months</p>
                    <div class="space-y-2.5">
                        <div v-for="t in financial.trend" :key="t.month" class="flex items-center gap-3">
                            <span class="text-xs text-gray-400 w-14 flex-shrink-0">{{ t.month }}</span>
                            <div class="flex-1 space-y-1">
                                <div class="h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full transition-all"
                                         :style="{ width: maxTrendIncome > 0 ? Math.max((t.income / maxTrendIncome) * 100, t.income > 0 ? 2 : 0) + '%' : '0%' }"/>
                                </div>
                                <div class="h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-red-400 rounded-full transition-all"
                                         :style="{ width: maxTrendIncome > 0 ? Math.max((t.expenses / maxTrendIncome) * 100, t.expenses > 0 ? 2 : 0) + '%' : '0%' }"/>
                                </div>
                            </div>
                            <span class="text-xs font-medium w-20 text-right flex-shrink-0"
                                  :class="t.net >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'">
                                {{ t.net >= 0 ? '+' : '' }}{{ fmt(t.net) }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 mt-4">
                        <div class="flex items-center gap-1.5"><div class="w-3 h-2 rounded-full bg-emerald-500"/><span class="text-xs text-gray-400">Income</span></div>
                        <div class="flex items-center gap-1.5"><div class="w-3 h-2 rounded-full bg-red-400"/><span class="text-xs text-gray-400">Expenses</span></div>
                    </div>
                </div>

                <!-- Expense breakdown -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Expense Breakdown</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">{{ selectedYear }}</p>
                    <div v-if="financial.expense_by_category?.length" class="space-y-3">
                        <div v-for="c in financial.expense_by_category" :key="c.category">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-gray-600 dark:text-gray-400">{{ c.category }}</span>
                                <span class="text-xs font-medium text-gray-900 dark:text-white">{{ fmt(c.total) }}</span>
                            </div>
                            <div class="h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                <div class="h-full bg-red-400 rounded-full transition-all"
                                     :style="{ width: totalExpenseCategory > 0 ? (c.total / totalExpenseCategory * 100) + '%' : '0%' }"/>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400 text-center py-8">No expense data for {{ selectedYear }}.</p>
                </div>
            </div>

            <!-- Profit margin trend -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                <p class="text-sm font-semibold text-gray-900 dark:text-white mb-5">Profit Margin Trend</p>
                <div class="flex items-end gap-1.5 h-24">
                    <div v-for="t in financial.margin_trend" :key="t.month"
                         class="flex-1 flex flex-col items-center gap-1 h-full justify-end">
                        <div class="w-full rounded-sm transition-all"
                             :class="t.margin >= 0 ? 'bg-emerald-500' : 'bg-red-400'"
                             :style="{ height: Math.max(Math.abs(t.margin), t.margin !== 0 ? 4 : 0) + '%' }"/>
                    </div>
                </div>
                <div class="flex gap-1.5 mt-2">
                    <div v-for="t in financial.margin_trend" :key="t.month" class="flex-1 text-center">
                        <span class="text-[9px] text-gray-400 leading-none">{{ t.month.split(' ')[0] }}</span>
                    </div>
                </div>
            </div>
        </template>

    </div>
</template>
