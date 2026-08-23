<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import { Sparkles, Search, Building2, CheckCircle, Clock, LogIn, BedDouble, Wrench, DoorClosed, ClipboardCheck } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    units:     { type: Array, default: () => [] },
    buildings: { type: Array, default: () => [] },
    counts:    { type: Object, default: () => ({}) },
})

// Readiness state → label, colour, icon, whether reception can act.
const stateMeta = {
    needs_cleaning: { label: 'Needs cleaning', cls: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400', dot: 'bg-gray-400', icon: DoorClosed },
    cleaning:       { label: 'Cleaning',       cls: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400', dot: 'bg-amber-500', icon: Sparkles },
    ready_for_qa:   { label: 'Ready for QA',   cls: 'bg-sky-50 dark:bg-sky-500/10 text-sky-700 dark:text-sky-400', dot: 'bg-sky-500', icon: CheckCircle },
    qa_in_progress: { label: 'QA in progress', cls: 'bg-orange-50 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400', dot: 'bg-orange-500', icon: Clock },
    pending:        { label: 'To inspect',     cls: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300', dot: 'bg-gray-500', icon: ClipboardCheck },
    ready:          { label: 'Guest ready',    cls: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400', dot: 'bg-emerald-500', icon: CheckCircle },
    occupied:       { label: 'Occupied',       cls: 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400', dot: 'bg-indigo-500', icon: BedDouble },
    blocked:        { label: 'Blocked',        cls: 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400', dot: 'bg-red-500', icon: Wrench },
    offline:        { label: 'Maintenance',    cls: 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400', dot: 'bg-gray-400', icon: Wrench },
}
const tabOrder = ['needs_cleaning', 'cleaning', 'ready_for_qa', 'qa_in_progress', 'pending', 'ready', 'occupied', 'blocked', 'offline']

const activeTab = ref('all')
const search    = ref('')
const building  = ref('')

const tabs = computed(() => [
    { key: 'all', label: 'All', count: props.units.length },
    ...tabOrder.filter(s => (props.counts[s] ?? 0) > 0)
        .map(s => ({ key: s, label: stateMeta[s].label, count: props.counts[s] })),
])

const filtered = computed(() => props.units.filter(u => {
    if (activeTab.value !== 'all' && u.state !== activeTab.value) return false
    if (building.value && u.building_name !== building.value) return false
    if (search.value && !`${u.unit_number} ${u.unit_type}`.toLowerCase().includes(search.value.toLowerCase())) return false
    return true
}))

// ── Action modal (request cleaning / mark cleaned) ──
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
        cancel:  { title: 'Cancel cleaning?',  message: `The cleaning turnover for unit ${u.unit_number} will be discarded. You can request cleaning again anytime.`, confirm: 'Cancel cleaning' },
    }[modal.value.kind] ?? {}
})

function confirmModal() {
    const { kind, unit } = modal.value
    modal.value.processing = true
    const opts = { preserveScroll: true, onFinish: () => { modal.value.processing = false; closeModal() } }
    if (kind === 'request') {
        router.post(route('manage.housekeeping.request-cleaning'), { unit_id: unit.unit_id, booking_id: unit.booking_id }, opts)
    } else if (kind === 'cleaned') {
        router.post(route('manage.housekeeping.mark-cleaned'), { turnover_id: unit.turnover_id }, opts)
    } else {
        router.post(route('manage.housekeeping.cancel'), { turnover_id: unit.turnover_id }, opts)
    }
}

function fmtDate(iso) {
    return iso ? new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) : ''
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
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Request and track cleaning across your units</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                    <input v-model="search" type="text" placeholder="Search unit…"
                           class="h-9 w-40 pl-9 pr-3 rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                </div>
                <select v-if="buildings.length > 1" v-model="building"
                        class="h-9 pl-3 pr-8 rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 text-sm text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                    <option value="">All buildings</option>
                    <option v-for="b in buildings" :key="b.id" :value="b.name">{{ b.name }}</option>
                </select>
            </div>
        </div>

        <!-- Quick-filter tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 mb-4">
            <button v-for="t in tabs" :key="t.key" @click="activeTab = t.key"
                    :class="activeTab === t.key ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 border-transparent' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
                    class="shrink-0 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium transition-all">
                {{ t.label }}
                <span :class="activeTab === t.key ? 'bg-white/20 dark:bg-gray-900/10' : 'bg-gray-100 dark:bg-gray-800'"
                      class="px-1.5 py-0.5 rounded tabular-nums">{{ t.count }}</span>
            </button>
        </div>

        <!-- Unit grid -->
        <div v-if="filtered.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <div v-for="u in filtered" :key="u.unit_id"
                 class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Unit {{ u.unit_number }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ u.unit_type }}<span v-if="buildings.length > 1"> · {{ u.building_name }}</span></p>
                    </div>
                    <span :class="['inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-medium shrink-0', stateMeta[u.state].cls]">
                        <span :class="['w-1.5 h-1.5 rounded-full', stateMeta[u.state].dot]" />
                        {{ stateMeta[u.state].label }}
                    </span>
                </div>

                <!-- Times -->
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-3 text-xs text-gray-500 dark:text-gray-400">
                    <span v-if="u.checkout" class="inline-flex items-center gap-1.5" title="Last checkout">
                        <DoorClosed class="w-3.5 h-3.5" /> Out {{ fmtDate(u.checkout.date) }}<template v-if="u.checkout.time"> · {{ u.checkout.time }}</template>
                    </span>
                    <span v-if="u.arrival" class="inline-flex items-center gap-1.5 text-gray-600 dark:text-gray-300" title="Next arrival">
                        <LogIn class="w-3.5 h-3.5" /> In {{ fmtDate(u.arrival.date) }}<template v-if="u.arrival.time"> · {{ u.arrival.time }}</template>
                    </span>
                </div>

                <!-- Action -->
                <div v-if="['needs_cleaning', 'cleaning', 'ready_for_qa'].includes(u.state)" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <button v-if="u.state === 'needs_cleaning'" @click="askRequest(u)"
                            class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:opacity-90 transition-all">
                        <Sparkles class="w-3.5 h-3.5" /> Request cleaning
                    </button>
                    <template v-else>
                        <button v-if="u.state === 'cleaning'" @click="askCleaned(u)"
                                class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:opacity-90 transition-all">
                            <CheckCircle class="w-3.5 h-3.5" /> Mark cleaned
                        </button>
                        <button @click="askCancel(u)"
                                class="w-full mt-1.5 text-xs font-medium text-gray-400 hover:text-red-500 transition-colors">
                            Cancel cleaning
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <div v-else class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl py-16 text-center">
            <Building2 class="w-10 h-10 text-gray-300 dark:text-gray-700 mx-auto mb-3" />
            <p class="text-gray-500 dark:text-gray-400">No units match this view.</p>
        </div>

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
