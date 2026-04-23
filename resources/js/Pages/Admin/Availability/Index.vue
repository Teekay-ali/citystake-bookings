<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Building2, CheckCircle2, X, ChevronRight } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    buildings:    Array,
    allBuildings: Array,
    selectedDate: String,
    filters:      Object,
    summary:      Object,
})

const date       = ref(props.selectedDate)
const buildingId = ref(props.filters.building_id ?? '')

// Collapsed state per building — first building expanded by default
const collapsedBuildings = ref(
    Object.fromEntries(
        (props.buildings ?? []).map((b, i) => [b.id, i !== 0])
    )
)

function toggleBuilding(id) {
    collapsedBuildings.value[id] = !collapsedBuildings.value[id]
}

function selectBuilding(id) {
    buildingId.value = id ? String(id) : ''
    if (id) {
        // Expand selected building, collapse all others
        Object.keys(collapsedBuildings.value).forEach(k => {
            collapsedBuildings.value[k] = String(k) !== String(id)
        })
    } else {
        // All buildings — expand first, collapse rest
        Object.keys(collapsedBuildings.value).forEach((k, i) => {
            collapsedBuildings.value[k] = i !== 0
        })
    }
    applyFilters()
}

function applyFilters() {
    router.get(route('manage.availability.index'), {
        date:        date.value,
        building_id: buildingId.value || undefined,
    }, { preserveState: true, replace: true })
}

function goToDate(offset) {
    const d = new Date(date.value)
    d.setDate(d.getDate() + offset)
    date.value = d.toISOString().split('T')[0]
    applyFilters()
}

const statusConfig = {
    available:   { label: 'Available',   bg: 'bg-emerald-50 dark:bg-emerald-900/20', border: 'border-emerald-200 dark:border-emerald-800', text: 'text-emerald-700 dark:text-emerald-400', dot: 'bg-emerald-500' },
    occupied:    { label: 'Reserved',    bg: 'bg-blue-50 dark:bg-blue-900/20',       border: 'border-blue-200 dark:border-blue-800',       text: 'text-blue-700 dark:text-blue-400',       dot: 'bg-blue-500' },
    checked_in:  { label: 'Checked In',  bg: 'bg-violet-50 dark:bg-violet-900/20',   border: 'border-violet-200 dark:border-violet-800',   text: 'text-violet-700 dark:text-violet-400',   dot: 'bg-violet-500' },
    maintenance: { label: 'Maintenance', bg: 'bg-amber-50 dark:bg-amber-900/20',     border: 'border-amber-200 dark:border-amber-800',     text: 'text-amber-700 dark:text-amber-400',     dot: 'bg-amber-500' },
    retired:     { label: 'Retired',     bg: 'bg-gray-50 dark:bg-gray-900',          border: 'border-gray-200 dark:border-gray-700',       text: 'text-gray-400 dark:text-gray-600',       dot: 'bg-gray-300' },
}

const selectedUnit = ref(null)

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatAmount(n) {
    return '₦' + Number(n).toLocaleString('en-NG', { minimumFractionDigits: 0 })
}

function unitCount(building) {
    return building.unit_types.reduce((sum, ut) => sum + ut.units.length, 0)
}
</script>

<template>
    <Head title="Availability Board" />

    <div class="p-6 lg:p-8">

        <!-- ── Header ── -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Availability Board</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Live unit status for {{ formatDate(selectedDate) }}
                </p>
            </div>

            <!-- Date nav -->
            <div class="flex items-center gap-2">
                <button @click="goToDate(-1)"
                        class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900 text-gray-500 dark:text-gray-400 transition-all">
                    ←
                </button>
                <input type="date" v-model="date" @change="applyFilters"
                       class="px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all" />
                <button @click="goToDate(1)"
                        class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900 text-gray-500 dark:text-gray-400 transition-all">
                    →
                </button>
                <button @click="date = new Date().toISOString().split('T')[0]; applyFilters()"
                        class="px-3 py-2 text-sm font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 transition-all">
                    Today
                </button>
            </div>
        </div>

        <!-- ── Summary cards ── -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Total Units</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ summary.total }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <p class="text-xs font-medium text-emerald-500 uppercase tracking-wider mb-2">Available</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ summary.available }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <p class="text-xs font-medium text-blue-500 uppercase tracking-wider mb-2">Occupied</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ summary.occupied }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <p class="text-xs font-medium text-amber-500 uppercase tracking-wider mb-2">Maintenance</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ summary.maintenance }}</p>
            </div>
        </div>

        <!-- ── Building filter ── -->
        <div class="flex items-center gap-2 mb-6 flex-wrap">
            <button @click="selectBuilding('')"
                    :class="buildingId === ''
                        ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                        : 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800'"
                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all">
                All Buildings
            </button>
            <button v-for="b in allBuildings" :key="b.id"
                    @click="selectBuilding(b.id)"
                    :class="buildingId === String(b.id)
                        ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                        : 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800'"
                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all">
                {{ b.name }}
            </button>
        </div>

        <!-- ── Unit grids per building ── -->
        <div v-if="buildings.length > 0" class="space-y-3">
            <div v-for="building in buildings" :key="building.id">

                <!-- Building header — clickable to collapse/expand -->
                <button
                    @click="toggleBuilding(building.id)"
                    :class="collapsedBuildings[building.id]
                        ? 'rounded-xl'
                        : 'rounded-t-xl rounded-b-none'"
                    class="w-full flex items-center justify-between gap-2 px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                    <div class="flex items-center gap-2">
                        <Building2 class="w-4 h-4 text-gray-400" />
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ building.name }}</span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            · {{ unitCount(building) }} unit{{ unitCount(building) !== 1 ? 's' : '' }}
                        </span>
                    </div>
                    <ChevronRight
                        :class="collapsedBuildings[building.id] ? 'rotate-0' : 'rotate-90'"
                        class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" />
                </button>

                <!-- Collapsible unit content -->
                <Transition
                    enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-1">
                    <div v-if="!collapsedBuildings[building.id]"
                         class="border border-t-0 border-gray-200 dark:border-gray-800 rounded-b-xl px-4 py-4 space-y-5">
                        <div v-for="unitType in building.unit_types" :key="unitType.id">
                            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">
                                {{ unitType.name }} — {{ unitType.bedroom_type }}
                            </p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5">
                                <button
                                    v-for="unit in unitType.units"
                                    :key="unit.id"
                                    @click="selectedUnit = selectedUnit?.id === unit.id ? null : unit"
                                    :class="[
                                        statusConfig[unit.availability].bg,
                                        statusConfig[unit.availability].border,
                                        'border rounded-xl p-3.5 text-left transition-all',
                                        selectedUnit?.id === unit.id
                                            ? 'ring-2 ring-gray-900 dark:ring-white ring-offset-1'
                                            : 'hover:brightness-95 dark:hover:brightness-110'
                                    ]">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ unit.unit_number }}
                                        </span>
                                        <span :class="['w-2 h-2 rounded-full flex-shrink-0', statusConfig[unit.availability].dot]"></span>
                                    </div>
                                    <span :class="['text-xs font-medium', statusConfig[unit.availability].text]">
                                        {{ statusConfig[unit.availability].label }}
                                    </span>
                                    <div v-if="unit.current_booking" class="mt-2 space-y-0.5">
                                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                            {{ unit.current_booking.guest_name }}
                                        </p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">
                                            out {{ formatDate(unit.current_booking.check_out) }}
                                        </p>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>

            </div>
        </div>

        <!-- ── Empty state ── -->
        <div v-else class="text-center py-20">
            <div class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-4">
                <Building2 class="w-6 h-6 text-gray-400" />
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">No buildings found.</p>
        </div>

    </div>

    <!-- ── Unit detail drawer ── -->
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="translate-x-full opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-full opacity-0">
        <div v-if="selectedUnit"
             class="fixed right-0 top-0 bottom-0 w-full sm:w-96 bg-white dark:bg-gray-950 border-l border-gray-200 dark:border-gray-800 z-50 overflow-y-auto">
            <div class="p-6">

                <!-- Drawer header -->
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        Unit {{ selectedUnit.unit_number }}
                    </h3>
                    <button @click="selectedUnit = null"
                            class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-900 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-all">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Status badge -->
                <div :class="[
                    statusConfig[selectedUnit.availability].bg,
                    statusConfig[selectedUnit.availability].border,
                    'border rounded-lg px-3 py-2.5 mb-5 flex items-center gap-2'
                ]">
                    <span :class="['w-2 h-2 rounded-full flex-shrink-0', statusConfig[selectedUnit.availability].dot]"></span>
                    <span :class="['text-sm font-medium', statusConfig[selectedUnit.availability].text]">
                        {{ statusConfig[selectedUnit.availability].label }}
                    </span>
                </div>

                <!-- No booking -->
                <div v-if="!selectedUnit.current_booking" class="text-center py-10">
                    <CheckCircle2 class="w-10 h-10 text-emerald-400 mx-auto mb-3" />
                    <p class="text-sm text-gray-500 dark:text-gray-400">No booking for this date.</p>
                    <p class="text-xs text-gray-400 dark:text-gray-600 mt-1">Unit is ready for reservation.</p>
                </div>

                <!-- Booking details -->
                <div v-else class="space-y-3">
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4 space-y-3">
                        <div class="flex justify-between">
                            <span class="text-xs text-gray-400 dark:text-gray-500">Guest</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedUnit.current_booking.guest_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-gray-400 dark:text-gray-500">Phone</span>
                            <span class="text-sm text-gray-900 dark:text-white">{{ selectedUnit.current_booking.guest_phone }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-gray-400 dark:text-gray-500">Check-in</span>
                            <span class="text-sm text-gray-900 dark:text-white">{{ formatDate(selectedUnit.current_booking.check_in) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-gray-400 dark:text-gray-500">Check-out</span>
                            <span class="text-sm text-gray-900 dark:text-white">{{ formatDate(selectedUnit.current_booking.check_out) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-gray-400 dark:text-gray-500">Nights</span>
                            <span class="text-sm text-gray-900 dark:text-white">{{ selectedUnit.current_booking.nights }}</span>
                        </div>
                        <div class="flex justify-between border-t border-gray-200 dark:border-gray-800 pt-3">
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Total</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatAmount(selectedUnit.current_booking.total_amount) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-gray-400 dark:text-gray-500">Payment</span>
                            <span :class="selectedUnit.current_booking.payment_status === 'paid'
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : 'text-amber-600 dark:text-amber-400'"
                                  class="text-sm font-medium capitalize">
                                {{ selectedUnit.current_booking.payment_status }}
                            </span>
                        </div>
                    </div>

                    <Link
                        v-if="selectedUnit.current_booking?.status === 'confirmed'"
                        :href="route('manage.bookings.show', selectedUnit.current_booking.id)"
                        class="block w-full text-center px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:bg-gray-700 dark:hover:bg-gray-100 transition-all">
                        Check-in Guest →
                    </Link>

                    <Link
                        :href="route('manage.bookings.show', selectedUnit.current_booking.id)"
                        class="block w-full text-center px-4 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-900 transition-all">
                        View Full Booking
                    </Link>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Drawer backdrop -->
    <Transition
        enter-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0">
        <div v-if="selectedUnit"
             @click="selectedUnit = null"
             class="fixed inset-0 bg-black/20 dark:bg-black/40 z-40 backdrop-blur-sm" />
    </Transition>

</template>
