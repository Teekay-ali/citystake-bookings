<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    Calendar,
    Download,
    MapPin,
    Users,
    Clock,
    CheckCircle,
    XCircle,
    AlertCircle,
    Plus,
    ChevronRight,
    Filter
} from 'lucide-vue-next';

const props = defineProps({
    bookings: Object,
});

const activeTab = ref('upcoming');

const tabs = [
    { key: 'upcoming', label: 'Upcoming', count: props.bookings.upcoming.length },
    { key: 'active', label: 'Active', count: props.bookings.active.length },
    { key: 'past', label: 'Past', count: props.bookings.past.length },
    { key: 'cancelled', label: 'Cancelled', count: props.bookings.cancelled.length },
];

const currentBookings = computed(() => {
    return props.bookings[activeTab.value] || [];
});

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
    if (booking.status === 'cancelled') {
        return {
            icon: XCircle,
            text: 'Cancelled',
            class: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800'
        };
    } else if (booking.payment_status === 'pending') {
        return {
            icon: AlertCircle,
            text: 'Payment Pending',
            class: 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800'
        };
    } else if (booking.check_out < new Date().toISOString()) {
        return {
            icon: CheckCircle,
            text: 'Completed',
            class: 'bg-gray-50 dark:bg-gray-900/20 text-gray-700 dark:text-gray-400 border border-gray-200 dark:border-gray-800'
        };
    } else if (booking.check_in <= new Date().toISOString() && booking.check_out >= new Date().toISOString()) {
        return {
            icon: Clock,
            text: 'Active',
            class: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800'
        };
    } else {
        return {
            icon: CheckCircle,
            text: 'Confirmed',
            class: 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800'
        };
    }
};
</script>

<template>
    <AppLayout>
        <Head title="My Bookings" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
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

                    <!-- Buttons -->
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('admin.bookings.calendar')"
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-full transition-all flex items-center shadow-lg"
                        >
                            <Calendar class="w-5 h-5 mr-2" />
                            Calendar View
                        </Link>

                        <button
                            @click="exportBookings"
                            class="px-6 py-3 bg-green-600 dark:bg-green-500 hover:bg-green-700 dark:hover:bg-green-600 text-white font-medium rounded-full transition-all flex items-center shadow-lg"
                        >
                            <Download class="w-5 h-5 mr-2" />
                            Export
                        </button>

                        <Link
                            :href="route('admin.bookings.create')"
                            class="px-6 py-3 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 text-white dark:text-gray-900 font-medium rounded-full transition-all flex items-center shadow-lg"
                        >
                            <Plus class="w-5 h-5 mr-2" />
                            Create Booking
                        </Link>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="mb-8 border-b border-gray-200 dark:border-gray-800">
                    <div class="flex space-x-8 overflow-x-auto">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            @click="activeTab = tab.key"
                            class="pb-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition-colors"
                            :class="activeTab === tab.key
                                ? 'border-gray-900 dark:border-white text-gray-900 dark:text-white'
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                        >
                            {{ tab.label }}
                            <span class="ml-2 py-0.5 px-2 rounded-full text-xs"
                                  :class="activeTab === tab.key
                                    ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                                    : 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400'">
                                {{ tab.count }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Bookings List -->
                <div v-if="currentBookings.length > 0" class="space-y-6">
                    <Link
                        v-for="booking in currentBookings"
                        :key="booking.id"
                        :href="route('bookings.show', booking.id)"
                        class="block border border-gray-200 dark:border-gray-800 rounded-2xl p-6 hover:border-gray-300 dark:hover:border-gray-700 transition-all group"
                    >
                        <div class="flex flex-col md:flex-row gap-6">
                            <!-- Image -->
                            <div class="md:w-48 h-48 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 flex-shrink-0">
                                <img
                                    v-if="booking.unit_type.images && booking.unit_type.images[0]"
                                    :src="booking.unit_type.images[0].image_path"
                                    :alt="booking.unit_type.name"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                />
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-1 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors">
                                            {{ booking.unit_type.name }}
                                        </h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                            <MapPin class="w-4 h-4 mr-1" />
                                            {{ booking.building.name }} • {{ booking.building.address }}
                                        </p>
                                    </div>

                                    <!-- Status Badge -->
                                    <div :class="getStatusBadge(booking).class" class="flex items-center px-3 py-1.5 rounded-full text-xs font-medium">
                                        <component :is="getStatusBadge(booking).icon" class="w-3.5 h-3.5 mr-1.5" />
                                        {{ getStatusBadge(booking).text }}
                                    </div>
                                </div>

                                <!-- Details Grid -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex items-center">
                                            <Calendar class="w-3 h-3 mr-1" />
                                            Check-in
                                        </p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ formatDate(booking.check_in) }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex items-center">
                                            <Calendar class="w-3 h-3 mr-1" />
                                            Check-out
                                        </p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ formatDate(booking.check_out) }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex items-center">
                                            <Users class="w-3 h-3 mr-1" />
                                            Guests
                                        </p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ booking.guests }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                            Total
                                        </p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ formatPrice(booking.total_amount) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-900">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Booking Ref: <span class="font-mono">{{ booking.booking_reference }}</span>
                                    </p>
                                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">
                                        View details
                                        <ChevronRight class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-16">
                    <div class="w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-6">
                        <Calendar class="w-10 h-10 text-gray-400" />
                    </div>
                    <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">
                        No {{ activeTab }} bookings
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-8">
                        {{ activeTab === 'upcoming' ? 'Start planning your next stay!' : 'You don\'t have any ' + activeTab + ' bookings.' }}
                    </p>
                    <Link
                        v-if="activeTab === 'upcoming'"
                        :href="route('properties.index')"
                        class="inline-flex items-center px-6 py-3 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 text-white dark:text-gray-900 font-medium rounded-full transition-all"
                    >
                        Browse Properties
                        <ChevronRight class="w-4 h-4 ml-2" />
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
