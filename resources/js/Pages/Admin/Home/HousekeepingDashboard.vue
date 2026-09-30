<script setup>
import { Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { AlertTriangle, DoorClosed, Sparkles, ClipboardCheck, CheckCircle2, BedDouble, Timer, LogIn } from 'lucide-vue-next'
import { useDarkMode } from '@/Composables/useDarkMode'
import { axisColor, ago, arrivesLabel } from './helpers'

const props = defineProps({ housekeeping: Object })

const { isDark } = useDarkMode()

const hkPipeline = computed(() => {
    const c = props.housekeeping?.counts ?? {}
    return [
        { label: 'Needs cleaning', value: c.needs_cleaning ?? 0, color: '#f43f5e' },
        { label: 'Cleaning',       value: c.cleaning ?? 0,       color: '#f59e0b' },
        { label: 'Awaiting QA',    value: c.ready_for_qa ?? 0,   color: '#0ea5e9' },
        { label: 'Guest ready',    value: c.ready ?? 0,          color: '#10b981' },
    ].filter(s => s.value > 0)
})
const hkPipelineSeries = computed(() => hkPipeline.value.map(s => s.value))
const hkPipelineTotal  = computed(() => hkPipelineSeries.value.reduce((a, b) => a + b, 0))
const hkPipelineOptions = computed(() => ({
    chart: { fontFamily: 'inherit', background: 'transparent' },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    labels: hkPipeline.value.map(s => s.label),
    colors: hkPipeline.value.map(s => s.color),
    stroke: { width: 2, colors: [isDark.value ? '#111827' : '#ffffff'] },
    legend: { show: false },
    dataLabels: { enabled: false },
    plotOptions: { pie: { donut: { size: '72%', labels: {
        show: true,
        total: { show: true, label: 'Units', color: axisColor, fontSize: '12px', formatter: () => hkPipelineTotal.value },
        value: { fontSize: '22px', fontWeight: 600, color: isDark.value ? '#fff' : '#111827', offsetY: 4 },
    } } } },
    tooltip: { theme: isDark.value ? 'dark' : 'light' },
}))

const hkTrendSeries = computed(() => [{ name: 'Cleaned', data: (props.housekeeping?.trend ?? []).map(p => p.count) }])
const hkTrendOptions = computed(() => ({
    chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    colors: ['#10b981'],
    plotOptions: { bar: { borderRadius: 5, columnWidth: '50%' } },
    stroke: { show: false },
    dataLabels: { enabled: false },
    grid: { borderColor: isDark.value ? '#1f2937' : '#f3f4f6', yaxis: { lines: { show: true } }, padding: { left: 4, right: 4, top: -8 } },
    xaxis: {
        categories: (props.housekeeping?.trend ?? []).map(p => p.label),
        labels: { style: { colors: axisColor, fontSize: '11px' } },
        axisBorder: { show: false }, axisTicks: { show: false },
    },
    yaxis: { labels: { style: { colors: axisColor, fontSize: '11px' }, formatter: (v) => `${Math.round(v)}` } },
    tooltip: { theme: isDark.value ? 'dark' : 'light', y: { formatter: (v) => `${v} unit${v !== 1 ? 's' : ''} cleaned` } },
}))

const hkStateMeta = {
    needs_cleaning: { label: 'Needs cleaning', chip: 'bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400', dot: 'bg-rose-500' },
    cleaning:       { label: 'Cleaning',       chip: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400', dot: 'bg-amber-500' },
}
function hkSince(iso) { return iso ? ago(iso) : null }
function hkArrives(iso) { return iso ? arrivesLabel(iso) : null }
function hkUrgent(u) { return u.arrival && arrivesLabel(u.arrival) === 'arrives today' }

function hkRequest(u) {
    router.post(route('manage.housekeeping.request-cleaning'), { unit_id: u.unit_id, booking_id: u.booking_id }, { preserveScroll: true })
}
function hkCleaned(u) {
    router.post(route('manage.housekeeping.mark-cleaned'), { turnover_id: u.turnover_id }, { preserveScroll: true })
}
</script>

<template>
    <div>
        <!-- At-risk banner -->
        <div v-if="housekeeping.counts.at_risk > 0"
             class="flex items-center gap-2.5 mb-4 px-4 py-2.5 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-800/60">
            <AlertTriangle class="w-4 h-4 text-amber-500 shrink-0" />
            <span class="text-sm text-amber-800 dark:text-amber-300">
                <span class="font-semibold">{{ housekeeping.counts.at_risk }} unit{{ housekeeping.counts.at_risk !== 1 ? 's' : '' }}</span>
                arriving today {{ housekeeping.counts.at_risk !== 1 ? 'aren\'t' : 'isn\'t' }} ready yet - turn {{ housekeeping.counts.at_risk !== 1 ? 'these' : 'this' }} around first.
            </span>
        </div>

        <!-- Stat cards -->
        <h2 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Cleaning today</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
            <Link :href="route('manage.housekeeping.index')"
                  class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5 hover:border-gray-300 dark:hover:border-gray-700 transition-all"
                  :class="housekeeping.counts.needs_cleaning > 0 ? 'ring-1 ring-rose-300/60 dark:ring-rose-700/40' : ''">
                <p class="text-xs font-medium text-rose-600 dark:text-rose-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <DoorClosed class="w-3.5 h-3.5" /> Needs cleaning
                </p>
                <p class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ housekeeping.counts.needs_cleaning }}</p>
            </Link>
            <Link :href="route('manage.housekeeping.index')"
                  class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5 hover:border-gray-300 dark:hover:border-gray-700 transition-all">
                <p class="text-xs font-medium text-amber-600 dark:text-amber-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <Sparkles class="w-3.5 h-3.5" /> In cleaning
                </p>
                <p class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ housekeeping.counts.cleaning }}</p>
            </Link>
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                <p class="text-xs font-medium text-sky-600 dark:text-sky-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <ClipboardCheck class="w-3.5 h-3.5" /> Handed to QA
                </p>
                <p class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ housekeeping.counts.ready_for_qa }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <CheckCircle2 class="w-3.5 h-3.5" /> Cleaned today
                </p>
                <p class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ housekeeping.counts.cleaned_today }}</p>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Turnover pipeline</h3>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">Where units sit right now</p>
                <div v-if="hkPipelineTotal > 0">
                    <VueApexCharts type="donut" height="220" :series="hkPipelineSeries" :options="hkPipelineOptions" />
                    <div class="flex flex-wrap justify-center gap-x-4 gap-y-1.5 mt-2">
                        <span v-for="s in hkPipeline" :key="s.label" class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: s.color }" />
                            {{ s.label }} <span class="tabular-nums text-gray-400">{{ s.value }}</span>
                        </span>
                    </div>
                </div>
                <div v-else class="flex flex-col items-center justify-center h-[220px] text-center">
                    <CheckCircle2 class="w-8 h-8 text-emerald-400 mb-2" />
                    <p class="text-sm text-gray-500 dark:text-gray-400">Every unit is settled.</p>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Cleanings completed</h3>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">Last 7 days</p>
                <VueApexCharts type="bar" height="240" :series="hkTrendSeries" :options="hkTrendOptions" />
            </div>
        </div>

        <!-- Cleaning worklist -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden mb-8">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <Sparkles class="w-4 h-4 text-rose-500" /> Cleaning worklist
                    <span v-if="housekeeping.worklist.length" class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400">{{ housekeeping.worklist.length }}</span>
                </h3>
                <Link :href="route('manage.housekeeping.index')" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">Open board →</Link>
            </div>
            <div v-if="housekeeping.worklist.length" class="divide-y divide-gray-100 dark:divide-gray-800">
                <div v-for="u in housekeeping.worklist" :key="u.unit_id"
                     :class="hkUrgent(u) ? 'border-l-2 border-amber-400 bg-amber-50/30 dark:bg-amber-500/[0.04]' : 'border-l-2 border-transparent'"
                     class="flex items-center gap-3 px-5 py-3">
                    <span class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center" :class="hkStateMeta[u.state].chip">
                        <BedDouble class="w-4 h-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            Unit {{ u.unit_number }}
                            <span class="font-normal text-gray-400 dark:text-gray-500">· {{ u.unit_type }}</span>
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 truncate flex items-center gap-2 mt-0.5 flex-wrap">
                            <span class="inline-flex items-center gap-1" :class="hkStateMeta[u.state].chip.split(' ').filter(c => c.startsWith('text')).join(' ')">
                                <span :class="hkStateMeta[u.state].dot" class="w-1.5 h-1.5 rounded-full" /> {{ hkStateMeta[u.state].label }}
                            </span>
                            <span v-if="hkSince(u.since)" class="inline-flex items-center gap-1"><Timer class="w-3 h-3" /> {{ hkSince(u.since) }}</span>
                            <span v-if="u.arrival" :class="hkUrgent(u) ? 'text-amber-600 dark:text-amber-400 font-medium' : ''" class="inline-flex items-center gap-1"><LogIn class="w-3 h-3" /> {{ hkArrives(u.arrival) }}</span>
                        </p>
                    </div>
                    <button v-if="u.state === 'needs_cleaning'" @click="hkRequest(u)"
                            class="shrink-0 inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:opacity-90 transition-all">
                        <Sparkles class="w-3.5 h-3.5" /> Start clean
                    </button>
                    <button v-else-if="u.state === 'cleaning'" @click="hkCleaned(u)"
                            class="shrink-0 inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:opacity-90 transition-all">
                        <CheckCircle2 class="w-3.5 h-3.5" /> Mark cleaned
                    </button>
                </div>
            </div>
            <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                <CheckCircle2 class="w-8 h-8 text-emerald-400 mb-2" />
                <p class="text-sm text-gray-500 dark:text-gray-400">Nothing to clean right now - all caught up.</p>
            </div>
        </div>
    </div>
</template>
