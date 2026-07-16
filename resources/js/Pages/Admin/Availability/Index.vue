<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Building2, ChevronRight, ChevronLeft, ChevronDown, X, Loader2 } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    buildings:    Array,
    allBuildings: Array,
    startDate:    String,
    today:        String,
    days:         Number,
    filters:      Object,
})

// ── Date helpers (UTC-based to avoid timezone day-shift) ──────
const DAY_MS = 86400000
// Parse a 'YYYY-MM-DD' string to a UTC timestamp
function ymdToMs(ymd) { return Date.parse(ymd + 'T00:00:00Z') }
// Format a UTC timestamp back to 'YYYY-MM-DD'
function msToYmd(ms) { return new Date(ms).toISOString().split('T')[0] }
// Local calendar day as 'YYYY-MM-DD' (no UTC shift)
function localYmd(d) {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

// ── Date range ────────────────────────────────────────────────
const dates = computed(() => {
    const result = []
    const startTs = ymdToMs(props.startDate)
    for (let i = 0; i < props.days; i++) {
        result.push(msToYmd(startTs + i * DAY_MS))
    }
    return result
})

// Server-provided "today" in app timezone (Africa/Lagos), so the highlight
// is Nigerian today regardless of the viewer's browser timezone.
const todayStr = computed(() => props.today ?? localYmd(new Date()))

function isToday(date) { return date === todayStr.value }
function isWeekend(date) {
    const d = new Date(date + 'T00:00:00')
    return d.getDay() === 0 || d.getDay() === 6
}
function dayLabel(date) {
    const d = new Date(date + 'T00:00:00')
    return d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' })
}
function shortDay(date) {
    const d = new Date(date + 'T00:00:00')
    return d.getDate()
}
function monthLabel(date) {
    const d = new Date(date + 'T00:00:00')
    return d.toLocaleDateString('en-GB', { month: 'short', year: 'numeric' })
}

// ── Navigation ────────────────────────────────────────────────
function navigate(days) {
    const d = new Date(props.startDate + 'T00:00:00')
    d.setDate(d.getDate() + days)
    applyFilters({ start: d.toISOString().split('T')[0] })
}

function goToToday() {
    applyFilters({ start: todayStr.value })
}

// ── Filters ───────────────────────────────────────────────────
const buildingId = ref(props.filters.building_id ?? '')
const rangeDays  = ref(props.filters.days ?? 30)
const loading    = ref(false)

const ranges = [7, 14, 30, 60]

// Keep local controls in sync with the URL (back/forward navigation)
watch(() => props.filters, (f) => {
    buildingId.value = f.building_id ?? ''
    rangeDays.value  = f.days ?? 30
})

function selectBuilding(id) {
    buildingId.value = id === buildingId.value ? '' : id
    applyFilters()
}

function setRange(d) {
    rangeDays.value = d
    applyFilters()
}

function applyFilters(extra = {}) {
    router.get(route('manage.availability.index'), {
        building_id: buildingId.value || undefined,
        start:       props.startDate,
        days:        rangeDays.value,
        ...extra,
    }, {
        preserveState: true,
        replace: true,
        onStart:  () => { loading.value = true },
        onFinish: () => { loading.value = false },
    })
}

// ── Collapsed buildings ───────────────────────────────────────
const collapsed = ref({})
function toggleBuilding(id) {
    collapsed.value[id] = !collapsed.value[id]
}

// ── Precomputed cell grid ─────────────────────────────────────
// Build each unit's per-date cells ONCE (kind + occupant + a single
// booking bar clamped to the window). Avoids re-running find()s per
// cell on every render, and correctly handles blocked dates, bleed
// bookings, maintenance and unavailable units.

function buildUnitCells(unit) {
    const ds       = dates.value
    const startTs  = ymdToMs(props.startDate)
    const endExcl  = startTs + ds.length * DAY_MS
    const blocked  = unit.blocked ?? []
    const bookings = unit.bookings ?? []
    const offline  = unit.status !== 'available' || !unit.is_available

    const cells = ds.map(date => {
        let kind = 'available'
        let booking = null
        let blockedReason = null

        if (offline) {
            kind = 'offline'
        } else {
            const bl = blocked.find(b => b.from <= date && b.to >= date)
            if (bl) {
                kind = 'blocked'
                blockedReason = bl.reason
            } else {
                booking = bookings.find(b => b.check_in <= date && b.check_out > date) ?? null
                if (booking) kind = booking.status === 'checked_in' ? 'checked_in' : 'occupied'
            }
        }
        return { date, kind, booking, blockedReason, bar: null }
    })

    // One bar per booking, clamped to the visible window (fixes bleed overflow)
    bookings.forEach(b => {
        const ciTs     = ymdToMs(b.check_in)
        const coTs     = ymdToMs(b.check_out)
        const visStart = Math.max(ciTs, startTs)
        const visEnd   = Math.min(coTs, endExcl)
        if (visEnd <= visStart) return
        const idx  = Math.round((visStart - startTs) / DAY_MS)
        const span = Math.round((visEnd - visStart) / DAY_MS)
        if (cells[idx]) cells[idx].bar = { span, guest: b.guest_name, status: b.status, paid: b.payment_status === 'paid' }
    })

    return cells
}

const decoratedBuildings = computed(() =>
    props.buildings.map(b => ({
        ...b,
        unit_types: (b.unit_types ?? []).map(ut => ({
            ...ut,
            units: (ut.units ?? []).map(u => ({ ...u, cells: buildUnitCells(u) })),
        })),
    }))
)

const cellBg = {
    available: 'bg-white dark:bg-gray-950 hover:bg-emerald-50 dark:hover:bg-emerald-900/10 cursor-pointer group',
    occupied:  'bg-indigo-50 dark:bg-indigo-900/20 cursor-pointer',
    checked_in:'bg-blue-100 dark:bg-blue-900/30 cursor-pointer',
    blocked:   'bg-gray-100 dark:bg-gray-800/60 cursor-not-allowed bg-[repeating-linear-gradient(45deg,transparent,transparent_4px,rgba(0,0,0,0.05)_4px,rgba(0,0,0,0.05)_8px)]',
    offline:   'bg-amber-50 dark:bg-amber-900/20 cursor-not-allowed',
}

// ── Selected booking drawer ───────────────────────────────────
const selectedBooking = ref(null)
const selectedUnit    = ref(null)
const selectedDate    = ref(null)

function handleCellClick(unit, cell, building, unitType) {
    if (cell.kind === 'offline' || cell.kind === 'blocked') return

    if (cell.booking) {
        selectedBooking.value = cell.booking
        selectedUnit.value    = null
        selectedDate.value    = null
    } else {
        selectedBooking.value = null
        selectedUnit.value    = { unit, building, unitType }
        selectedDate.value    = cell.date
    }
}

function closeDrawer() {
    selectedBooking.value = null
    selectedUnit.value    = null
    selectedDate.value    = null
}

function goToBooking(id) {
    router.visit(route('manage.bookings.show', id))
}

function createBooking(unit, building, unitType, date) {
    router.visit(route('manage.bookings.create'), {
        method: 'get',
        data: {
            unit_id:      unit.id,
            building_id:  building.id,
            unit_type_id: unitType.id,
            check_in:     date,
            check_out:    msToYmd(ymdToMs(date) + DAY_MS),
            nights:       1,
        },
    })
}

function formatAmount(n) {
    return '₦' + Number(n).toLocaleString('en-NG', { minimumFractionDigits: 0 })
}

function formatDate(d) {
    return new Date(d + 'T00:00:00').toLocaleDateString('en-NG', {
        day: 'numeric', month: 'short', year: 'numeric'
    })
}

// ── Legend (collapsible on mobile) ────────────────────────────
const showLegend = ref(false)
const legendItems = [
    { label: 'Available',            cls: 'w-3 h-3 rounded-sm bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-700' },
    { label: 'Booked',               cls: 'w-3 h-3 rounded-sm bg-indigo-500' },
    { label: 'Checked In',           cls: 'w-3 h-3 rounded-sm bg-blue-500' },
    { label: 'Payment pending',      cls: 'w-2.5 h-2.5 rounded-full bg-amber-300' },
    { label: 'Blocked',              cls: 'w-3 h-3 rounded-sm bg-gray-200 dark:bg-gray-700' },
    { label: 'Maintenance / Offline',cls: 'w-3 h-3 rounded-sm bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800' },
]

// Month headers
const monthGroups = computed(() => {
    const groups = []
    let current = null
    dates.value.forEach(d => {
        const m = monthLabel(d)
        if (m !== current) {
            groups.push({ label: m, count: 1 })
            current = m
        } else {
            groups[groups.length - 1].count++
        }
    })
    return groups
})
</script>

<template>
    <Head title="Availability" />

    <div class="p-4 lg:p-6 flex flex-col gap-4 h-full min-h-0">

        <!-- ── Header ── -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Availability</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ rangeDays }}-day booking timeline</p>
                </div>
                <Loader2 v-if="loading" class="w-4 h-4 text-gray-400 animate-spin" />
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <!-- Range selector -->
                <div class="flex items-center rounded-lg border border-gray-200 dark:border-gray-800 overflow-hidden">
                    <button v-for="d in ranges" :key="d" @click="setRange(d)"
                            :class="rangeDays === d ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-900'"
                            class="px-2.5 h-8 text-xs font-medium transition-colors border-r border-gray-200 dark:border-gray-800 last:border-r-0">
                        {{ d }}d
                    </button>
                </div>

                <!-- Navigate -->
                <button @click="navigate(-rangeDays)"
                        class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900 text-gray-500 transition-colors">
                    <ChevronLeft class="w-4 h-4" />
                </button>
                <button @click="goToToday"
                        class="px-3 h-8 text-sm font-medium rounded-lg border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900 text-gray-700 dark:text-gray-300 transition-colors">
                    Today
                </button>
                <button @click="navigate(rangeDays)"
                        class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900 text-gray-500 transition-colors">
                    <ChevronRight class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Building filter -->
        <div class="flex items-center gap-1.5 flex-wrap">
            <button @click="selectBuilding('')"
                    :class="!buildingId ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800'"
                    class="px-3 h-8 rounded-lg text-sm font-medium transition-all">
                All buildings
            </button>
            <button v-for="b in allBuildings" :key="b.id"
                    @click="selectBuilding(String(b.id))"
                    :class="buildingId === String(b.id) ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800'"
                    class="px-3 h-8 rounded-lg text-sm font-medium transition-all">
                {{ b.name }}
            </button>
        </div>

        <!-- ── Legend ── -->
        <div class="shrink-0">
            <!-- Mobile: compact toggle -->
            <button type="button" @click="showLegend = !showLegend"
                    class="sm:hidden flex items-center gap-1 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                Legend
                <ChevronDown :class="showLegend ? 'rotate-180' : ''" class="w-3.5 h-3.5 transition-transform" />
            </button>

            <!-- Items: hidden on mobile until toggled, always shown from sm up -->
            <div :class="showLegend ? 'flex' : 'hidden'"
                 class="sm:flex items-center flex-wrap gap-x-4 gap-y-1.5 text-xs text-gray-500 dark:text-gray-400 mt-2 sm:mt-0">
                <span v-for="item in legendItems" :key="item.label" class="flex items-center gap-1.5">
                    <span :class="item.cls"></span> {{ item.label }}
                </span>
            </div>
        </div>

        <!-- ── Timeline grid ── -->
        <div class="flex-1 min-h-0 overflow-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950">
            <table class="border-collapse" style="table-layout: fixed; min-width: max-content;">

                <!-- Month headers -->
                <thead>
                <tr>
                    <!-- Unit label column -->
                    <th class="sticky left-0 top-0 z-40 bg-gray-50 dark:bg-gray-900 border-b border-r border-gray-200 dark:border-gray-800 w-28 min-w-28 sm:w-36 sm:min-w-36"></th>
                    <th v-for="group in monthGroups" :key="group.label"
                        :colspan="group.count"
                        class="sticky top-0 z-30 text-xs font-semibold text-gray-500 dark:text-gray-400 text-left px-2 py-1.5 border-b border-r border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900">
                        {{ group.label }}
                    </th>
                </tr>

                <!-- Day headers -->
                <tr>
                    <th class="sticky left-0 top-7 z-40 bg-gray-50 dark:bg-gray-900 border-b border-r border-gray-200 dark:border-gray-800 w-28 min-w-28 sm:w-36 sm:min-w-36 px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 text-left">
                        Unit
                    </th>
                    <th v-for="date in dates" :key="date"
                        :class="[
                                'sticky top-7 z-30 w-10 min-w-10 border-b border-r border-gray-200 dark:border-gray-800 text-center py-1.5 text-xs font-medium',
                                isToday(date) ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400',
                                isWeekend(date) && !isToday(date) ? 'text-amber-500 dark:text-amber-400' : '',
                            ]">
                        {{ shortDay(date) }}
                    </th>
                </tr>
                </thead>

                <tbody>
                <template v-for="building in decoratedBuildings" :key="building.id">

                    <!-- Building row -->
                    <tr class="cursor-pointer" @click="toggleBuilding(building.id)">
                        <td :colspan="days + 1"
                            class="sticky left-0 px-3 py-2 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs font-semibold text-gray-700 dark:text-gray-300">
                            <div class="flex items-center gap-1.5">
                                <ChevronRight :class="collapsed[building.id] ? 'rotate-0' : 'rotate-90'" class="w-3.5 h-3.5 transition-transform text-gray-400" />
                                <Building2 class="w-3.5 h-3.5 text-gray-400" />
                                {{ building.name }}
                                <span class="font-normal text-gray-400 ml-1">
                                        · {{ building.unit_types?.reduce((s, ut) => s + ut.units.length, 0) }} units
                                    </span>
                            </div>
                        </td>
                    </tr>

                    <template v-if="!collapsed[building.id]">
                        <template v-for="unitType in building.unit_types" :key="unitType.id">
                            <!-- Unit type subheader -->
                            <tr>
                                <td :colspan="days + 1"
                                    class="px-4 py-1 bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-800/50 text-xs text-gray-400 dark:text-gray-500 italic">
                                    {{ unitType.name }}
                                </td>
                            </tr>

                            <!-- Unit rows -->
                            <tr v-for="unit in unitType.units" :key="unit.id"
                                class="border-b border-gray-100 dark:border-gray-800/50 hover:bg-gray-50/30 dark:hover:bg-gray-900/30">

                                <!-- Unit label -->
                                <td class="sticky left-0 z-20 bg-white dark:bg-gray-950 border-r border-gray-200 dark:border-gray-800 px-3 py-0 h-10 w-28 min-w-28 sm:w-36 sm:min-w-36">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ unit.unit_number }}</span>
                                        <span v-if="unit.status === 'maintenance'"
                                              class="text-[10px] text-amber-600 dark:text-amber-400 font-medium">maint.</span>
                                        <span v-else-if="!unit.is_available"
                                              class="text-[10px] text-amber-600 dark:text-amber-400 font-medium">offline</span>
                                    </div>
                                </td>

                                <!-- Day cells -->
                                <td v-for="cell in unit.cells" :key="cell.date"
                                    :title="cell.kind === 'blocked' ? (cell.blockedReason || 'Blocked') : ''"
                                    :class="[cellBg[cell.kind], 'relative border-r border-gray-100 dark:border-gray-800/50 h-10 p-0']"
                                    @click="handleCellClick(unit, cell, building, unitType)">

                                    <!-- Booking bar - rendered once at its visible start, clamped to window -->
                                    <div v-if="cell.bar"
                                         :style="`width: calc(${cell.bar.span} * 2.5rem - 2px); min-width: calc(${cell.bar.span} * 2.5rem - 2px);`"
                                         :class="[
                                             'absolute top-1 bottom-1 left-0.5 rounded-md z-10 flex items-center gap-1 px-2 overflow-hidden',
                                             cell.bar.status === 'checked_in' ? 'bg-blue-500 dark:bg-blue-600' : 'bg-indigo-500 dark:bg-indigo-600'
                                         ]">
                                        <span v-if="!cell.bar.paid" class="w-1.5 h-1.5 rounded-full bg-amber-300 shrink-0" title="Payment pending"></span>
                                        <span class="text-[10px] font-medium text-white truncate">{{ cell.bar.guest }}</span>
                                    </div>

                                    <!-- Available cell hover hint -->
                                    <span v-else-if="cell.kind === 'available'"
                                          class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 text-[10px] text-emerald-500 font-medium transition-opacity pointer-events-none">
                                        +
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </template>
                </template>

                <!-- Empty state -->
                <tr v-if="decoratedBuildings.length === 0">
                    <td :colspan="days + 1" class="py-20 text-center text-sm text-gray-400">
                        No buildings found.
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- ── Drawer - booking detail or new booking ── -->
        <Transition
            enter-active-class="transition-all duration-250 ease-out"
            enter-from-class="translate-x-full opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-full opacity-0">

            <!-- Existing booking -->
            <div v-if="selectedBooking"
                 class="fixed right-0 top-0 bottom-0 w-full sm:w-96 bg-white dark:bg-gray-950 border-l border-gray-200 dark:border-gray-800 z-50 overflow-y-auto shadow-2xl">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Booking Details</h3>
                        <button @click="closeDrawer" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-900 text-gray-400 transition-colors"><X class="w-4 h-4" /></button>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Reference</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ selectedBooking.reference }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Guest</p>
                            <p class="text-sm text-gray-900 dark:text-white">{{ selectedBooking.guest_name }}</p>
                            <p class="text-xs text-gray-400">{{ selectedBooking.guest_phone }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Unit</p>
                            <p class="text-sm text-gray-900 dark:text-white">{{ selectedBooking.unit_type }} · {{ selectedBooking.unit_number }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Check-in</p>
                                <p class="text-sm text-gray-900 dark:text-white">{{ formatDate(selectedBooking.check_in) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Check-out</p>
                                <p class="text-sm text-gray-900 dark:text-white">{{ formatDate(selectedBooking.check_out) }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Status</p>
                                <span :class="[
                                    'text-xs font-medium px-2 py-0.5 rounded-full',
                                    selectedBooking.status === 'checked_in' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' :
                                    selectedBooking.status === 'confirmed'  ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' :
                                    'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                                ]">{{ selectedBooking.status }}</span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Payment</p>
                                <span :class="[
                                    'text-xs font-medium px-2 py-0.5 rounded-full',
                                    selectedBooking.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                                ]">{{ selectedBooking.payment_status }}</span>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatAmount(selectedBooking.total_amount) }}</p>
                        </div>
                    </div>

                    <button @click="goToBooking(selectedBooking.reference)"
                            class="mt-8 w-full px-4 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-xl hover:opacity-90 transition-all">
                        View Full Booking →
                    </button>
                </div>
            </div>

            <!-- New booking -->
            <div v-else-if="selectedUnit"
                 class="fixed right-0 top-0 bottom-0 w-full sm:w-96 bg-white dark:bg-gray-950 border-l border-gray-200 dark:border-gray-800 z-50 overflow-y-auto shadow-2xl">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">New Booking</h3>
                        <button @click="closeDrawer" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-900 text-gray-400 transition-colors"><X class="w-4 h-4" /></button>
                    </div>

                    <div class="space-y-3 mb-6">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Unit</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ selectedUnit.building.name }} · {{ selectedUnit.unit.unit_number }}
                            </p>
                            <p class="text-xs text-gray-400">{{ selectedUnit.unitType.name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Check-in</p>
                            <p class="text-sm text-gray-900 dark:text-white">{{ formatDate(selectedDate) }}</p>
                        </div>
                    </div>

                    <button @click="createBooking(selectedUnit.unit, selectedUnit.building, selectedUnit.unitType, selectedDate)"
                            class="w-full px-4 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-xl hover:opacity-90 transition-all">
                        Create Booking →
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Backdrop -->
        <div v-if="selectedBooking || selectedUnit"
             @click="closeDrawer"
             class="fixed inset-0 bg-black/20 dark:bg-black/40 z-40" />

    </div>
</template>
