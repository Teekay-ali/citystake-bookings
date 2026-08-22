<script setup>
import { computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import VueApexCharts from 'vue3-apexcharts'
import { useDarkMode } from '@/Composables/useDarkMode'
import { ArrowLeft, Timer, Gauge, RefreshCcw, ClipboardCheck } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    range:      Number,
    ranges:     { type: Array, default: () => [7, 30, 90] },
    stats:      { type: Object, default: () => ({}) },
    stages:     { type: Array, default: () => [] },
    trend:      { type: Array, default: () => [] },
    topIssues:  { type: Array, default: () => [] },
    byCategory: { type: Array, default: () => [] },
})

const { isDark } = useDarkMode()
const grid = () => (isDark.value ? '#1f2937' : '#f3f4f6')
const axisCol = '#9ca3af'
const card = 'bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none'

function setRange(r) {
    router.get(route('manage.inspections.analytics'), { range: r }, { preserveState: true, replace: true, preserveScroll: true })
}

// minutes → "3h 20m" / "45m" / "—"
function fmtMins(m) {
    if (m == null) return '—'
    if (m < 60) return `${m}m`
    const h = Math.floor(m / 60), r = m % 60
    return r ? `${h}h ${r}m` : `${h}h`
}

const statTiles = computed(() => [
    { key: 'turnaround', label: 'Avg turnaround', value: fmtMins(props.stats.avg_turnaround), icon: Timer, accent: 'text-blue-500' },
    { key: 'score',      label: 'Avg score',      value: props.stats.avg_score != null ? props.stats.avg_score + '%' : '—', icon: Gauge, accent: 'text-emerald-500' },
    { key: 'cycles',     label: 'Cycles done',    value: props.stats.turnovers ?? 0, icon: RefreshCcw, accent: 'text-violet-500' },
    { key: 'inspections',label: 'Inspections',    value: props.stats.inspections ?? 0, icon: ClipboardCheck, accent: 'text-amber-500' },
])

// ── Turnaround trend (area) ──
const trendSeries = computed(() => [{ name: 'Avg turnaround (min)', data: props.trend.map(d => d.minutes) }])
const trendOptions = computed(() => ({
    chart: { type: 'area', height: 260, toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent', animations: { speed: 400 } },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    colors: ['#3b82f6'],
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 100] } },
    dataLabels: { enabled: false }, markers: { size: 0, hover: { size: 4 } },
    grid: { borderColor: grid(), yaxis: { lines: { show: true } }, padding: { left: 4, right: 8, top: -8 } },
    xaxis: { categories: props.trend.map(d => d.date), labels: { style: { colors: axisCol, fontSize: '11px' }, hideOverlappingLabels: true }, axisBorder: { show: false }, axisTicks: { show: false }, tooltip: { enabled: false } },
    yaxis: { labels: { style: { colors: axisCol, fontSize: '11px' }, formatter: (v) => fmtMins(Math.round(v)) } },
    tooltip: { theme: isDark.value ? 'dark' : 'light', y: { formatter: (v) => fmtMins(Math.round(v)) } },
}))

// ── Stage averages (horizontal bar) ──
const stageSeries = computed(() => [{ name: 'Avg', data: props.stages.map(s => s.minutes) }])
const stageOptions = computed(() => ({
    chart: { type: 'bar', height: 200, toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    colors: ['#8b5cf6'],
    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '55%' } },
    dataLabels: { enabled: true, formatter: (v) => fmtMins(v), style: { fontSize: '11px', colors: [isDark.value ? '#e5e7eb' : '#374151'] }, offsetX: 28 },
    grid: { borderColor: grid(), xaxis: { lines: { show: false } } },
    xaxis: { categories: props.stages.map(s => s.label), labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { style: { colors: isDark.value ? '#d1d5db' : '#374151', fontSize: '12px' } } },
    tooltip: { theme: isDark.value ? 'dark' : 'light', y: { formatter: (v) => fmtMins(v) } },
}))

// ── Top issues (horizontal bar) ──
const issueSeries = computed(() => [{ name: 'Fails', data: props.topIssues.map(i => i.count) }])
const issueOptions = computed(() => ({
    chart: { type: 'bar', height: Math.max(200, props.topIssues.length * 34), toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    colors: ['#ef4444'],
    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%' } },
    dataLabels: { enabled: true, style: { fontSize: '11px', colors: [isDark.value ? '#e5e7eb' : '#374151'] }, offsetX: 16 },
    grid: { borderColor: grid(), xaxis: { lines: { show: false } } },
    xaxis: { categories: props.topIssues.map(i => i.label), labels: { style: { colors: axisCol, fontSize: '11px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { style: { colors: isDark.value ? '#d1d5db' : '#374151', fontSize: '11.5px' }, maxWidth: 220 } },
    tooltip: { theme: isDark.value ? 'dark' : 'light' },
}))

// ── Issues by category (donut) ──
const catColors = { Plumbing: '#3b82f6', Electrical: '#f59e0b', Cleanliness: '#10b981', Appliance: '#8b5cf6', Furniture: '#6b7280', Safety: '#ef4444', General: '#94a3b8' }
const catSeries = computed(() => props.byCategory.map(c => c.count))
const catOptions = computed(() => ({
    chart: { type: 'donut', fontFamily: 'inherit', background: 'transparent' },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    labels: props.byCategory.map(c => c.label),
    colors: props.byCategory.map(c => catColors[c.label] ?? '#94a3b8'),
    stroke: { width: 2, colors: [isDark.value ? '#111827' : '#ffffff'] },
    dataLabels: { enabled: false },
    legend: { position: 'bottom', fontSize: '12px', labels: { colors: axisCol } },
    plotOptions: { pie: { donut: { size: '68%' } } },
    tooltip: { theme: isDark.value ? 'dark' : 'light' },
}))

const hasIssues = computed(() => props.topIssues.length > 0)
</script>

<template>
    <Head title="QC Analytics" />

    <div class="p-4 lg:p-6">

        <!-- Header -->
        <div class="flex items-center justify-between gap-3 flex-wrap mb-6">
            <div class="flex items-center gap-3 min-w-0">
                <Link :href="route('manage.inspections.index')"
                      class="p-1.5 rounded-lg text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-all shrink-0">
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">QC Analytics</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Turnaround times &amp; recurring issues</p>
                </div>
            </div>
            <div class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-900 rounded-xl p-1">
                <button v-for="r in ranges" :key="r" @click="setRange(r)"
                        :class="range === r ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all">{{ r }} days</button>
            </div>
        </div>

        <!-- Stat tiles -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <div v-for="t in statTiles" :key="t.key" :class="card" class="p-4">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">{{ t.label }}</p>
                    <component :is="t.icon" class="w-4 h-4" :class="t.accent" />
                </div>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-2 tabular-nums">{{ t.value }}</p>
            </div>
        </div>

        <!-- Turnaround trend + stages -->
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_20rem] gap-4 mb-4">
            <div :class="card" class="p-5">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Turnaround over time</h2>
                <p class="text-xs text-gray-400 mb-3">Average checkout → guest-ready, last {{ range }} days</p>
                <VueApexCharts type="area" height="260" :options="trendOptions" :series="trendSeries" />
            </div>
            <div :class="card" class="p-5">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Stage averages</h2>
                <VueApexCharts type="bar" height="200" :options="stageOptions" :series="stageSeries" />
            </div>
        </div>

        <!-- Recurring issues -->
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_20rem] gap-4">
            <div :class="card" class="p-5">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Top recurring issues</h2>
                <VueApexCharts v-if="hasIssues" type="bar" :height="Math.max(200, topIssues.length * 34)" :options="issueOptions" :series="issueSeries" />
                <p v-else class="text-sm text-gray-400 py-10 text-center">No failed items in this window.</p>
            </div>
            <div :class="card" class="p-5">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Issues by category</h2>
                <VueApexCharts v-if="byCategory.length" type="donut" height="260" :options="catOptions" :series="catSeries" />
                <p v-else class="text-sm text-gray-400 py-10 text-center">No data yet.</p>
            </div>
        </div>
    </div>
</template>
