<script setup>
import { computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, Building2, CheckCircle, AlertTriangle, ClipboardCheck, ArrowRight, User, Clock, BedDouble, Wrench } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    round:       Object,
    units:       { type: Array, default: () => [] },
    counts:      { type: Object, default: () => ({}) },
    canComplete: Boolean,
})

const isActive = computed(() => props.round.status === 'in_progress')
const progress = computed(() =>
    props.counts.inspectable > 0 ? Math.round((props.counts.inspected / props.counts.inspectable) * 100) : 0
)

const stateMeta = {
    pending:     { label: 'Pending',     cls: 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400' },
    in_progress: { label: 'In progress', cls: 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400' },
    ok:          { label: 'OK',          cls: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' },
    concern:     { label: 'Concern',     cls: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400' },
    occupied:    { label: 'Occupied',    cls: 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400' },
    offline:     { label: 'Maintenance', cls: 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400' },
}

function inspectUnit(unit) {
    if (!isActive.value) return
    router.post(route('manage.inspections.start'), { round_id: props.round.id, unit_id: unit.unit_id })
}
function viewInspection(unit) {
    if (unit.inspection_id) router.get(route('manage.inspections.show', unit.inspection_id))
}
function onUnitClick(unit) {
    if (['ok', 'concern', 'in_progress'].includes(unit.state)) viewInspection(unit)
    else if (unit.state === 'pending') inspectUnit(unit)
}
function completeRound() {
    router.post(route('manage.inspections.round.complete', props.round.id))
}
function cancelRound() {
    if (confirm('Discard this round? Nothing will be reported and it won\'t count.')) {
        router.post(route('manage.inspections.round.cancel', props.round.id))
    }
}

function fmtDate(d) {
    return d ? new Date(d).toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }) : '—'
}
</script>

<template>
    <Head :title="`Round · ${round.building_name}`" />

    <div class="p-4 lg:p-6 max-w-3xl mx-auto">

        <Link :href="route('manage.inspections.index')" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors mb-4">
            <ArrowLeft class="w-4 h-4" /> Inspections
        </Link>

        <!-- Round header -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5 mb-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                        <Building2 class="w-4 h-4 text-gray-400 shrink-0" /> {{ round.building_name }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ fmtDate(round.round_date) }}</p>
                </div>
                <span class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium"
                      :class="round.status === 'completed' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                            : round.status === 'cancelled' ? 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400'
                            : 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400'">
                    <span v-if="isActive" class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse" />
                    {{ round.status === 'completed' ? 'Completed' : round.status === 'cancelled' ? 'Discarded' : 'In progress' }}
                </span>
            </div>

            <!-- Progress -->
            <div class="mt-4">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                    <span>{{ counts.inspected }} of {{ counts.inspectable }} inspected</span>
                    <span v-if="counts.concerns > 0" class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400">
                        <AlertTriangle class="w-3 h-3" /> {{ counts.concerns }} concern{{ counts.concerns !== 1 ? 's' : '' }}
                    </span>
                </div>
                <div class="h-1.5 rounded-full bg-gray-200 dark:bg-gray-800 overflow-hidden">
                    <div class="h-full rounded-full transition-all" :class="round.status === 'completed' ? 'bg-emerald-500' : 'bg-blue-500'"
                         :style="{ width: progress + '%' }" />
                </div>
            </div>

            <div v-if="round.status === 'completed'" class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400">
                <span v-if="round.completed_by" class="inline-flex items-center gap-1.5"><User class="w-3.5 h-3.5" /> {{ round.completed_by }}</span>
                <span v-if="round.completed_at" class="inline-flex items-center gap-1.5"><Clock class="w-3.5 h-3.5" /> {{ fmtDate(round.completed_at) }}</span>
            </div>
        </div>

        <!-- Unit checklist -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none divide-y divide-gray-100 dark:divide-gray-800 mb-4">
            <div v-for="unit in units" :key="unit.unit_id"
                 @click="round.status !== 'cancelled' && onUnitClick(unit)"
                 :class="['ok', 'concern', 'in_progress', 'pending'].includes(unit.state) && round.status !== 'cancelled' ? 'cursor-pointer hover:bg-gray-50/60 dark:hover:bg-gray-800/40' : ''"
                 class="flex items-center justify-between gap-3 px-4 py-3 transition-colors">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center text-xs font-semibold"
                          :class="unit.state === 'concern' ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400'
                                : unit.state === 'ok' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400'">
                        <CheckCircle v-if="unit.state === 'ok'" class="w-4 h-4" />
                        <AlertTriangle v-else-if="unit.state === 'concern'" class="w-4 h-4" />
                        <BedDouble v-else-if="unit.state === 'occupied'" class="w-4 h-4" />
                        <Wrench v-else-if="unit.state === 'offline'" class="w-4 h-4" />
                        <template v-else>{{ unit.unit_number }}</template>
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">Unit {{ unit.unit_number }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ unit.unit_type }}<span v-if="unit.state === 'concern' && unit.findings_count"> · {{ unit.findings_count }} issue{{ unit.findings_count !== 1 ? 's' : '' }}</span></p>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-[11px] font-medium px-2 py-1 rounded-lg" :class="stateMeta[unit.state].cls">{{ stateMeta[unit.state].label }}</span>
                    <template v-if="isActive">
                        <span v-if="unit.state === 'pending'" class="inline-flex items-center gap-1 text-xs font-semibold text-gray-900 dark:text-white">
                            Inspect <ArrowRight class="w-3.5 h-3.5" />
                        </span>
                        <span v-else-if="unit.state === 'in_progress'" class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600 dark:text-amber-400">
                            Resume <ArrowRight class="w-3.5 h-3.5" />
                        </span>
                    </template>
                </div>
            </div>
        </div>

        <!-- Sticky actions (active rounds only) -->
        <div v-if="isActive"
             class="sticky bottom-0 z-30 -mx-4 lg:-mx-6 px-4 lg:px-6 py-3 bg-white/90 dark:bg-gray-950/90 backdrop-blur border-t border-gray-200 dark:border-gray-800 flex items-center gap-2">
            <button @click="cancelRound"
                    class="px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-all">
                Discard
            </button>
            <button @click="completeRound" :disabled="!canComplete"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-sm font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                <ClipboardCheck class="w-4 h-4" />
                {{ canComplete ? 'Complete round' : `${counts.pending} unit${counts.pending !== 1 ? 's' : ''} left` }}
            </button>
        </div>
    </div>
</template>
