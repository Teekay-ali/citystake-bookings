<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Calendar, Filter, Eye, X } from 'lucide-vue-next';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

const props = defineProps({
    bookings: Array,
    buildings: Array,
    filters: Object,
});

const buildingFilter = ref(props.filters.building_id || '');
const selectedEvent = ref(null);
const showEventModal = ref(false);

const calendarOptions = {
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,dayGridWeek'
    },
    events: props.bookings,
    eventClick: handleEventClick,
    height: 'auto',
    eventDisplay: 'block',
    eventTimeFormat: {
        hour: '2-digit',
        minute: '2-digit',
        meridiem: false
    },
};

watch(buildingFilter, (newValue) => {
    router.get(route('manage.bookings.calendar'), {
        building_id: newValue,
    }, {
        preserveState: true,
        replace: true,
    });
});

function handleEventClick(info) {
    selectedEvent.value = info.event;
    showEventModal.value = true;
}

function closeModal() {
    showEventModal.value = false;
    selectedEvent.value = null;
}

function formatPrice(price) {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
}

function getStatusBadge(status) {
    const badges = {
        'confirmed': 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800',
        'pending': 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800',
        'cancelled': 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800',
        'completed': 'bg-gray-50 dark:bg-gray-900/20 text-gray-700 dark:text-gray-400 border border-gray-200 dark:border-gray-800',
    };
    return badges[status] || badges['confirmed'];
}

function getPaymentBadge(status) {
    return status === 'paid'
        ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800'
        : 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800';
}
</script>

<template>
    <ManageLayout>
        <Head title="Booking Calendar - Admin" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-2">
                            Booking Calendar
                        </h1>
                        <p class="text-lg text-gray-600 dark:text-gray-400">
                            Visual overview of all bookings
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('manage.bookings.index')"
                            class="px-6 py-3 bg-gray-100 dark:bg-gray-900 hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-900 dark:text-white font-medium rounded-full transition-all"
                        >
                            View List
                        </Link>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 mb-8">
                    <div class="flex items-center gap-4">
                        <Filter class="w-5 h-5 text-gray-400" />
                        <select
                            v-model="buildingFilter"
                            class="px-4 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                        >
                            <option value="">All Properties</option>
                            <option v-for="building in buildings" :key="building.id" :value="building.id">
                                {{ building.name }}
                            </option>
                        </select>

                        <!-- Legend -->
                        <div class="flex-1 flex items-center justify-end gap-6 text-sm">
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 bg-green-500 rounded"></div>
                                <span class="text-gray-600 dark:text-gray-400">Confirmed</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 bg-yellow-500 rounded"></div>
                                <span class="text-gray-600 dark:text-gray-400">Pending Payment</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 bg-gray-500 rounded"></div>
                                <span class="text-gray-600 dark:text-gray-400">Completed</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 bg-red-500 rounded"></div>
                                <span class="text-gray-600 dark:text-gray-400">Cancelled</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-8 calendar-container">
                    <FullCalendar :options="calendarOptions" />
                </div>

                <!-- Event Details Modal -->
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="showEventModal && selectedEvent"
                        class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center"
                        @click="closeModal"
                    >
                        <!-- Backdrop -->
                        <div class="fixed inset-0 bg-gray-900/75 dark:bg-gray-950/90 backdrop-blur-sm"></div>

                        <!-- Modal -->
                        <Transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-150"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div
                                v-if="showEventModal && selectedEvent"
                                class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 p-8 max-w-lg w-full mx-4"
                                @click.stop
                            >
                                <!-- Close Button -->
                                <button
                                    @click="closeModal"
                                    class="absolute top-6 right-6 p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-all"
                                >
                                    <X class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                </button>

                                <!-- Header -->
                                <div class="mb-6">
                                    <h3 class="text-2xl font-medium text-gray-900 dark:text-white mb-2">
                                        Booking Details
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 font-mono">
                                        {{ selectedEvent.extendedProps.booking_reference }}
                                    </p>
                                </div>

                                <!-- Details -->
                                <div class="space-y-4 mb-6">
                                    <div>
                                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Guest</label>
                                        <p class="text-lg font-medium text-gray-900 dark:text-white mt-1">
                                            {{ selectedEvent.extendedProps.guest_name }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Property</label>
                                        <p class="text-sm text-gray-900 dark:text-white mt-1">
                                            {{ selectedEvent.extendedProps.property }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ selectedEvent.extendedProps.unit_type }} - Unit {{ selectedEvent.extendedProps.unit_number }}
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Check-in</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">
                                                {{ new Date(selectedEvent.start).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                                            </p>
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Check-out</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">
                                                {{ new Date(selectedEvent.end).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Guests</label>
                                            <p class="text-sm text-gray-900 dark:text-white mt-1">
                                                {{ selectedEvent.extendedProps.guests }}
                                            </p>
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</label>
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                                {{ formatPrice(selectedEvent.extendedProps.total_amount) }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex gap-4">
                                        <div>
                                            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</label>
                                            <div class="mt-2">
                                                <span :class="getStatusBadge(selectedEvent.extendedProps.status)" class="inline-flex px-3 py-1 rounded-full text-xs font-medium">
                                                    {{ selectedEvent.extendedProps.status.charAt(0).toUpperCase() + selectedEvent.extendedProps.status.slice(1) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Payment</label>
                                            <div class="mt-2">
                                                <span :class="getPaymentBadge(selectedEvent.extendedProps.payment_status)" class="inline-flex px-3 py-1 rounded-full text-xs font-medium">
                                                    {{ selectedEvent.extendedProps.payment_status.charAt(0).toUpperCase() + selectedEvent.extendedProps.payment_status.slice(1) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex gap-3">
                                    <Link
                                        :href="route('manage.bookings.show', selectedEvent.extendedProps.booking_reference)"
                                        class="flex-1 px-6 py-3 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 text-white dark:text-gray-900 font-medium rounded-xl transition-all flex items-center justify-center"
                                    >
                                        <Eye class="w-4 h-4 mr-2" />
                                        View Full Details
                                    </Link>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </Transition>
            </div>
        </div>
    </ManageLayout>
</template>

<style>
/* FullCalendar Styling */
.calendar-container :deep(.fc) {
    font-family: inherit;
}

.calendar-container :deep(.fc-theme-standard .fc-scrollgrid) {
    border-color: rgb(229 231 235 / var(--tw-border-opacity));
}

.dark .calendar-container :deep(.fc-theme-standard .fc-scrollgrid) {
    border-color: rgb(31 41 55 / var(--tw-border-opacity));
}

.calendar-container :deep(.fc-theme-standard td),
.calendar-container :deep(.fc-theme-standard th) {
    border-color: rgb(229 231 235 / var(--tw-border-opacity));
}

.dark .calendar-container :deep(.fc-theme-standard td),
.dark .calendar-container :deep(.fc-theme-standard th) {
    border-color: rgb(31 41 55 / var(--tw-border-opacity));
}

.calendar-container :deep(.fc .fc-daygrid-day-number) {
    color: rgb(17 24 39 / var(--tw-text-opacity));
}

.dark .calendar-container :deep(.fc .fc-daygrid-day-number) {
    color: rgb(255 255 255 / var(--tw-text-opacity));
}

.calendar-container :deep(.fc .fc-col-header-cell-cushion) {
    color: rgb(107 114 128 / var(--tw-text-opacity));
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
}

.dark .calendar-container :deep(.fc .fc-col-header-cell-cushion) {
    color: rgb(156 163 175 / var(--tw-text-opacity));
}

.calendar-container :deep(.fc .fc-button-primary) {
    background-color: rgb(17 24 39);
    border-color: rgb(17 24 39);
    color: white;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 0.75rem;
}

.dark .calendar-container :deep(.fc .fc-button-primary) {
    background-color: white;
    border-color: white;
    color: rgb(17 24 39);
}

.calendar-container :deep(.fc .fc-button-primary:hover) {
    background-color: rgb(31 41 55);
    border-color: rgb(31 41 55);
}

.dark .calendar-container :deep(.fc .fc-button-primary:hover) {
    background-color: rgb(243 244 246);
    border-color: rgb(243 244 246);
}

.calendar-container :deep(.fc .fc-button-primary:not(:disabled).fc-button-active) {
    background-color: rgb(17 24 39);
    border-color: rgb(17 24 39);
}

.dark .calendar-container :deep(.fc .fc-button-primary:not(:disabled).fc-button-active) {
    background-color: white;
    border-color: white;
}

.calendar-container :deep(.fc-daygrid-event) {
    padding: 2px 4px;
    margin: 1px 2px;
    border-radius: 4px;
    font-size: 0.75rem;
    cursor: pointer;
}

.calendar-container :deep(.fc-event-title) {
    font-weight: 500;
}

.calendar-container :deep(.fc-toolbar-title) {
    font-size: 1.5rem;
    font-weight: 300;
    color: rgb(17 24 39);
}

.dark .calendar-container :deep(.fc-toolbar-title) {
    color: white;
}
</style>
