<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { AlertTriangle, Wrench, CheckCircle2, ChevronRight } from 'lucide-vue-next'
import { useDarkMode } from '@/Composables/useDarkMode'
import { axisColor } from './helpers'

const props = defineProps({
    openComplaints:     Number,
    pendingMaintenance: Number,
    openTasks:          Number,
    recentComplaints:   Array,
    charts:             Object,
})

const { isDark } = useDarkMode()

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
    stroke: { show: false },
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
</script>

<template>
    <div>
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
    </div>
</template>
