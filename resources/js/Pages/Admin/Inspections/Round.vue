<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import {
    ArrowLeft, Building2, CheckCircle, AlertTriangle, ClipboardCheck, ArrowRight,
    User, Clock, BedDouble, Wrench, Sofa, Trees, Trash2, DoorClosed, ChevronDown,
} from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    round:       Object,
    units:       { type: Array, default: () => [] },
    sections:    { type: Array, default: () => [] },
    counts:      { type: Object, default: () => ({}) },
    canComplete: Boolean,
})

const isActive = computed(() => props.round.status === 'in_progress')
const unitsOpen = ref(true)

const sectionIcon = { common: Sofa, outdoor: Trees }

// Overall round progress folds units and the two property spaces together.
const spacesDone  = computed(() => props.sections.filter(s => s.status === 'completed').length)
const spacesTotal = computed(() => props.sections.length)
const doneTotal   = computed(() => props.counts.inspected + spacesDone.value)
const workTotal   = computed(() => (props.counts.inspectable ?? 0) + spacesTotal.value)
const pct         = computed(() => workTotal.value > 0 ? Math.round(doneTotal.value / workTotal.value * 100) : 0)

// Progress ring geometry
const R = 52
const CIRC = 2 * Math.PI * R
const dash = computed(() => `${CIRC * pct.value / 100} ${CIRC}`)

const stateMeta = {
    pending:     { label: 'Pending',     cls: 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400' },
    in_progress: { label: 'In progress', cls: 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400' },
    ok:          { label: 'Pass',        cls: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' },
    concern:     { label: 'Fail',        cls: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400' },
    occupied:    { label: 'Occupied',    cls: 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400' },
    offline:     { label: 'Maintenance', cls: 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400' },
}

function inspectUnit(unit) {
    if (!isActive.value) return
    router.post(route('manage.inspections.start'), { round_id: props.round.id, unit_id: unit.unit_id })
}
function onUnitClick(unit) {
    if (props.round.status === 'cancelled') return
    if (['ok', 'concern', 'in_progress'].includes(unit.state) && unit.inspection_id) {
        router.get(route('manage.inspections.show', unit.inspection_id))
    } else if (unit.state === 'pending') {
        inspectUnit(unit)
    }
}
function openSection(section) {
    if (props.round.status !== 'cancelled') router.get(route('manage.inspections.section', section.id))
}
function completeRound() {
    router.post(route('manage.inspections.round.complete', props.round.id))
}

const showDiscard = ref(false)
const discarding  = ref(false)
function cancelRound() {
    discarding.value = true
    router.post(route('manage.inspections.round.cancel', props.round.id), {}, {
        onFinish: () => { discarding.value = false; showDiscard.value = false },
    })
}

const remainingLabel = computed(() => {
    if (props.canComplete) return 'Complete round'
    if (props.counts.pending > 0) return `${props.counts.pending} unit${props.counts.pending !== 1 ? 's' : ''} left`
    return `${spacesTotal.value - spacesDone.value} space${spacesTotal.value - spacesDone.value !== 1 ? 's' : ''} left`
})

function fmtDate(d) {
    return d ? new Date(d).toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }) : '-'
}

const card = 'bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none'
</script>

<template>
    <Head :title="`Round · ${round.building_name}`" />

    <div class="p-4 lg:p-6" :class="isActive ? 'pb-24 lg:pb-6' : ''">

        <!-- ── Fixed topbar ── -->
        <div class="sticky top-0 z-20 -mx-4 lg:-mx-6 -mt-4 lg:-mt-6 px-4 lg:px-6 py-3 mb-5 flex items-center justify-between gap-3 flex-wrap bg-white/90 dark:bg-gray-950/90 backdrop-blur border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-3 min-w-0">
                <Link :href="route('manage.inspections.index')"
                      class="p-1.5 rounded-lg text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-all shrink-0">
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h1 class="text-base font-semibold text-gray-900 dark:text-white truncate">{{ round.building_name }}</h1>
                        <span class="shrink-0 inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-md"
                              :class="round.status === 'completed' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                                    : round.status === 'cancelled' ? 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400'
                                    : 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400'">
                            <span v-if="isActive" class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse" />
                            {{ round.status === 'completed' ? 'Completed' : round.status === 'cancelled' ? 'Discarded' : 'In progress' }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ fmtDate(round.round_date) }}</p>
                </div>
            </div>

            <!-- Desktop actions -->
            <div v-if="isActive" class="hidden lg:flex items-center gap-2 shrink-0">
                <button @click="showDiscard = true"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 shadow-sm hover:text-red-600 hover:border-red-200 dark:hover:border-red-800 transition-all">
                    <Trash2 class="w-3.5 h-3.5" /> Discard
                </button>
                <button @click="completeRound" :disabled="!canComplete"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg shadow-sm hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                    <ClipboardCheck class="w-3.5 h-3.5" /> {{ remainingLabel }}
                </button>
            </div>
        </div>

        <!-- ── Body: summary-first on mobile, two columns on desktop ── -->
        <div class="flex flex-col gap-4 lg:grid lg:grid-cols-[1fr_20rem] lg:gap-5 lg:items-start">

            <!-- ════ Unit inspections (collapsible) ════ -->
            <div class="order-2 lg:order-none">
                <div :class="card" class="overflow-hidden">
                    <button type="button" @click="unitsOpen = !unitsOpen"
                            :class="unitsOpen ? 'border-b border-gray-100 dark:border-gray-800' : ''"
                            class="w-full flex items-center justify-between px-5 py-3.5 text-left">
                        <div class="flex items-center gap-2">
                            <DoorClosed class="w-4 h-4 text-gray-400" />
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Unit inspections</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs tabular-nums text-gray-400">{{ counts.inspected }}/{{ counts.inspectable }}</span>
                            <ChevronDown class="w-4 h-4 text-gray-400 transition-transform" :class="unitsOpen ? '' : '-rotate-90'" />
                        </div>
                    </button>

                    <div v-show="unitsOpen" class="divide-y divide-gray-100 dark:divide-gray-800">
                        <div v-for="unit in units" :key="unit.unit_id"
                             @click="onUnitClick(unit)"
                             :class="['ok', 'concern', 'in_progress', 'pending'].includes(unit.state) && round.status !== 'cancelled' ? 'cursor-pointer hover:bg-gray-50/60 dark:hover:bg-gray-800/40' : ''"
                             class="flex items-center justify-between gap-3 px-5 py-3 transition-colors">
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
                                    <p class="text-xs text-gray-400 truncate">{{ unit.unit_type }}<span v-if="unit.state === 'concern' && unit.concern_count"> · {{ unit.concern_count }} fail{{ unit.concern_count !== 1 ? 's' : '' }}</span></p>
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

                        <div v-if="units.length === 0" class="px-5 py-10 text-center text-sm text-gray-400">No units in this property.</div>
                    </div>
                </div>
            </div>

            <!-- ════ Sidebar: summary + property spaces (first on mobile) ════ -->
            <div class="order-1 lg:order-none space-y-4 lg:sticky lg:top-20">

                <!-- Round summary with progress ring -->
                <div :class="card" class="p-5">
                    <div class="flex items-center gap-4">
                        <div class="relative shrink-0 w-[76px] h-[76px]">
                            <svg viewBox="0 0 120 120" class="w-full h-full -rotate-90">
                                <circle cx="60" cy="60" :r="R" fill="none" stroke="currentColor" stroke-width="10" class="text-gray-200 dark:text-gray-800" />
                                <circle cx="60" cy="60" :r="R" fill="none" stroke-width="10" stroke-linecap="round"
                                        :class="round.status === 'completed' ? 'text-emerald-500' : 'text-gray-900 dark:text-white'"
                                        stroke="currentColor" :stroke-dasharray="dash" class="transition-all duration-500" />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-lg font-semibold tabular-nums text-gray-900 dark:text-white">{{ pct }}%</span>
                            </div>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Round progress</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ doneTotal }} of {{ workTotal }} checks done</p>
                            <p v-if="counts.concerns > 0" class="inline-flex items-center gap-1 text-xs text-amber-600 dark:text-amber-400 mt-1.5">
                                <AlertTriangle class="w-3 h-3" /> {{ counts.concerns }} fail{{ counts.concerns !== 1 ? 's' : '' }} logged
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 text-center">
                        <div>
                            <p class="text-lg font-semibold tabular-nums text-gray-900 dark:text-white">{{ counts.inspected }}</p>
                            <p class="text-[11px] text-gray-400">Inspected</p>
                        </div>
                        <div>
                            <p class="text-lg font-semibold tabular-nums text-gray-900 dark:text-white">{{ counts.pending }}</p>
                            <p class="text-[11px] text-gray-400">Pending</p>
                        </div>
                        <div>
                            <p class="text-lg font-semibold tabular-nums text-gray-900 dark:text-white">{{ counts.occupied }}</p>
                            <p class="text-[11px] text-gray-400">Occupied</p>
                        </div>
                    </div>

                    <div v-if="round.status === 'completed'" class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400">
                        <span v-if="round.completed_by" class="inline-flex items-center gap-1.5"><User class="w-3.5 h-3.5" /> {{ round.completed_by }}</span>
                        <span v-if="round.completed_at" class="inline-flex items-center gap-1.5"><Clock class="w-3.5 h-3.5" /> {{ fmtDate(round.completed_at) }}</span>
                    </div>
                </div>

                <!-- Property spaces -->
                <div :class="card" class="overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-800">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Property spaces</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Inspected once for the whole property</p>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        <div v-for="s in sections" :key="s.id"
                             @click="openSection(s)"
                             :class="round.status !== 'cancelled' ? 'cursor-pointer hover:bg-gray-50/60 dark:hover:bg-gray-800/40' : ''"
                             class="flex items-center justify-between gap-3 px-5 py-3.5 transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center"
                                      :class="s.status === 'completed' && s.result === 'fail' ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400'
                                            : s.status === 'completed' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400'">
                                    <CheckCircle v-if="s.status === 'completed' && s.result !== 'fail'" class="w-4 h-4" />
                                    <AlertTriangle v-else-if="s.status === 'completed'" class="w-4 h-4" />
                                    <component v-else :is="sectionIcon[s.section]" class="w-4 h-4" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ s.title }}</p>
                                    <p class="text-xs text-gray-400 tabular-nums">{{ s.answered }}/{{ s.total }} checked</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-[11px] font-medium px-2 py-1 rounded-lg"
                                      :class="s.status === 'completed' && s.result === 'fail' ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400'
                                            : s.status === 'completed' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                                            : s.status === 'in_progress' ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400'
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400'">
                                    {{ s.status === 'completed' ? (s.result === 'fail' ? 'Fail' : 'Pass') : s.status === 'in_progress' ? 'In progress' : 'Pending' }}
                                </span>
                                <ArrowRight v-if="isActive && s.status !== 'completed'" class="w-3.5 h-3.5 text-gray-400" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Mobile sticky actions ── -->
        <div v-if="isActive"
             class="lg:hidden fixed bottom-0 inset-x-0 z-30 px-4 py-3 bg-white/90 dark:bg-gray-950/90 backdrop-blur border-t border-gray-200 dark:border-gray-800 flex items-center gap-2">
            <button @click="showDiscard = true"
                    class="px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-all">
                Discard
            </button>
            <button @click="completeRound" :disabled="!canComplete"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-sm font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                <ClipboardCheck class="w-4 h-4" /> {{ remainingLabel }}
            </button>
        </div>

        <!-- Discard confirmation -->
        <ConfirmationModal
            :show="showDiscard"
            :processing="discarding"
            title="Discard this round?"
            message="Nothing will be reported and it won't count. Any units already inspected in this round will be discarded too."
            confirm-text="Discard round"
            cancel-text="Keep round"
            variant="danger"
            @confirm="cancelRound"
            @close="showDiscard = false" />
    </div>
</template>
