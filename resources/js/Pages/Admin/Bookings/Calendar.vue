<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import {
    Eye, X, CalendarDays, Users, CheckCircle2,
    TrendingUp, LogIn, LogOut, AlertCircle, Building2
} from 'lucide-vue-next'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    bookings:  Array,
    buildings: Array,
    filters:   Object,
})

const buildingFilter = ref(props.filters.building_id || '')
const selectedEvent  = ref(null)
const showEventModal = ref(false)

// ── Derived stats ─────────────────────────────────────────────
const today = new Date()
today.setHours(0, 0, 0, 0)
const todayStr = today.toISOString().split('T')[0]

const stats = computed(() => {
    const all       = props.bookings
    const confirmed = all.filter(b => b.extendedProps.status === 'confirmed')
    const pending   = all.filter(b => b.extendedProps.payment_status === 'pending')
    const now       = new Date()
    const monthStart = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0]
    const monthEnd   = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0]
    const thisMonth  = all.filter(b => b.start >= monthStart && b.start <= monthEnd)
    const revenue    = confirmed.reduce((sum, b) => sum + Number(b.extendedProps.total_amount || 0), 0)
    return {
        total: all.length, confirmed: confirmed.length,
        pending: pending.length, thisMonth: thisMonth.length, revenue,
    }
})

const todayArrivals = computed(() =>
    props.bookings.filter(b => b.start === todayStr && b.extendedProps.status === 'confirmed')
)

const todayDepartures = computed(() =>
    props.bookings.filter(b => {
        if (!b.end) return false
        const endDate = new Date(b.end)
        endDate.setDate(endDate.getDate() - 1)
        return endDate.toISOString().split('T')[0] === todayStr
            && b.extendedProps.status === 'confirmed'
    })
)

// ── Calendar config ───────────────────────────────────────────
const calendarOptions = {
    plugins:      [dayGridPlugin, interactionPlugin],
    initialView:  'dayGridMonth',
    headerToolbar: {
        left:   'prev,next today',
        center: 'title',
        right:  'dayGridMonth,dayGridWeek',
    },
    events:          props.bookings,
    eventClick:      handleEventClick,
    height:          'auto',
    eventDisplay:    'block',
    eventTimeFormat: { hour: '2-digit', minute: '2-digit', meridiem: false },
    dayMaxEvents:    3,
}

watch(buildingFilter, (val) => {
    router.get(route('manage.bookings.calendar'), { building_id: val }, {
        preserveState: true, replace: true,
    })
})

function handleEventClick(info) {
    selectedEvent.value  = info.event
    showEventModal.value = true
}

function closeModal() {
    showEventModal.value = false
    selectedEvent.value  = null
}

// ── Helpers ───────────────────────────────────────────────────
function formatPrice(n) {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency', currency: 'NGN',
        minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(n)
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric',
    })
}

function getStatusBadge(status) {
    const map = {
        confirmed: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800',
        pending:   'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800',
        cancelled: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800',
        completed: 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700',
    }
    return map[status] ?? map.confirmed
}

function getPaymentBadge(status) {
    return status === 'paid'
        ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800'
        : 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800'
}

function capitalise(s) {
    return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''
}

const canCheckIn = computed(() => {
    if (!selectedEvent.value) return false
    return selectedEvent.value.startStr === todayStr
        && selectedEvent.value.extendedProps.status === 'confirmed'
})
</script>

<template>
    <Head title="Booking Calendar" />

    <div class="p-6 lg:p-8">

        <!-- ── Header ── -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Booking Calendar</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Visual overview of all bookings</p>
            </div>
            <Link :href="route('manage.bookings.index')"
                  class="inline-flex items-center gap-2 px-3 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                View List
            </Link>
        </div>

        <!-- ── Stat cards ── -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-medium text-emerald-500 uppercase tracking-wider">Confirmed</p>
                    <CheckCircle2 class="w-3.5 h-3.5 text-emerald-500" />
                </div>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.confirmed }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">active bookings</p>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-medium text-amber-500 uppercase tracking-wider">Pending</p>
                    <AlertCircle class="w-3.5 h-3.5 text-amber-500" />
                </div>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.pending }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">awaiting payment</p>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">This Month</p>
                    <CalendarDays class="w-3.5 h-3.5 text-gray-400" />
                </div>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.thisMonth }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">bookings</p>
            </div>

            <div class="bg-gray-900 dark:bg-white border border-gray-800 dark:border-gray-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Revenue</p>
                    <TrendingUp class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" />
                </div>
                <p class="text-lg font-semibold text-white dark:text-gray-900 leading-tight">
                    {{ formatPrice(stats.revenue) }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">confirmed total</p>
            </div>
        </div>

        <!-- ── Today snapshot + filter row ── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-6">

            <!-- Today's arrivals -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <div class="flex items-center gap-2 mb-3">
                    <LogIn class="w-4 h-4 text-emerald-500" />
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Today's Arrivals</span>
                    <span class="ml-auto text-xs font-semibold bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 px-2 py-0.5 rounded-md">
                        {{ todayArrivals.length }}
                    </span>
                </div>
                <div v-if="todayArrivals.length" class="space-y-2">
                    <div v-for="b in todayArrivals.slice(0, 3)" :key="b.id"
                         class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-[60%]">
                            {{ b.extendedProps.guest_name }}
                        </span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            Unit {{ b.extendedProps.unit_number }}
                        </span>
                    </div>
                    <p v-if="todayArrivals.length > 3" class="text-xs text-gray-400 dark:text-gray-500">
                        +{{ todayArrivals.length - 3 }} more
                    </p>
                </div>
                <p v-else class="text-sm text-gray-400 dark:text-gray-500">No arrivals today</p>
            </div>

            <!-- Today's departures -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <div class="flex items-center gap-2 mb-3">
                    <LogOut class="w-4 h-4 text-gray-400" />
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Today's Departures</span>
                    <span class="ml-auto text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 px-2 py-0.5 rounded-md">
                        {{ todayDepartures.length }}
                    </span>
                </div>
                <div v-if="todayDepartures.length" class="space-y-2">
                    <div v-for="b in todayDepartures.slice(0, 3)" :key="b.id"
                         class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-[60%]">
                            {{ b.extendedProps.guest_name }}
                        </span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            Unit {{ b.extendedProps.unit_number }}
                        </span>
                    </div>
                    <p v-if="todayDepartures.length > 3" class="text-xs text-gray-400 dark:text-gray-500">
                        +{{ todayDepartures.length - 3 }} more
                    </p>
                </div>
                <p v-else class="text-sm text-gray-400 dark:text-gray-500">No departures today</p>
            </div>

            <!-- Filter + legend -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Filter by property</p>
                <select v-model="buildingFilter"
                        class="w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white mb-4 transition-all">
                    <option value="">All Properties</option>
                    <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <div class="grid grid-cols-2 gap-1.5">
                    <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                        <span class="w-2.5 h-2.5 rounded-sm bg-emerald-500 shrink-0"></span> Confirmed
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                        <span class="w-2.5 h-2.5 rounded-sm bg-amber-500 shrink-0"></span> Pending
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                        <span class="w-2.5 h-2.5 rounded-sm bg-gray-400 shrink-0"></span> Completed
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                        <span class="w-2.5 h-2.5 rounded-sm bg-red-500 shrink-0"></span> Cancelled
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Calendar ── -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 calendar-container">
            <FullCalendar :options="calendarOptions" />
        </div>

    </div>

    <!-- ── Event detail modal ── -->
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0">
        <div v-if="showEventModal && selectedEvent"
             class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center p-4"
             @click.self="closeModal">

            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 translate-y-4 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0 translate-y-4 sm:scale-95">
                <div class="bg-white dark:bg-gray-900 rounded-xl w-full max-w-md overflow-hidden">

                    <!-- Modal header -->
                    <div class="px-5 pt-5 pb-4 border-b border-gray-100 dark:border-gray-800 flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1.5">
                                <span :class="getStatusBadge(selectedEvent.extendedProps.status)"
                                      class="text-xs font-medium px-2 py-0.5 rounded-lg">
                                    {{ capitalise(selectedEvent.extendedProps.status) }}
                                </span>
                                <span :class="getPaymentBadge(selectedEvent.extendedProps.payment_status)"
                                      class="text-xs font-medium px-2 py-0.5 rounded-lg">
                                    {{ capitalise(selectedEvent.extendedProps.payment_status) }}
                                </span>
                            </div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                                {{ selectedEvent.extendedProps.guest_name }}
                            </h3>
                            <p class="text-xs text-gray-400 dark:text-gray-500 font-mono mt-0.5">
                                {{ selectedEvent.extendedProps.booking_reference }}
                            </p>
                        </div>
                        <button @click="closeModal"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all shrink-0">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Modal body -->
                    <div class="px-5 py-4 space-y-3">

                        <!-- Property info -->
                        <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                            <Building2 class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" />
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ selectedEvent.extendedProps.property }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ selectedEvent.extendedProps.unit_type }} · Unit {{ selectedEvent.extendedProps.unit_number }}
                                </p>
                            </div>
                        </div>

                        <!-- Dates + guests -->
                        <div class="grid grid-cols-3 gap-2">
                            <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Check-in</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white leading-tight">
                                    {{ formatDate(selectedEvent.start) }}
                                </p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Check-out</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white leading-tight">
                                    {{ (() => { const d = new Date(selectedEvent.end); d.setDate(d.getDate() - 1); return formatDate(d) })() }}
                                </p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Guests</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white flex items-center gap-1">
                                    <Users class="w-3.5 h-3.5 text-gray-400" />
                                    {{ selectedEvent.extendedProps.guests }}
                                </p>
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                            <span class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wider">Total Amount</span>
                            <span class="text-base font-semibold text-gray-900 dark:text-white">
                                {{ formatPrice(selectedEvent.extendedProps.total_amount) }}
                            </span>
                        </div>
                    </div>

                    <!-- Modal footer -->
                    <div class="px-5 pb-5 flex gap-2">
                        <Link v-if="canCheckIn"
                              :href="route('manage.bookings.check-in', selectedEvent.id)"
                              method="post"
                              as="button"
                              class="flex-1 px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 transition-all flex items-center justify-center gap-2">
                            <CheckCircle2 class="w-4 h-4" />
                            Check In
                        </Link>
                        <Link :href="route('manage.bookings.show', selectedEvent.id)"
                              class="flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all flex items-center justify-center gap-2">
                            <Eye class="w-4 h-4" />
                            View Details
                        </Link>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<style>
.calendar-container :deep(.fc) { font-family: inherit; }

.calendar-container :deep(.fc-theme-standard .fc-scrollgrid),
.calendar-container :deep(.fc-theme-standard td),
.calendar-container :deep(.fc-theme-standard th) {
    border-color: rgb(229 231 235);
}
.dark .calendar-container :deep(.fc-theme-standard .fc-scrollgrid),
.dark .calendar-container :deep(.fc-theme-standard td),
.dark .calendar-container :deep(.fc-theme-standard th) {
    border-color: rgb(31 41 55);
}

.calendar-container :deep(.fc-col-header-cell-cushion),
.calendar-container :deep(.fc-daygrid-day-number) {
    color: rgb(107 114 128);
    text-decoration: none;
    font-size: 0.8rem;
}
.dark .calendar-container :deep(.fc-col-header-cell-cushion),
.dark .calendar-container :deep(.fc-daygrid-day-number) {
    color: rgb(156 163 175);
}

.calendar-container :deep(.fc-daygrid-day.fc-day-today) {
    background-color: rgb(249 250 251);
}
.dark .calendar-container :deep(.fc-daygrid-day.fc-day-today) {
    background-color: rgb(17 24 39);
}

.calendar-container :deep(.fc-button-primary) {
    background-color: rgb(17 24 39) !important;
    border-color: rgb(17 24 39) !important;
    font-size: 0.8rem;
    padding: 0.3rem 0.75rem;
    border-radius: 0.5rem !important;
}
.dark .calendar-container :deep(.fc-button-primary) {
    background-color: rgb(255 255 255) !important;
    border-color: rgb(255 255 255) !important;
    color: rgb(17 24 39) !important;
}

.calendar-container :deep(.fc-button-primary:disabled) { opacity: 0.4 !important; }

.calendar-container :deep(.fc-toolbar-title) {
    font-size: 1rem !important;
    font-weight: 600;
    color: rgb(17 24 39);
}
.dark .calendar-container :deep(.fc-toolbar-title) { color: rgb(255 255 255); }

.calendar-container :deep(.fc-event) {
    border-radius: 4px !important;
    font-size: 0.7rem !important;
    padding: 1px 4px !important;
    cursor: pointer;
}

.calendar-container :deep(.fc-daygrid-more-link) {
    font-size: 0.7rem;
    color: rgb(107 114 128);
}
</style>
