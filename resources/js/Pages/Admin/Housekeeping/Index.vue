<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import {
    Sparkles, Search, CheckCircle, Clock, LogIn, BedDouble, Wrench, DoorClosed,
    ClipboardCheck, X, ChevronRight, AlertTriangle, Timer, MoreVertical,
} from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    units:     { type: Array, default: () => [] },
    buildings: { type: Array, default: () => [] },
    counts:    { type: Object, default: () => ({}) },
    can:       { type: Object, default: () => ({}) },
})

const stateMeta = {
    needs_cleaning: { label: 'Needs cleaning', chip: 'bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400',       dot: 'bg-rose-500',    prio: 1 },
    cleaning:       { label: 'Cleaning',       chip: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400',   dot: 'bg-amber-500',   prio: 2 },
    ready_for_qa:   { label: 'Ready for QA',   chip: 'bg-sky-50 dark:bg-sky-500/10 text-sky-700 dark:text-sky-400',           dot: 'bg-sky-500',     prio: 3 },
    qa_in_progress: { label: 'QA in progress', chip: 'bg-orange-50 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400', dot: 'bg-orange-500', prio: 4 },
    pending:        { label: 'To inspect',     chip: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300',         dot: 'bg-gray-500',    prio: 5 },
    blocked:        { label: 'Blocked',        chip: 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400',           dot: 'bg-red-500',     prio: 6 },
    occupied:       { label: 'Occupied',       chip: 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400', dot: 'bg-indigo-500', prio: 7 },
    ready:          { label: 'Guest ready',    chip: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400', dot: 'bg-emerald-500', prio: 8 },
    offline:        { label: 'Maintenance',    chip: 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400',         dot: 'bg-gray-400',    prio: 9 },
}

// The turnover pipeline — the flow reception drives.
const pipeline = [
    { key: 'needs_cleaning', label: 'Needs cleaning', icon: DoorClosed,  accent: 'text-rose-600 dark:text-rose-400',    ring: 'ring-rose-400 bg-rose-50/50 dark:bg-rose-500/5' },
    { key: 'cleaning',       label: 'Cleaning',       icon: Sparkles,    accent: 'text-amber-600 dark:text-amber-400',  ring: 'ring-amber-400 bg-amber-50/50 dark:bg-amber-500/5' },
    { key: 'ready_for_qa',   label: 'Ready for QA',   icon: ClipboardCheck, accent: 'text-sky-600 dark:text-sky-400',   ring: 'ring-sky-400 bg-sky-50/50 dark:bg-sky-500/5' },
    { key: 'ready',          label: 'Guest ready',    icon: CheckCircle, accent: 'text-emerald-600 dark:text-emerald-400', ring: 'ring-emerald-400 bg-emerald-50/50 dark:bg-emerald-500/5' },
]
const secondary = ['pending', 'qa_in_progress', 'occupied', 'blocked', 'offline']

const activeTab = ref('all')
const search    = ref('')
const building  = ref('')

function toggle(key) { activeTab.value = activeTab.value === key ? 'all' : key }

// ── Time intelligence ──
const NOT_READY = ['needs_cleaning', 'cleaning', 'ready_for_qa', 'qa_in_progress', 'pending']
function startOfDay(d) { const x = new Date(d); x.setHours(0, 0, 0, 0); return x.getTime() }
const todayKey = startOfDay(new Date())
function dayDiff(iso) { return Math.round((startOfDay(iso) - todayKey) / 86400000) }
function ago(iso) {
    if (!iso) return null
    const m = Math.floor((Date.now() - new Date(iso)) / 60000)
    if (m < 1) return 'just now'
    if (m < 60) return `${m}m`
    if (m < 1440) return `${Math.floor(m / 60)}h`
    return `${Math.floor(m / 1440)}d`
}
function dayWord(iso) {
    const d = dayDiff(iso)
    return d === 0 ? 'today' : d === 1 ? 'tomorrow' : d === -1 ? 'yesterday' : d < 0 ? `${-d}d ago` : `in ${d}d`
}
const isUrgent = (u) => u.arrival && dayDiff(u.arrival.date) <= 0 && NOT_READY.includes(u.state)
function agingLabel(u) {
    if (!u.since) return null
    return u.state === 'needs_cleaning' ? `Vacant since ${dayWord(u.since)}` : `${ago(u.since)} in this stage`
}
function arrivalLabel(u) {
    if (!u.arrival) return null
    const d = dayDiff(u.arrival.date)
    const when = d === 0 ? 'today' : d === 1 ? 'tomorrow' : new Date(u.arrival.date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })
    return u.arrival.time ? `${when} · ${u.arrival.time}` : when
}

const atRisk = computed(() => props.units.filter(isUrgent))

// ── Filter + sort (urgent → attention order → unit no.) ──
const rows = computed(() => props.units
    .filter(u => {
        if (activeTab.value === 'at_risk') { if (!isUrgent(u)) return false }
        else if (activeTab.value !== 'all' && u.state !== activeTab.value) return false
        if (building.value && u.building_name !== building.value) return false
        if (search.value && !`${u.unit_number} ${u.unit_type}`.toLowerCase().includes(search.value.toLowerCase())) return false
        return true
    })
    .sort((a, b) => (isUrgent(b) - isUrgent(a))
        || (stateMeta[a.state].prio - stateMeta[b.state].prio)
        || String(a.unit_number).localeCompare(String(b.unit_number), undefined, { numeric: true })))

const canCancel = (u) => ['cleaning', 'ready_for_qa'].includes(u.state)

// ── Row kebab menu ──
// The menu is teleported to <body> with fixed positioning so it's never clipped
// by the worklist card's overflow, and flips upward near the bottom of the screen.
const openMenu  = ref(null)   // unit_id of the open menu
const menuUnit  = ref(null)   // the unit the open menu belongs to
const menuStyle = ref({})
function toggleMenu(u, e) {
    if (openMenu.value === u.unit_id) { openMenu.value = null; menuUnit.value = null; return }
    const r = e.currentTarget.getBoundingClientRect()
    const estH = menuItems(u).length * 36 + 10
    const openUp = r.bottom + estH + 8 > window.innerHeight
    menuStyle.value = {
        position: 'fixed',
        left: `${Math.max(8, r.right - 208)}px`,
        ...(openUp ? { bottom: `${window.innerHeight - r.top + 4}px` } : { top: `${r.bottom + 4}px` }),
    }
    menuUnit.value = u
    openMenu.value = u.unit_id
}
function go(url) { openMenu.value = null; router.visit(url) }

// Contextual actions per unit, gated by both turnover state and the user's
// permissions (reception can't block, QC can't open bookings, etc.).
function menuItems(u) {
    const items = []
    if (u.state === 'needs_cleaning')
        items.push({ key: 'request', label: 'Request cleaning', icon: Sparkles, run: () => { openMenu.value = null; askRequest(u) } })
    if (u.state === 'cleaning')
        items.push({ key: 'cleaned', label: 'Mark as cleaned', icon: CheckCircle, run: () => { openMenu.value = null; askCleaned(u) } })
    if (props.can.view_inspections && ['ready_for_qa', 'qa_in_progress'].includes(u.state))
        items.push({ key: 'inspect', label: 'Open inspection', icon: ClipboardCheck, run: () => go(route('manage.inspections.index')) })
    if (props.can.view_bookings && u.booking_id)
        items.push({ key: 'booking', label: 'View departing booking', icon: BedDouble, run: () => go(route('manage.bookings.show', u.booking_id)) })
    if (props.can.block && !['blocked', 'occupied'].includes(u.state))
        items.push({ key: 'block', label: 'Block for maintenance', icon: Wrench, run: () => go(route('manage.blocked-dates.create')) })
    if (canCancel(u))
        items.push({ key: 'cancel', label: 'Cancel cleaning', icon: X, danger: true, run: () => { openMenu.value = null; askCancel(u) } })
    return items
}

// ── Action modal ──
const modal = ref({ show: false, kind: null, unit: null, processing: false })
function askRequest(u) { modal.value = { show: true, kind: 'request', unit: u, processing: false } }
function askCleaned(u) { modal.value = { show: true, kind: 'cleaned', unit: u, processing: false } }
function askCancel(u)  { modal.value = { show: true, kind: 'cancel',  unit: u, processing: false } }
function closeModal()  { modal.value = { ...modal.value, show: false } }

const modalCopy = computed(() => {
    const u = modal.value.unit
    if (!u) return {}
    return {
        request: { title: 'Request cleaning?', message: `Unit ${u.unit_number} will be marked as cleaning in progress.`, confirm: 'Request cleaning' },
        cleaned: { title: 'Mark as cleaned?',  message: `Unit ${u.unit_number} will move to Ready for QA and quality control will be notified.`, confirm: 'Mark cleaned' },
        cancel:  { title: 'Cancel cleaning?',  message: `The cleaning turnover for unit ${u.unit_number} will be discarded.`, confirm: 'Cancel cleaning' },
    }[modal.value.kind] ?? {}
})

function confirmModal() {
    const { kind, unit } = modal.value
    modal.value.processing = true
    const opts = { preserveScroll: true, onFinish: () => { modal.value.processing = false; closeModal() } }
    if (kind === 'request') router.post(route('manage.housekeeping.request-cleaning'), { unit_id: unit.unit_id, booking_id: unit.booking_id }, opts)
    else if (kind === 'cleaned') router.post(route('manage.housekeeping.mark-cleaned'), { turnover_id: unit.turnover_id }, opts)
    else router.post(route('manage.housekeeping.cancel'), { turnover_id: unit.turnover_id }, opts)
}
</script>

<template>
    <Head title="Housekeeping" />

    <div class="p-4 lg:p-6">

        <!-- Header -->
        <div class="flex items-center justify-between gap-3 flex-wrap mb-5">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-gray-900 dark:bg-white flex items-center justify-center">
                    <Sparkles class="w-4 h-4 text-white dark:text-gray-900" />
                </div>
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Housekeeping</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Prepare vacated units for the next guest · {{ units.length }} units</p>
                </div>
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <div class="relative flex-1 sm:flex-none">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                    <input v-model="search" type="text" placeholder="Search unit…"
                           class="h-9 w-full sm:w-48 pl-9 pr-3 rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                </div>
                <select v-if="buildings.length > 1" v-model="building"
                        class="h-9 pl-3 pr-8 rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 text-sm text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                    <option value="">All buildings</option>
                    <option v-for="b in buildings" :key="b.id" :value="b.name">{{ b.name }}</option>
                </select>
            </div>
        </div>

        <!-- Pipeline: 2×2 grid on mobile, a connected row from lg up -->
        <div class="grid grid-cols-2 gap-2 mb-3 lg:flex lg:items-stretch">
            <template v-for="(stage, i) in pipeline" :key="stage.key">
                <button @click="toggle(stage.key)"
                        :class="activeTab === stage.key ? `ring-2 ${stage.ring} border-transparent` : 'border-gray-200/80 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
                        class="text-left bg-white dark:bg-gray-900 border rounded-xl px-3.5 py-3 transition-all lg:flex-1 lg:min-w-[120px]">
                    <div class="flex items-center justify-between">
                        <component :is="stage.icon" class="w-4 h-4" :class="stage.accent" />
                        <span class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ counts[stage.key] ?? 0 }}</span>
                    </div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-1.5">{{ stage.label }}</p>
                </button>
                <div v-if="i < pipeline.length - 1" class="hidden lg:flex items-center text-gray-300 dark:text-gray-700 shrink-0">
                    <ChevronRight class="w-4 h-4" />
                </div>
            </template>
        </div>

        <!-- At-risk banner -->
        <button v-if="atRisk.length && activeTab !== 'at_risk'" @click="activeTab = 'at_risk'"
                class="w-full flex items-center gap-2.5 mb-3 px-4 py-2.5 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-800/60 text-left hover:bg-amber-100/60 dark:hover:bg-amber-500/15 transition-colors">
            <AlertTriangle class="w-4 h-4 text-amber-500 shrink-0" />
            <span class="text-sm text-amber-800 dark:text-amber-300 flex-1">
                <span class="font-semibold">{{ atRisk.length }} unit{{ atRisk.length !== 1 ? 's' : '' }}</span>
                arriving today {{ atRisk.length !== 1 ? 'aren\'t' : 'isn\'t' }} ready yet — turn these around first.
            </span>
            <span class="text-xs font-semibold text-amber-700 dark:text-amber-400 shrink-0">Show →</span>
        </button>

        <!-- Secondary filters -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 mb-3">
            <button @click="activeTab = 'all'"
                    :class="activeTab === 'all' ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'"
                    class="shrink-0 px-2.5 py-1 rounded-full text-xs font-medium transition-colors">All {{ units.length }}</button>
            <button v-for="s in secondary" :key="s" v-show="(counts[s] ?? 0) > 0" @click="toggle(s)"
                    :class="activeTab === s ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'"
                    class="shrink-0 inline-flex items-center gap-1.5 pl-2 pr-2.5 py-1 rounded-full text-xs font-medium transition-colors">
                <span :class="stateMeta[s].dot" class="w-1.5 h-1.5 rounded-full" />
                {{ stateMeta[s].label }} <span class="tabular-nums opacity-70">{{ counts[s] }}</span>
            </button>
        </div>

        <!-- Worklist -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden">
            <div v-if="!rows.length" class="py-16 text-center">
                <Sparkles class="w-9 h-9 text-gray-300 dark:text-gray-700 mx-auto mb-3" />
                <p class="text-gray-500 dark:text-gray-400">Nothing here — every unit in this view is settled.</p>
            </div>

            <div v-else class="divide-y divide-gray-100 dark:divide-gray-800">
                <div v-for="u in rows" :key="u.unit_id"
                     :class="isUrgent(u) ? 'border-l-2 border-amber-400 bg-amber-50/30 dark:bg-amber-500/[0.04]' : 'border-l-2 border-transparent'"
                     class="flex items-center gap-3 px-4 sm:px-5 py-3 hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">

                    <!-- State-coloured unit avatar -->
                    <span class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center" :class="stateMeta[u.state].chip">
                        <BedDouble class="w-4 h-4" />
                    </span>

                    <!-- Unit + aging -->
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Unit {{ u.unit_number }}</p>
                            <span :class="stateMeta[u.state].chip" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-medium">
                                <span :class="stateMeta[u.state].dot" class="w-1.5 h-1.5 rounded-full" />
                                {{ stateMeta[u.state].label }}
                            </span>
                            <span v-if="agingLabel(u)" class="inline-flex items-center gap-1 text-[11px] text-gray-400">
                                <Timer class="w-3 h-3" /> {{ agingLabel(u) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 truncate mt-0.5">
                            {{ u.unit_type }}<span v-if="buildings.length > 1"> · {{ u.building_name }}</span>
                        </p>
                        <!-- Arrival (mobile: the right-hand column is hidden below sm) -->
                        <p v-if="u.arrival" class="sm:hidden mt-1 text-[11px] inline-flex items-center gap-1"
                           :class="isUrgent(u) ? 'text-amber-700 dark:text-amber-400 font-medium' : 'text-gray-400'">
                            <LogIn class="w-3 h-3 shrink-0" /> Arrives {{ arrivalLabel(u) }}<span v-if="u.guest_next" class="truncate"> · {{ u.guest_next }}</span>
                        </p>
                    </div>

                    <!-- Arrival (sm+) -->
                    <div v-if="u.arrival" class="hidden sm:block text-right shrink-0">
                        <p :class="isUrgent(u) ? 'text-amber-700 dark:text-amber-400 font-medium' : 'text-gray-500 dark:text-gray-400'" class="text-xs inline-flex items-center gap-1">
                            <LogIn class="w-3 h-3" /> {{ arrivalLabel(u) }}
                        </p>
                        <p v-if="u.guest_next" class="text-[11px] text-gray-400 truncate max-w-[10rem]">{{ u.guest_next }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="shrink-0 inline-flex items-center gap-1.5">
                        <!-- Kebab: opens the teleported contextual menu below -->
                        <button v-if="menuItems(u).length" @click.stop="toggleMenu(u, $event)"
                                :class="openMenu === u.unit_id ? 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200' : 'text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800'"
                                class="p-1.5 rounded-lg transition-all" :aria-expanded="openMenu === u.unit_id" aria-haspopup="true" title="Actions">
                            <MoreVertical class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row action menu (teleported so it's never clipped by the list card) -->
        <Teleport to="body">
            <template v-if="openMenu !== null && menuUnit">
                <div class="fixed inset-0 z-40" @click="openMenu = null" />
                <div :style="menuStyle"
                     class="z-50 w-52 py-1 bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-lg shadow-gray-300/40 dark:shadow-black/40 overflow-hidden">
                    <button v-for="item in menuItems(menuUnit)" :key="item.key" @click.stop="item.run"
                            :class="item.danger ? 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800'"
                            class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-left transition-colors">
                        <component :is="item.icon" class="w-3.5 h-3.5 shrink-0" :class="item.danger ? '' : 'text-gray-400'" />
                        {{ item.label }}
                    </button>
                </div>
            </template>
        </Teleport>

        <!-- Action confirmation -->
        <ConfirmationModal
            :show="modal.show"
            :processing="modal.processing"
            :title="modalCopy.title"
            :message="modalCopy.message"
            :confirm-text="modalCopy.confirm"
            cancel-text="Cancel"
            @confirm="confirmModal"
            @close="closeModal" />
    </div>
</template>
