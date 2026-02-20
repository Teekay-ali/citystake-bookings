<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    Calendar,
    DollarSign,
    Home,
    Users,
    TrendingUp,
    Clock,
    CheckCircle,
    AlertCircle,
    ChevronRight
} from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    recentBookings: Array,
    upcomingCheckIns: Array,
    revenueData: Array,
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
            text: 'Cancelled',
            class: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800'
        };
    } else if (booking.payment_status === 'pending') {
        return {
            text: 'Payment Pending',
            class: 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800'
        };
    } else {
        return {
            text: 'Confirmed',
            class: 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800'
        };
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Admin Dashboard" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-12">
                    <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-2">
                        Admin Dashboard
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        Welcome back! Here's what's happening with your properties.
                    </p>
                </div>

                <!-- Quick Navigation -->
                <div class="flex gap-3 mb-8">
                    <Link
                        :href="route('admin.dashboard')"
                        class="px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-full text-sm font-medium"
                    >
                        Dashboard
                    </Link>
                    <Link
                        :href="route('admin.bookings.index')"
                        class="px-4 py-2 bg-gray-100 dark:bg-gray-900 hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-900 dark:text-white rounded-full text-sm font-medium transition-all"
                    >
                        All Bookings
                    </Link>
                    <Link
                        :href="route('admin.properties.index')"
                        class="px-4 py-2 bg-gray-100 dark:bg-gray-900 hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-900 dark:text-white rounded-full text-sm font-medium transition-all"
                    >
                        Properties
                    </Link>
                    <Link
                        :href="route('admin.blocked-dates.index')"
                        class="px-4 py-2 bg-gray-100 dark:bg-gray-900 hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-900 dark:text-white rounded-full text-sm font-medium transition-all"
                    >
                        Blocked Dates
                    </Link>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                    <!-- Total Revenue -->
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
                                <DollarSign class="w-6 h-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Revenue</p>
                        <p class="text-2xl font-light text-gray-900 dark:text-white">
                            {{ formatPrice(stats.total_revenue) }}
                        </p>
                        <p class="text-xs text-green-600 dark:text-green-400 mt-2">
                            {{ formatPrice(stats.monthly_revenue) }} this month
                        </p>
                    </div>

                    <!-- Active Bookings -->
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                                <Calendar class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Active Stays</p>
                        <p class="text-2xl font-light text-gray-900 dark:text-white">
                            {{ stats.active_bookings }}
                        </p>
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">
                            {{ stats.upcoming_bookings }} upcoming
                        </p>
                    </div>

                    <!-- Total Bookings -->
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border border-purple-200 dark:border-purple-800 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                                <CheckCircle class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Bookings</p>
                        <p class="text-2xl font-light text-gray-900 dark:text-white">
                            {{ stats.total_bookings }}
                        </p>
                        <p class="text-xs text-purple-600 dark:text-purple-400 mt-2">
                            All time
                        </p>
                    </div>

                    <!-- Properties -->
                    <div class="bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 border border-orange-200 dark:border-orange-800 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-orange-100 dark:bg-orange-900/50 flex items-center justify-center">
                                <Home class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Properties</p>
                        <p class="text-2xl font-light text-gray-900 dark:text-white">
                            {{ stats.total_properties }}
                        </p>
                        <p class="text-xs text-orange-600 dark:text-orange-400 mt-2">
                            {{ stats.total_unit_types }} unit types
                        </p>
                    </div>


                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Recent Bookings -->
                    <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-medium text-gray-900 dark:text-white">
                                Recent Bookings
                            </h2>
                            <Link
                                :href="route('admin.bookings.index')"
                                class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white flex items-center"
                            >
                                View all
                                <ChevronRight class="w-4 h-4 ml-1" />
                            </Link>
                        </div>

                        <div class="space-y-4">
                            <div
                                v-for="booking in recentBookings"
                                :key="booking.id"
                                class="flex items-start justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-xl"
                            >
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 dark:text-white mb-1">
                                        {{ booking.guest_name }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                        {{ booking.unit_type.name }} • {{ booking.building.name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500">
                                        {{ formatDate(booking.check_in) }} - {{ formatDate(booking.check_out) }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium text-gray-900 dark:text-white mb-2">
                                        {{ formatPrice(booking.total_amount) }}
                                    </p>
                                    <span :class="getStatusBadge(booking).class" class="text-xs px-2 py-1 rounded-full">
                                        {{ getStatusBadge(booking).text }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Check-ins -->
                    <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-medium text-gray-900 dark:text-white flex items-center">
                                <Clock class="w-5 h-5 mr-2" />
                                Upcoming Check-ins
                            </h2>
                        </div>

                        <div v-if="upcomingCheckIns.length > 0" class="space-y-4">
                            <div
                                v-for="booking in upcomingCheckIns"
                                :key="booking.id"
                                class="flex items-start justify-between p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-900"
                            >
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 dark:text-white mb-1">
                                        {{ booking.guest_name }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                        {{ booking.unit_type.name }} • Unit {{ booking.unit.unit_number }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500">
                                        {{ booking.guests }} guest{{ booking.guests > 1 ? 's' : '' }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-blue-600 dark:text-blue-400 mb-1">
                                        {{ formatDate(booking.check_in) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500">
                                        {{ booking.nights }} night{{ booking.nights > 1 ? 's' : '' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-12">
                            <Calendar class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                No upcoming check-ins in the next 7 days
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
