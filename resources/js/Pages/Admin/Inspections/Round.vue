<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import {
    ArrowLeft, Building2, CheckCircle, AlertTriangle, ClipboardCheck, ArrowRight,
    User, Clock, BedDouble, Wrench, Sofa, Trees, Trash2, DoorClosed, ChevronDown,
    Sparkles, Search, LogIn,
} from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    round:       Object,
    units:       { type: Array, default: () => [] },
    sections:    { type: Array, default: () => [] },
    counts:      { type: Object, default: () => ({}) },
    concerns:    { type: Array, default: () => [] },
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
    pending:        { label: 'To inspect',     cls: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300', dot: 'bg-gray-500', icon: ClipboardCheck },
    needs_cleaning: { label: 'Needs cleaning', cls: 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400', dot: 'bg-gray-400', icon: DoorClosed },
    cleaning:       { label: 'Cleaning',       cls: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400', dot: 'bg-amber-500', icon: Sparkles },
    ready_for_qa:   { label: 'Ready for QA',   cls: 'bg-sky-50 dark:bg-sky-500/10 text-sky-700 dark:text-sky-400', dot: 'bg-sky-500', icon: ClipboardCheck },
    qa_in_progress: { label: 'QA in progress', cls: 'bg-orange-50 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400', dot: 'bg-orange-500', icon: Clock },
    ok:             { label: 'Passed',         cls: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400', dot: 'bg-emerald-500', icon: CheckCircle },
    concern:        { label: 'Concerns',       cls: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400', dot: 'bg-amber-500', icon: AlertTriangle },
    ready:          { label: 'Guest ready',    cls: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400', dot: 'bg-emerald-500', icon: CheckCircle },
    occupied:       { label: 'Occupied',       cls: 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400', dot: 'bg-indigo-500', icon: BedDouble },
    blocked:        { label: 'Blocked',  cls: 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400', dot: 'bg-red-500', icon: Wrench },
    offline:        { label: 'Maintenance',    cls: 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400', dot: 'bg-gray-400', icon: Wrench },
}

// ── Quick filter + search over the unit list ──
const activeTab = ref('all')
const search    = ref('')
const tabOrder  = ['pending', 'ready_for_qa', 'qa_in_progress', 'cleaning', 'needs_cleaning', 'concern', 'ok', 'ready', 'occupied', 'blocked', 'offline']
const stateCounts = computed(() => {
    const c = {}
    props.units.forEach(u => { c[u.state] = (c[u.state] || 0) + 1 })
    return c
})
const tabs = computed(() => [
    { key: 'all', label: 'All', count: props.units.length },
    ...tabOrder.filter(s => stateCounts.value[s]).map(s => ({ key: s, label: stateMeta[s].label, count: stateCounts.value[s] })),
])
const filteredUnits = computed(() => props.units.filter(u => {
    if (activeTab.value !== 'all' && u.state !== activeTab.value) return false
    if (search.value && !`${u.unit_number} ${u.unit_type}`.toLowerCase().includes(search.value.toLowerCase())) return false
    return true
}))
const isActionable = (u) => ['pending', 'ready_for_qa', 'qa_in_progress'].includes(u.state)
const fmtShort = (iso) => iso ? new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) : ''

function inspectUnit(unit) {
    if (!isActive.value) return
    router.post(route('manage.inspections.start'), { round_id: props.round.id, unit_id: unit.unit_id })
}
function onUnitClick(unit) {
    if (props.round.status === 'cancelled') return
    if (['ok', 'concern', 'qa_in_progress'].includes(unit.state) && unit.inspection_id) {
        router.get(route('manage.inspections.show', unit.inspection_id))
    } else if (['pending', 'ready_for_qa'].includes(unit.state)) {
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
            <div class="order-2 lg:order-none space-y-4">
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

                    <div v-show="unitsOpen">
                        <!-- Search + quick-filter tabs -->
                        <div class="px-4 pt-3 pb-2 border-b border-gray-100 dark:border-gray-800 space-y-2.5">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                                <input v-model="search" type="text" placeholder="Search unit…"
                                       class="w-full h-9 pl-9 pr-3 rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                            </div>
                            <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5">
                                <button v-for="t in tabs" :key="t.key" @click="activeTab = t.key"
                                        :class="activeTab === t.key ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 border-transparent' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
                                        class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-[11px] font-medium transition-all">
                                    {{ t.label }}
                                    <span :class="activeTab === t.key ? 'bg-white/20 dark:bg-gray-900/10' : 'bg-gray-100 dark:bg-gray-800'" class="px-1 rounded tabular-nums">{{ t.count }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                            <div v-for="unit in filteredUnits" :key="unit.unit_id"
                                 @click="onUnitClick(unit)"
                                 :class="(isActionable(unit) || ['ok', 'concern'].includes(unit.state)) && round.status !== 'cancelled' ? 'cursor-pointer hover:bg-gray-50/60 dark:hover:bg-gray-800/40' : ''"
                                 class="flex items-center justify-between gap-3 px-5 py-3 transition-colors">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center" :class="stateMeta[unit.state].cls">
                                        <component :is="stateMeta[unit.state].icon" class="w-4 h-4" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">Unit {{ unit.unit_number }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ unit.unit_type }}<span v-if="unit.state === 'concern' && unit.concern_count"> · {{ unit.concern_count }} fail{{ unit.concern_count !== 1 ? 's' : '' }}</span></p>
                                        <div v-if="unit.checkout || unit.arrival" class="flex items-center gap-3 mt-0.5 text-[11px] text-gray-400">
                                            <span v-if="unit.checkout" class="inline-flex items-center gap-1"><DoorClosed class="w-3 h-3" /> Out {{ fmtShort(unit.checkout.date) }}<template v-if="unit.checkout.time"> · {{ unit.checkout.time }}</template></span>
                                            <span v-if="unit.arrival" class="inline-flex items-center gap-1"><LogIn class="w-3 h-3" /> In {{ fmtShort(unit.arrival.date) }}<template v-if="unit.arrival.time"> · {{ unit.arrival.time }}</template></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-[11px] font-medium px-2 py-1 rounded-lg" :class="stateMeta[unit.state].cls">{{ stateMeta[unit.state].label }}</span>
                                    <template v-if="isActive">
                                        <span v-if="['pending', 'ready_for_qa'].includes(unit.state)" class="inline-flex items-center gap-1 text-xs font-semibold text-gray-900 dark:text-white">
                                            Inspect <ArrowRight class="w-3.5 h-3.5" />
                                        </span>
                                        <span v-else-if="unit.state === 'qa_in_progress'" class="inline-flex items-center gap-1 text-xs font-semibold text-orange-600 dark:text-orange-400">
                                            Resume <ArrowRight class="w-3.5 h-3.5" />
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <div v-if="filteredUnits.length === 0" class="px-5 py-10 text-center text-sm text-gray-400">
                                {{ units.length ? 'No units match this view.' : 'No units in this property.' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════ Concerns report ════ -->
                <div v-if="concerns.length" :class="card" class="overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <AlertTriangle class="w-4 h-4 text-amber-500" />
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Concerns</h2>
                        </div>
                        <span class="text-xs tabular-nums text-gray-400">{{ concerns.length }} failed item{{ concerns.length !== 1 ? 's' : '' }}</span>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        <div v-for="(c, i) in concerns" :key="i" class="px-5 py-3.5">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-sm font-medium text-gray-900 dark:text-white leading-snug">{{ c.label }}</p>
                                <span class="shrink-0 text-[11px] font-medium px-2 py-0.5 rounded-md bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 whitespace-nowrap">{{ c.source }}</span>
                            </div>
                            <p v-if="c.note" class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ c.note }}</p>
                            <div v-if="c.photos.length" class="flex flex-wrap gap-2 mt-2.5">
                                <a v-for="(p, pi) in c.photos" :key="pi" :href="p" target="_blank" rel="noopener"
                                   class="w-14 h-14 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800 hover:ring-2 hover:ring-gray-900 dark:hover:ring-white transition-all">
                                    <img :src="p" class="w-full h-full object-cover" />
                                </a>
                            </div>
                        </div>
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
                                    {{ s.status === 'completed' ? (s.result === 'fail' ? 'Concerns' : 'Passed') : s.status === 'in_progress' ? 'In progress' : 'Pending' }}
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
