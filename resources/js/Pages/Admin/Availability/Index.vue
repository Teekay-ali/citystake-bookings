<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Building2, CalendarDays, CheckCircle2, XCircle, Wrench, LogIn, ChevronDown } from 'lucide-vue-next'

const props = defineProps({
    buildings: Array,
    allBuildings: Array,
    selectedDate: String,
    filters: Object,
    summary: Object,
})

const date = ref(props.selectedDate)
const buildingId = ref(props.filters.building_id ?? '')

function applyFilters() {
    router.get(route('manage.availability.index'), {
        date: date.value,
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
    available:   { label: 'Available',    bg: 'bg-emerald-50 dark:bg-emerald-900/20', border: 'border-emerald-200 dark:border-emerald-800', text: 'text-emerald-700 dark:text-emerald-400', dot: 'bg-emerald-500' },
    occupied:    { label: 'Reserved',     bg: 'bg-blue-50 dark:bg-blue-900/20',       border: 'border-blue-200 dark:border-blue-800',       text: 'text-blue-700 dark:text-blue-400',       dot: 'bg-blue-500' },
    checked_in:  { label: 'Checked In',   bg: 'bg-violet-50 dark:bg-violet-900/20',   border: 'border-violet-200 dark:border-violet-800',   text: 'text-violet-700 dark:text-violet-400',   dot: 'bg-violet-500' },
    maintenance: { label: 'Maintenance',  bg: 'bg-amber-50 dark:bg-amber-900/20',     border: 'border-amber-200 dark:border-amber-800',     text: 'text-amber-700 dark:text-amber-400',     dot: 'bg-amber-500' },
    retired:     { label: 'Retired',      bg: 'bg-gray-50 dark:bg-gray-900/20',       border: 'border-gray-200 dark:border-gray-800',       text: 'text-gray-400 dark:text-gray-600',       dot: 'bg-gray-400' },
}

const selectedUnit = ref(null)

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatAmount(n) {
    return '₦' + Number(n).toLocaleString('en-NG', { minimumFractionDigits: 0 })
}
</script>

<template>
    <ManageLayout>
        <Head title="Availability Board" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">

                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                    <div>
                        <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-1">
                            Availability Board
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400">Live unit status for {{ formatDate(selectedDate) }}</p>
                    </div>

                    <!-- Date nav -->
                    <div class="flex items-center gap-2">
                        <button @click="goToDate(-1)"
                                class="p-2 rounded-xl border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900 transition-all text-gray-600 dark:text-gray-400">
                            ←
                        </button>
                        <input type="date" v-model="date" @change="applyFilters"
                               class="px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                        <button @click="goToDate(1)"
                                class="p-2 rounded-xl border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900 transition-all text-gray-600 dark:text-gray-400">
                            →
                        </button>
                        <button @click="date = new Date().toISOString().split('T')[0]; applyFilters()"
                                class="px-4 py-2 text-sm font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl hover:opacity-90 transition-all">
                            Today
                        </button>
                    </div>
                </div>

                <!-- Summary cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-2xl p-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Units</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ summary.total }}</p>
                    </div>
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl p-4">
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mb-1">Available</p>
                        <p class="text-2xl font-semibold text-emerald-700 dark:text-emerald-400">{{ summary.available }}</p>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-4">
                        <p class="text-xs text-blue-600 dark:text-blue-400 mb-1">Occupied</p>
                        <p class="text-2xl font-semibold text-blue-700 dark:text-blue-400">{{ summary.occupied }}</p>
                    </div>
                    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-4">
                        <p class="text-xs text-amber-600 dark:text-amber-400 mb-1">Maintenance</p>
                        <p class="text-2xl font-semibold text-amber-700 dark:text-amber-400">{{ summary.maintenance }}</p>
                    </div>
                </div>

                <!-- Building filter -->
                <div class="flex items-center gap-3 mb-8 flex-wrap">
                    <button @click="buildingId = ''; applyFilters()"
                            :class="buildingId === '' ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800'"
                            class="px-4 py-2 rounded-full text-sm font-medium transition-all">
                        All Buildings
                    </button>
                    <button v-for="b in allBuildings" :key="b.id"
                            @click="buildingId = String(b.id); applyFilters()"
                            :class="buildingId === String(b.id) ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800'"
                            class="px-4 py-2 rounded-full text-sm font-medium transition-all">
                        {{ b.name }}
                    </button>
                </div>

                <!-- Unit grids per building -->
                <div v-for="building in buildings" :key="building.id" class="mb-12">
                    <div class="flex items-center gap-3 mb-4">
                        <Building2 class="w-5 h-5 text-gray-400" />
                        <h2 class="text-xl font-medium text-gray-900 dark:text-white">{{ building.name }}</h2>
                    </div>

                    <div v-for="unitType in building.unit_types" :key="unitType.id" class="mb-6">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">
                            {{ unitType.name }} — {{ unitType.bedroom_type }}
                        </p>

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                            <button
                                v-for="unit in unitType.units"
                                :key="unit.id"
                                @click="selectedUnit = selectedUnit?.id === unit.id ? null : unit"
                                :class="[
                                    statusConfig[unit.availability].bg,
                                    statusConfig[unit.availability].border,
                                    'border rounded-2xl p-4 text-left transition-all hover:shadow-md',
                                    selectedUnit?.id === unit.id ? 'ring-2 ring-gray-900 dark:ring-white' : ''
                                ]">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ unit.unit_number }}
                                    </span>
                                    <span :class="['w-2 h-2 rounded-full', statusConfig[unit.availability].dot]"></span>
                                </div>
                                <span :class="['text-xs font-medium', statusConfig[unit.availability].text]">
                                    {{ statusConfig[unit.availability].label }}
                                </span>
                                <div v-if="unit.current_booking" class="mt-2">
                                    <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                        {{ unit.current_booking.guest_name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500">
                                        out {{ formatDate(unit.current_booking.check_out) }}
                                    </p>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="buildings.length === 0" class="text-center py-24">
                    <Building2 class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500 dark:text-gray-400">No buildings found.</p>
                </div>

            </div>
        </div>

        <!-- Unit detail drawer (slide in from right) -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="translate-x-full opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-full opacity-0">
            <div v-if="selectedUnit"
                 class="fixed right-0 top-0 bottom-0 w-full sm:w-96 bg-white dark:bg-gray-950 border-l border-gray-200 dark:border-gray-800 shadow-2xl z-50 overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Unit {{ selectedUnit.unit_number }}
                        </h3>
                        <button @click="selectedUnit = null"
                                class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-900 text-gray-500 transition-all">
                            ✕
                        </button>
                    </div>

                    <!-- Status badge -->
                    <div :class="[statusConfig[selectedUnit.availability].bg, statusConfig[selectedUnit.availability].border, 'border rounded-xl px-4 py-3 mb-6 flex items-center gap-2']">
                        <span :class="['w-2 h-2 rounded-full', statusConfig[selectedUnit.availability].dot]"></span>
                        <span :class="['text-sm font-medium', statusConfig[selectedUnit.availability].text]">
                            {{ statusConfig[selectedUnit.availability].label }}
                        </span>
                    </div>

                    <!-- No booking -->
                    <div v-if="!selectedUnit.current_booking" class="text-center py-8">
                        <CheckCircle2 class="w-10 h-10 text-emerald-400 mx-auto mb-3" />
                        <p class="text-gray-500 dark:text-gray-400 text-sm">No booking for this date.</p>
                        <p class="text-gray-400 dark:text-gray-600 text-xs mt-1">Unit is ready for walk-in or reservation.</p>
                    </div>

                    <!-- Booking details -->
                    <div v-else class="space-y-4">
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Guest</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedUnit.current_booking.guest_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Phone</span>
                                <span class="text-sm text-gray-900 dark:text-white">{{ selectedUnit.current_booking.guest_phone }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Check-in</span>
                                <span class="text-sm text-gray-900 dark:text-white">{{ formatDate(selectedUnit.current_booking.check_in) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Check-out</span>
                                <span class="text-sm text-gray-900 dark:text-white">{{ formatDate(selectedUnit.current_booking.check_out) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Nights</span>
                                <span class="text-sm text-gray-900 dark:text-white">{{ selectedUnit.current_booking.nights }}</span>
                            </div>
                            <div class="flex justify-between border-t border-gray-200 dark:border-gray-800 pt-3">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatAmount(selectedUnit.current_booking.total_amount) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Payment</span>
                                <span :class="selectedUnit.current_booking.payment_status === 'paid' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'"
                                      class="text-sm font-medium capitalize">
                                    {{ selectedUnit.current_booking.payment_status }}
                                </span>
                            </div>
                        </div>

                        <Link
                            v-if="selectedUnit.current_booking?.status === 'confirmed'"
                            :href="route('manage.bookings.show', selectedUnit.current_booking.id)"
                            class="block w-full text-center px-4 py-3 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-sm font-medium transition-all">
                            Check-in Guest →
                        </Link>

                        <Link
                            :href="route('manage.bookings.show', selectedUnit.current_booking.id)"
                            class="block w-full text-center px-4 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-sm font-medium hover:opacity-90 transition-all mt-2">
                            View Full Booking
                        </Link>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Drawer backdrop -->
        <Transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100"
                    leave-active-class="transition-opacity duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="selectedUnit" @click="selectedUnit = null"
                 class="fixed inset-0 bg-black/20 dark:bg-black/40 z-40 backdrop-blur-sm" />
        </Transition>

    </ManageLayout>
</template>
