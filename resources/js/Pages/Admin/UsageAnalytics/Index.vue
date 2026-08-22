<script setup>
import { computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import VueApexCharts from 'vue3-apexcharts'
import { useDarkMode } from '@/Composables/useDarkMode'
import { Activity, Users, TrendingUp, CalendarClock, MousePointerClick } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    range:       Number,
    ranges:      { type: Array, default: () => [7, 30, 90] },
    userId:      { type: Number, default: null },
    users:       { type: Array, default: () => [] },
    stats:       { type: Object, default: () => ({}) },
    perDay:      { type: Array, default: () => [] },
    topPages:    { type: Array, default: () => [] },
    activeUsers: { type: Array, default: () => [] },
    byHour:      { type: Array, default: () => [] },
    recent:      { type: Array, default: () => [] },
})

const { isDark } = useDarkMode()

function apply(params) {
    router.get(route('manage.usage-analytics.index'),
        { range: props.range, user_id: props.userId ?? undefined, ...params },
        { preserveState: true, replace: true, preserveScroll: true })
}
function setRange(r) { apply({ range: r }) }
function onUserChange(e) { apply({ user_id: e.target.value || undefined }) }

const grid   = () => (isDark.value ? '#1f2937' : '#f3f4f6')
const axisCol = '#9ca3af'

const card = 'bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none'

const statTiles = computed(() => [
    { key: 'total_visits', label: 'Page views',   value: props.stats.total_visits ?? 0,  icon: MousePointerClick, accent: 'text-blue-500' },
    { key: 'active_users', label: 'Active users',  value: props.stats.active_users ?? 0,  icon: Users,             accent: 'text-emerald-500' },
    { key: 'avg_per_day',  label: 'Avg / day',     value: props.stats.avg_per_day ?? 0,   icon: TrendingUp,        accent: 'text-violet-500' },
    { key: 'busiest_day',  label: 'Busiest day',   value: props.stats.busiest_day ?? '—', icon: CalendarClock,     accent: 'text-amber-500' },
])

// ── Visits over time (area) ──
const trendSeries = computed(() => [{ name: 'Views', data: props.perDay.map(d => d.count) }])
const trendOptions = computed(() => ({
    chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent', animations: { speed: 400 } },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    colors: ['#6366f1'],
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 100] } },
    dataLabels: { enabled: false },
    markers: { size: 0, hover: { size: 4 } },
    grid: { borderColor: grid(), strokeDashArray: 0, yaxis: { lines: { show: true } }, padding: { left: 4, right: 8, top: -8 } },
    xaxis: {
        categories: props.perDay.map(d => d.date),
        labels: { style: { colors: axisCol, fontSize: '11px' }, rotate: 0, hideOverlappingLabels: true },
        axisBorder: { show: false }, axisTicks: { show: false }, tooltip: { enabled: false },
    },
    yaxis: { labels: { style: { colors: axisCol, fontSize: '11px' }, formatter: (v) => Math.round(v) } },
    tooltip: { theme: isDark.value ? 'dark' : 'light' },
}))

// ── Top pages (horizontal bar) ──
const topSeries = computed(() => [{ name: 'Views', data: props.topPages.map(p => p.count) }])
const topOptions = computed(() => ({
    chart: { type: 'bar', height: Math.max(220, props.topPages.length * 34), toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    colors: ['#10b981'],
    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '62%', distributed: false } },
    dataLabels: { enabled: true, style: { fontSize: '11px', colors: [isDark.value ? '#e5e7eb' : '#374151'] }, offsetX: 18 },
    grid: { borderColor: grid(), xaxis: { lines: { show: false } } },
    xaxis: {
        categories: props.topPages.map(p => p.label),
        labels: { style: { colors: axisCol, fontSize: '11px' } },
        axisBorder: { show: false }, axisTicks: { show: false },
    },
    yaxis: { labels: { style: { colors: isDark.value ? '#d1d5db' : '#374151', fontSize: '12px' } } },
    tooltip: { theme: isDark.value ? 'dark' : 'light' },
}))

// ── Busiest hours (bar) ──
const hourSeries = computed(() => [{ name: 'Views', data: props.byHour.map(h => h.count) }])
const hourOptions = computed(() => ({
    chart: { type: 'bar', height: 220, toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    colors: ['#f59e0b'],
    plotOptions: { bar: { borderRadius: 3, columnWidth: '60%' } },
    dataLabels: { enabled: false },
    grid: { borderColor: grid(), yaxis: { lines: { show: true } }, padding: { top: -10 } },
    xaxis: {
        categories: props.byHour.map(h => h.hour),
        labels: { style: { colors: axisCol, fontSize: '10px' }, formatter: (v) => `${v}h`, hideOverlappingLabels: true },
        axisBorder: { show: false }, axisTicks: { show: false }, tooltip: { enabled: false },
    },
    yaxis: { labels: { style: { colors: axisCol, fontSize: '11px' }, formatter: (v) => Math.round(v) } },
    tooltip: { theme: isDark.value ? 'dark' : 'light', x: { formatter: (v) => `${v}:00 – ${v}:59` } },
}))

const maxUser = computed(() => Math.max(1, ...props.activeUsers.map(u => u.count)))
const initials = (name) => (name ?? '').split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()

function relTime(iso) {
    const diff = Math.floor((Date.now() - new Date(iso)) / 60000)
    if (diff < 1) return 'just now'
    if (diff < 60) return `${diff}m ago`
    if (diff < 1440) return `${Math.floor(diff / 60)}h ago`
    return new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })
}
</script>

<template>
    <Head title="Usage Analytics" />

    <div class="p-4 lg:p-6">

        <!-- Header -->
        <div class="flex items-center justify-between gap-3 flex-wrap mb-6">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-gray-900 dark:bg-white flex items-center justify-center">
                    <Activity class="w-4 h-4 text-white dark:text-gray-900" />
                </div>
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Usage Analytics</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Which pages the team visits across the admin area</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- User filter -->
                <select :value="userId ?? ''" @change="onUserChange"
                        class="h-9 pl-3 pr-8 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 text-xs font-medium text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white max-w-[11rem]">
                    <option value="">All users</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>

                <!-- Range selector -->
                <div class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-900 rounded-xl p-1">
                    <button v-for="r in ranges" :key="r" @click="setRange(r)"
                            :class="range === r ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all">
                        {{ r }} days
                    </button>
                </div>
            </div>
        </div>

        <!-- Stat tiles -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <div v-for="t in statTiles" :key="t.key" :class="card" class="p-4">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">{{ t.label }}</p>
                    <component :is="t.icon" class="w-4 h-4" :class="t.accent" />
                </div>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-2 tabular-nums truncate">{{ t.value }}</p>
            </div>
        </div>

        <!-- Trend -->
        <div :class="card" class="p-5 mb-4">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Page views over time</h2>
            <p class="text-xs text-gray-400 mb-3">Daily views across the last {{ range }} days</p>
            <VueApexCharts type="area" height="280" :options="trendOptions" :series="trendSeries" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
            <!-- Top pages -->
            <div :class="[card, userId ? 'lg:col-span-2' : '']" class="p-5">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Most visited pages</h2>
                <VueApexCharts v-if="topPages.length" type="bar" :height="Math.max(220, topPages.length * 34)" :options="topOptions" :series="topSeries" />
                <p v-else class="text-sm text-gray-400 py-10 text-center">No visits recorded yet.</p>
            </div>

            <!-- Most active users (org-wide only) -->
            <div v-if="!userId" :class="card" class="p-5">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Most active users</h2>
                <div v-if="activeUsers.length" class="space-y-3">
                    <div v-for="(u, i) in activeUsers" :key="i" class="flex items-center gap-3">
                        <span class="shrink-0 w-6 text-xs font-semibold tabular-nums text-gray-400">{{ i + 1 }}</span>
                        <div class="shrink-0 w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-[11px] font-semibold text-gray-600 dark:text-gray-300">
                            {{ initials(u.name) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ u.name }}</p>
                                <span class="text-xs tabular-nums text-gray-500 dark:text-gray-400">{{ u.count }}</span>
                            </div>
                            <div class="mt-1 flex items-center gap-2">
                                <div class="flex-1 h-1.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                    <div class="h-full rounded-full bg-gray-900 dark:bg-white" :style="{ width: (u.count / maxUser * 100) + '%' }" />
                                </div>
                                <span v-if="u.role" class="text-[10px] text-gray-400 shrink-0">{{ u.role }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-400 py-10 text-center">No active users yet.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Busiest hours -->
            <div :class="card" class="p-5">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Activity by hour</h2>
                <p class="text-xs text-gray-400 mb-3">When the team is most active (24h)</p>
                <VueApexCharts type="bar" height="220" :options="hourOptions" :series="hourSeries" />
            </div>

            <!-- Recent activity -->
            <div :class="card" class="overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-800">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Recent activity</h2>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800 max-h-[420px] overflow-y-auto">
                    <div v-for="(v, i) in recent" :key="i" class="px-5 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm text-gray-900 dark:text-white truncate">
                                <span class="font-medium">{{ v.user }}</span>
                                <span class="text-gray-400"> visited </span>{{ v.page }}
                            </p>
                            <span class="text-[11px] text-gray-400 shrink-0">{{ relTime(v.visited_at) }}</span>
                        </div>
                        <p class="text-[11px] font-mono text-gray-400 dark:text-gray-500 truncate mt-0.5">{{ v.path }}</p>
                    </div>
                    <div v-if="!recent.length" class="px-5 py-10 text-center text-sm text-gray-400">No activity yet.</div>
                </div>
            </div>
        </div>
    </div>
</template>
