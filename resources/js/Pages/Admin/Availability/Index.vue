<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Building2, ChevronRight, ChevronLeft } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    buildings:    Array,
    allBuildings: Array,
    startDate:    String,
    days:         Number,
    filters:      Object,
})

// ── Date range ────────────────────────────────────────────────
const dates = computed(() => {
    const result = []
    const start = new Date(props.startDate + 'T00:00:00')
    for (let i = 0; i < props.days; i++) {
        const d = new Date(start)
        d.setDate(d.getDate() + i)
        result.push(d.toISOString().split('T')[0])
    }
    return result
})

const todayStr = new Date().toISOString().split('T')[0]

function isToday(date) { return date === todayStr }
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
    applyFilters({ start: todayStr })
}

// ── Filters ───────────────────────────────────────────────────
const buildingId = ref(props.filters.building_id ?? '')

function selectBuilding(id) {
    buildingId.value = id === buildingId.value ? '' : id
    applyFilters()
}

function applyFilters(extra = {}) {
    router.get(route('manage.availability.index'), {
        building_id: buildingId.value || undefined,
        start:       props.startDate,
        ...extra,
    }, { preserveState: true, replace: true })
}

// ── Collapsed buildings ───────────────────────────────────────
const collapsed = ref({})
function toggleBuilding(id) {
    collapsed.value[id] = !collapsed.value[id]
}

// ── Booking lookup ────────────────────────────────────────────
function getBookingForDate(unit, date) {
    return unit.bookings?.find(b => b.check_in <= date && b.check_out > date) ?? null
}

function isCheckIn(unit, date) {
    return unit.bookings?.some(b => b.check_in === date) ?? false
}

function isCheckOut(unit, date) {
    return unit.bookings?.some(b => b.check_out === date) ?? false
}

// ── Cell rendering ────────────────────────────────────────────
function cellClass(unit, date) {
    if (unit.status === 'maintenance') {
        return 'bg-amber-50 dark:bg-amber-900/20 cursor-not-allowed'
    }
    const booking = getBookingForDate(unit, date)
    if (!booking) return 'bg-white dark:bg-gray-950 hover:bg-emerald-50 dark:hover:bg-emerald-900/10 cursor-pointer group'
    if (booking.status === 'checked_in') return 'bg-blue-100 dark:bg-blue-900/30 cursor-pointer'
    return 'bg-rose-100 dark:bg-rose-900/30 cursor-pointer'
}

// ── Booking bar spans ─────────────────────────────────────────
// Returns the booking block to render for a given unit + date
// Only returns a value on the check_in day so we render it once
function getBookingBlock(unit, date) {
    const booking = unit.bookings?.find(b => b.check_in === date)
    if (!booking) return null

    // Calculate span width — how many days visible from check_in
    const checkIn  = new Date(booking.check_in  + 'T00:00:00')
    const checkOut = new Date(booking.check_out + 'T00:00:00')
    const start    = new Date(props.startDate   + 'T00:00:00')
    const end      = new Date(dates.value[dates.value.length - 1] + 'T00:00:00')

    const visibleStart = checkIn  < start  ? start  : checkIn
    const visibleEnd   = checkOut > end    ? end    : checkOut

    const span = Math.round((visibleEnd - visibleStart) / (1000 * 60 * 60 * 24))

    return { booking, span }
}

// Bookings that start before our window but bleed into it
function getBleedBlock(unit) {
    const start = props.startDate
    return unit.bookings?.find(b => b.check_in < start && b.check_out > start) ?? null
}

// ── Selected booking drawer ───────────────────────────────────
const selectedBooking = ref(null)
const selectedUnit    = ref(null)
const selectedDate    = ref(null)

function handleCellClick(unit, date, building, unitType) {
    const booking = getBookingForDate(unit, date)
    if (unit.status === 'maintenance') return

    if (booking) {
        selectedBooking.value = booking
        selectedUnit.value    = null
        selectedDate.value    = null
    } else {
        selectedBooking.value = null
        selectedUnit.value    = { unit, building, unitType }
        selectedDate.value    = date
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

    <div class="p-4 lg:p-6 flex flex-col gap-4 min-h-full">

        <!-- ── Header ── -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Availability</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">30-day booking timeline</p>
            </div>

            <div class="flex items-center gap-2">
                <!-- Navigate -->
                <button @click="navigate(-30)"
                        class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900 text-gray-500 transition-colors">
                    <ChevronLeft class="w-4 h-4" />
                </button>
                <button @click="goToToday"
                        class="px-3 h-8 text-sm font-medium rounded-lg border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900 text-gray-700 dark:text-gray-300 transition-colors">
                    Today
                </button>
                <button @click="navigate(30)"
                        class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900 text-gray-500 transition-colors">
                    <ChevronRight class="w-4 h-4" />
                </button>

                <!-- Building filter -->
                <div class="flex items-center gap-1.5 ml-2 flex-wrap">
                    <button @click="selectBuilding('')"
                            :class="!buildingId ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800'"
                            class="px-3 h-8 rounded-lg text-sm font-medium transition-all">
                        All
                    </button>
                    <button v-for="b in allBuildings" :key="b.id"
                            @click="selectBuilding(String(b.id))"
                            :class="buildingId === String(b.id) ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800'"
                            class="px-3 h-8 rounded-lg text-sm font-medium transition-all">
                        {{ b.name }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Legend ── -->
        <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-700"></span> Available
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-rose-100 dark:bg-rose-900/30"></span> Confirmed
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-blue-100 dark:bg-blue-900/30"></span> Checked In
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-amber-50 dark:bg-amber-900/20"></span> Maintenance
            </span>
        </div>

        <!-- ── Timeline grid ── -->
        <div class="flex-1 overflow-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950">
            <table class="border-collapse" style="table-layout: fixed; min-width: max-content;">

                <!-- Month headers -->
                <thead>
                <tr>
                    <!-- Unit label column -->
                    <th class="sticky left-0 z-30 bg-gray-50 dark:bg-gray-900 border-b border-r border-gray-200 dark:border-gray-800 w-36 min-w-36"></th>
                    <th v-for="group in monthGroups" :key="group.label"
                        :colspan="group.count"
                        class="text-xs font-semibold text-gray-500 dark:text-gray-400 text-left px-2 py-1.5 border-b border-r border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900">
                        {{ group.label }}
                    </th>
                </tr>

                <!-- Day headers -->
                <tr>
                    <th class="sticky left-0 z-30 bg-gray-50 dark:bg-gray-900 border-b border-r border-gray-200 dark:border-gray-800 w-36 min-w-36 px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 text-left">
                        Unit
                    </th>
                    <th v-for="date in dates" :key="date"
                        :class="[
                                'w-10 min-w-10 border-b border-r border-gray-200 dark:border-gray-800 text-center py-1.5 text-xs font-medium',
                                isToday(date) ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400',
                                isWeekend(date) && !isToday(date) ? 'text-amber-500 dark:text-amber-400' : '',
                            ]">
                        {{ shortDay(date) }}
                    </th>
                </tr>
                </thead>

                <tbody>
                <template v-for="building in buildings" :key="building.id">

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
                                <td class="sticky left-0 z-20 bg-white dark:bg-gray-950 border-r border-gray-200 dark:border-gray-800 px-3 py-0 h-10 w-36 min-w-36">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ unit.unit_number }}</span>
                                        <span v-if="unit.status === 'maintenance'"
                                              class="text-[10px] text-amber-600 dark:text-amber-400 font-medium">maint.</span>
                                    </div>
                                </td>

                                <!-- Day cells -->
                                <td v-for="date in dates" :key="date"
                                    :class="[cellClass(unit, date), 'relative border-r border-gray-100 dark:border-gray-800/50 h-10 p-0']"
                                    @click="handleCellClick(unit, date, building, unitType)">

                                    <!-- Booking bar — only rendered on check_in day -->
                                    <template v-if="getBookingBlock(unit, date)">
                                        <div
                                            :style="`width: calc(${getBookingBlock(unit, date).span} * 2.5rem - 2px); min-width: calc(${getBookingBlock(unit, date).span} * 2.5rem - 2px);`"
                                            :class="[
                                                    'absolute top-1 bottom-1 left-0.5 rounded-md z-10 flex items-center px-2 overflow-hidden',
                                                    getBookingBlock(unit, date).booking.status === 'checked_in'
                                                        ? 'bg-blue-500 dark:bg-blue-600'
                                                        : 'bg-rose-500 dark:bg-rose-600'
                                                ]">
                                                <span class="text-[10px] font-medium text-white truncate">
                                                    {{ getBookingBlock(unit, date).booking.guest_name }}
                                                </span>
                                        </div>
                                    </template>

                                    <!-- Bleed block — booking started before window -->
                                    <template v-else-if="date === dates[0] && getBleedBlock(unit)">
                                        <div
                                            :style="`width: calc(${Math.round((new Date(getBleedBlock(unit).check_out + 'T00:00:00') - new Date(dates[0] + 'T00:00:00')) / 86400000)} * 2.5rem - 2px);`"
                                            :class="[
                                                    'absolute top-1 bottom-1 left-0.5 rounded-md z-10 flex items-center px-2 overflow-hidden',
                                                    getBleedBlock(unit).status === 'checked_in' ? 'bg-blue-500 dark:bg-blue-600' : 'bg-rose-500 dark:bg-rose-600'
                                                ]">
                                                <span class="text-[10px] font-medium text-white truncate">
                                                    {{ getBleedBlock(unit).guest_name }}
                                                </span>
                                        </div>
                                    </template>

                                    <!-- Available cell hover hint -->
                                    <span v-else-if="unit.status !== 'maintenance'"
                                          class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 text-[10px] text-emerald-500 font-medium transition-opacity pointer-events-none">
                                            +
                                        </span>
                                </td>
                            </tr>
                        </template>
                    </template>
                </template>

                <!-- Empty state -->
                <tr v-if="buildings.length === 0">
                    <td :colspan="days + 1" class="py-20 text-center text-sm text-gray-400">
                        No buildings found.
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- ── Drawer — booking detail or new booking ── -->
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
                        <button @click="closeDrawer" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-900 text-gray-400 transition-colors">✕</button>
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

                    <button @click="goToBooking(selectedBooking.id)"
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
                        <button @click="closeDrawer" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-900 text-gray-400 transition-colors">✕</button>
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
