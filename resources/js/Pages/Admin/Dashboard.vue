<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    TrendingUp,
    TrendingDown,
    DollarSign,
    Calendar,
    Building2,
    Users,
    CheckCircle,
    Clock,
    BarChart3,
    ArrowRight,
    AlertCircle
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    stats: Object,
    revenue: Object,
    monthlyRevenue: Array,
    revenueByProperty: Array,
    paymentBreakdown: Object,
    statusBreakdown: Object,
    recentBookings: Array,
    upcomingCheckIns: Array,
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price || 0);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

// Calculate max for chart scaling
const maxRevenue = computed(() => {
    return Math.max(...props.monthlyRevenue.map(m => m.total), 0);
});
</script>

<template>
    <AppLayout>
        <Head title="Admin Dashboard" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-8">            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                    <div>
                        <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-3">
                            Dashboard
                        </h1>
                        <p class="text-lg text-gray-600 dark:text-gray-400">
                            Overview of your property bookings and revenue
                        </p>
                    </div>

                    <!-- Quick Actions -->
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('admin.analytics.occupancy')"
                            class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-full transition-all flex items-center shadow-lg"
                        >
                            <BarChart3 class="w-5 h-5 mr-2" />
                            Occupancy Analytics
                        </Link>
                        <Link
                            :href="route('admin.bookings.calendar')"
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-full transition-all flex items-center shadow-lg"
                        >
                            <Calendar class="w-5 h-5 mr-2" />
                            Calendar View
                        </Link>
                        <Link
                            :href="route('admin.bookings.index')"
                            class="px-6 py-3 bg-gray-100 dark:bg-gray-900 hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-900 dark:text-white font-medium rounded-full transition-all flex items-center"
                        >
                            All Bookings
                        </Link>
                    </div>
                </div>

                <!-- Revenue Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                    <!-- Total Revenue -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-5 sm:p-8 rounded-2xl border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                <DollarSign class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                        <div class="text-3xl font-semibold text-gray-900 dark:text-white mb-2">
                            {{ formatPrice(revenue.total) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Total Revenue (All Time)
                        </div>
                    </div>

                    <!-- This Month Revenue -->
                    <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 p-5 sm:p-8 rounded-2xl border border-green-200 dark:border-green-800">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                <Calendar class="w-6 h-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div v-if="revenue.growth_percentage !== null" class="flex items-center gap-1 text-sm font-medium" :class="revenue.growth_percentage >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                <component :is="revenue.growth_percentage >= 0 ? TrendingUp : TrendingDown" class="w-4 h-4" />
                                {{ Math.abs(revenue.growth_percentage) }}%
                            </div>
                        </div>
                        <div class="text-3xl font-semibold text-gray-900 dark:text-white mb-2">
                            {{ formatPrice(revenue.this_month) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            This Month's Revenue
                        </div>
                    </div>

                    <!-- This Year Revenue -->
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 p-5 sm:p-8 rounded-2xl border border-purple-200 dark:border-purple-800">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                <TrendingUp class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                            </div>
                        </div>
                        <div class="text-3xl font-semibold text-gray-900 dark:text-white mb-2">
                            {{ formatPrice(revenue.this_year) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Year to Date Revenue
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl border border-gray-200 dark:border-gray-800">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                <Calendar class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                            </div>
                        </div>
                        <div class="text-2xl font-semibold text-gray-900 dark:text-white mb-1">
                            {{ stats.total_bookings }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Total Bookings
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl border border-gray-200 dark:border-gray-800">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center">
                                <CheckCircle class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                        <div class="text-2xl font-semibold text-gray-900 dark:text-white mb-1">
                            {{ stats.active_bookings }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Active Bookings
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl border border-gray-200 dark:border-gray-800">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-xl bg-green-50 dark:bg-green-900/20 flex items-center justify-center">
                                <Building2 class="w-5 h-5 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                        <div class="text-2xl font-semibold text-gray-900 dark:text-white mb-1">
                            {{ stats.total_properties }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Properties
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl border border-gray-200 dark:border-gray-800">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center">
                                <Users class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                            </div>
                        </div>
                        <div class="text-2xl font-semibold text-gray-900 dark:text-white mb-1">
                            {{ stats.total_users }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Total Users
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
                    <!-- Monthly Revenue Chart -->
                    <div class="bg-white dark:bg-gray-900 p-5 sm:p-8 rounded-2xl border border-gray-200 dark:border-gray-800">
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-6">
                            Revenue Trend (Last 6 Months)
                        </h3>
                        <div class="space-y-4">
                            <div v-for="month in monthlyRevenue" :key="month.month" class="flex items-center gap-4">
                                <div class="w-20 text-sm text-gray-600 dark:text-gray-400">
                                    {{ month.month }}
                                </div>
                                <div class="flex-1">
                                    <div class="h-8 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
                                        <div
                                            class="h-full bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg transition-all"
                                            :style="{ width: maxRevenue > 0 ? (month.total / maxRevenue * 100) + '%' : '0%' }"
                                        ></div>
                                    </div>
                                </div>
                                <div class="w-32 text-right text-sm font-medium text-gray-900 dark:text-white">
                                    {{ formatPrice(month.total) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue by Property -->
                    <div class="bg-white dark:bg-gray-900 p-5 sm:p-8 rounded-2xl border border-gray-200 dark:border-gray-800">
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-6">
                            Top Properties by Revenue
                        </h3>
                        <div class="space-y-4">
                            <div v-for="(property, index) in revenueByProperty" :key="index" class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-sm font-medium text-gray-600 dark:text-gray-400">
                                        {{ index + 1 }}
                                    </div>
                                    <span class="text-sm text-gray-900 dark:text-white">
                                        {{ property.property }}
                                    </span>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ formatPrice(property.total) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment & Status Breakdown -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
                    <!-- Payment Status -->
                    <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl border border-gray-200 dark:border-gray-800">
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-6">
                            Payment Status
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <CheckCircle class="w-5 h-5 text-green-600 dark:text-green-400" />
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Paid</span>
                                </div>
                                <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ paymentBreakdown.paid }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <Clock class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Pending</span>
                                </div>
                                <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ paymentBreakdown.pending }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Status -->
                    <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl border border-gray-200 dark:border-gray-800">
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-6">
                            Booking Status
                        </h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl text-center">
                                <div class="text-2xl font-semibold text-gray-900 dark:text-white mb-1">
                                    {{ statusBreakdown.confirmed }}
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Confirmed</div>
                            </div>
                            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl text-center">
                                <div class="text-2xl font-semibold text-gray-900 dark:text-white mb-1">
                                    {{ statusBreakdown.pending }}
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Pending</div>
                            </div>
                            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl text-center">
                                <div class="text-2xl font-semibold text-gray-900 dark:text-white mb-1">
                                    {{ statusBreakdown.completed }}
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Completed</div>
                            </div>
                            <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-xl text-center">
                                <div class="text-2xl font-semibold text-gray-900 dark:text-white mb-1">
                                    {{ statusBreakdown.cancelled }}
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Cancelled</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Bookings & Upcoming Check-ins -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Recent Bookings -->
                    <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl border border-gray-200 dark:border-gray-800">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-medium text-gray-900 dark:text-white">
                                Recent Bookings
                            </h3>
                            <Link
                                :href="route('admin.bookings.index')"
                                class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white flex items-center gap-1"
                            >
                                View all
                                <ArrowRight class="w-4 h-4" />
                            </Link>
                        </div>
                        <div class="space-y-4">
                            <Link
                                v-for="booking in recentBookings"
                                :key="booking.id"
                                :href="route('admin.bookings.show', booking.id)"
                                class="block p-4 bg-gray-50 dark:bg-gray-950 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors"
                            >
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ booking.guest_name }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ booking.building?.name }} - {{ booking.unit_type?.name }}
                                        </p>
                                    </div>
                                    <span class="text-xs font-medium text-gray-900 dark:text-white">
                                        {{ formatPrice(booking.total_amount) }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatDate(booking.check_in) }} - {{ formatDate(booking.check_out) }}
                                </p>
                            </Link>
                        </div>
                    </div>

                    <!-- Upcoming Check-ins -->
                    <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl border border-gray-200 dark:border-gray-800">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-medium text-gray-900 dark:text-white">
                                Upcoming Check-ins
                            </h3>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                Next 7 days
                            </span>
                        </div>
                        <div class="space-y-4">
                            <div
                                v-for="booking in upcomingCheckIns"
                                :key="booking.id"
                                class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800"
                            >
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ booking.guest_name }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ booking.building?.name }} - {{ booking.unit_type?.name }}
                                        </p>
                                    </div>
                                    <span class="text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-full">
                                        {{ booking.nights }} nights
                                    </span>
                                </div>
                                <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">
                                    Check-in: {{ formatDate(booking.check_in) }}
                                </p>
                            </div>

                            <div v-if="upcomingCheckIns.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400 text-sm">
                                No upcoming check-ins in the next 7 days
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
