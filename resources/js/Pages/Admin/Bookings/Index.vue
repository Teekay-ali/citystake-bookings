<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import {
    Search,
    Filter,
    Calendar,
    MapPin,
    Users,
    DollarSign,
    ChevronRight, ChevronLeft,
    Plus,
    Download,
    FileSpreadsheet,
    Eye,
    CheckCircle,
    XCircle,
    Clock,
    AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
    bookings: Object,
    buildings: Array,
    filters: Object,
});

// Filter states
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const building = ref(props.filters.building || '');
const paymentStatus = ref(props.filters.payment_status || '');
const sortBy = ref(props.filters.sort_by || 'created_at');
const sortOrder = ref(props.filters.sort_order || 'desc');

// Debounce timer
let searchTimeout = null;

// Watch search with debounce
watch(search, (newValue) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500); // 500ms debounce
});

// Watch other filters without debounce
watch([status, building, paymentStatus, sortBy, sortOrder], () => {
    applyFilters();
});

const applyFilters = () => {
    router.get(route('manage.bookings.index'), {
        search: search.value,
        status: status.value,
        building: building.value,
        payment_status: paymentStatus.value,
        sort_by: sortBy.value,
        sort_order: sortOrder.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const getStatusBadge = (booking) => {
    const map = {
        cancelled:       { icon: XCircle,      text: 'Cancelled',        class: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800' },
        payment_pending: { icon: AlertCircle,   text: 'Payment Pending',  class: 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800' },
        active:          { icon: Clock,         text: 'Active Stay',      class: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800' },
        checked_in:      { icon: Clock,         text: 'Checked In',       class: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800' },
        completed:       { icon: CheckCircle,   text: 'Completed',        class: 'bg-gray-50 dark:bg-gray-900/20 text-gray-700 dark:text-gray-400 border border-gray-200 dark:border-gray-800' },
        confirmed:       { icon: CheckCircle,   text: 'Confirmed',        class: 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800' },
    };
    return map[booking.display_status] ?? map['confirmed'];
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    building.value = '';
    paymentStatus.value = '';
};

// Add export function
const exportBookings = () => {
    const params = new URLSearchParams();

    if (status.value) params.append('status', status.value);
    if (paymentStatus.value) params.append('payment_status', paymentStatus.value);
    if (building.value) params.append('building_id', building.value);
    if (search.value) params.append('search', search.value);

    const url = route('manage.bookings.export') + (params.toString() ? '?' + params.toString() : '');
    window.location.href = url;
};

</script>

<template>
    <ManageLayout>
        <Head title="All Bookings - Manage Bookingss" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-8">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-2">
                            All Bookings
                        </h1>
                        <p class="text-lg text-gray-600 dark:text-gray-400">
                            Manage and track all property bookings
                        </p>
                    </div>

                    <!-- Export Button -->
                    <div class="flex items-center gap-3">
                        <button
                            @click="exportBookings"
                            class="px-6 py-3 bg-green-600 dark:bg-green-500 hover:bg-green-700 dark:hover:bg-green-600 text-white font-medium rounded-full transition-all flex items-center shadow-lg"
                        >
                            <Download class="w-5 h-5 mr-2" />
                            Export to Excel
                        </button>

                        <Link
                            :href="route('manage.bookings.create')"
                            class="px-6 py-3 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 text-white dark:text-gray-900 font-medium rounded-full transition-all flex items-center shadow-lg"
                        >
                            <Plus class="w-5 h-5 mr-2" />
                            Create Booking
                        </Link>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- Search -->
                        <div class="col-span-2 lg:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Search
                            </label>
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Reference, name, or email..."
                                    class="w-full pl-10 pr-4 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                                />
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Status
                            </label>
                            <select
                                v-model="status"
                                class="w-full px-4 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                            >
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="upcoming">Upcoming</option>
                                <option value="past">Past</option>
                                <option value="pending">Pending</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <!-- Building Filter -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Property
                            </label>
                            <select
                                v-model="building"
                                class="w-full px-4 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                            >
                                <option value="">All Properties</option>
                                <option v-for="b in buildings" :key="b.id" :value="b.id">
                                    {{ b.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Payment Status -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Payment
                            </label>
                            <select
                                v-model="paymentStatus"
                                class="w-full px-4 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                            >
                                <option value="">All Payments</option>
                                <option value="paid">Paid</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-between items-center">
                        <button
                            @click="clearFilters"
                            class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                        >
                            Clear filters
                        </button>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Showing {{ bookings.data.length }} of {{ bookings.total }} bookings
                        </p>
                    </div>
                </div>

                <!-- Bookings Table -->
                <div class="border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Booking
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Guest
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Property
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Dates
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Amount
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-950 divide-y divide-gray-200 dark:divide-gray-800">
                            <tr
                                v-for="booking in bookings.data"
                                :key="booking.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white font-mono">
                                            {{ booking.booking_reference }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ formatDate(booking.created_at) }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ booking.guest_name }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ booking.guests }} guest{{ booking.guests > 1 ? 's' : '' }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="text-sm text-gray-900 dark:text-white">
                                            {{ booking.unit_type.name }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ booking.building.name }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <p class="text-sm text-gray-900 dark:text-white">
                                            {{ formatDate(booking.check_in) }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ booking.nights }} night{{ booking.nights > 1 ? 's' : '' }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ formatPrice(booking.total_amount) }}
                                    </p>
                                    <p class="text-xs" :class="booking.payment_status === 'paid' ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400'">
                                        {{ booking.payment_status === 'paid' ? 'Paid' : 'Pending' }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getStatusBadge(booking).class" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium">
                                            <component :is="getStatusBadge(booking).icon" class="w-3 h-3 mr-1" />
                                            {{ getStatusBadge(booking).text }}
                                        </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <Link
                                        :href="route('manage.bookings.show', booking.id)"
                                        class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                                    >
                                        <Eye class="w-4 h-4 mr-1" />
                                        View
                                    </Link>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Mobile card list -->
                    <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-800">
                        <Link
                            v-for="booking in bookings.data"
                            :key="booking.id"
                            :href="route('manage.bookings.show', booking.id)"
                            class="flex items-start justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors"
                        >
                            <div class="space-y-1 min-w-0 flex-1 pr-3">
                                <p class="text-sm font-mono font-medium text-gray-900 dark:text-white truncate">
                                    {{ booking.booking_reference }}
                                </p>
                                <p class="text-sm text-gray-900 dark:text-white">{{ booking.guest_name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ booking.building?.name }} · {{ booking.unit_type?.name }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatDate(booking.check_in) }} → {{ formatDate(booking.check_out) }}
                                </p>
                            </div>
                            <div class="flex flex-col items-end gap-2 shrink-0">
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ formatPrice(booking.total_amount) }}
                </span>
                                <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium border', getStatusBadge(booking).class]">
                    {{ getStatusBadge(booking).text }}
                </span>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                <!-- Pagination -->
                <div v-if="bookings.last_page > 1" class="flex justify-center items-center gap-1 mt-8">
                    <!-- Prev -->
                    <button @click="bookings.prev_page_url && router.visit(bookings.prev_page_url)"
                            :disabled="!bookings.prev_page_url"
                            class="w-9 h-9 flex items-center justify-center rounded-full border border-gray-200 dark:border-gray-800 text-gray-500 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-900 hover:text-gray-900 dark:hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition-all">
                        <ChevronLeft class="w-4 h-4" />
                    </button>

                    <!-- Page numbers -->
                    <template v-for="link in bookings.links.slice(1, -1)" :key="link.label">
                        <button v-if="link.label !== '...'"
                                @click="link.url && router.visit(link.url)"
                                :disabled="!link.url"
                                :class="[
                    'w-9 h-9 flex items-center justify-center rounded-full text-sm font-medium transition-all',
                    link.active
                        ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                        : 'border border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-900 hover:text-gray-900 dark:hover:text-white'
                ]">
                            {{ link.label }}
                        </button>
                        <span v-else class="w-9 h-9 flex items-center justify-center text-sm text-gray-400">…</span>
                    </template>

                    <!-- Next -->
                    <button @click="bookings.next_page_url && router.visit(bookings.next_page_url)"
                            :disabled="!bookings.next_page_url"
                            class="w-9 h-9 flex items-center justify-center rounded-full border border-gray-200 dark:border-gray-800 text-gray-500 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-900 hover:text-gray-900 dark:hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition-all">
                        <ChevronRight class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>
    </ManageLayout>
</template>
