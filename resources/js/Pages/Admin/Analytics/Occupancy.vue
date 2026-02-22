<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import {
    TrendingUp,
    TrendingDown,
    Building2,
    Calendar,
    DollarSign,
    BarChart3,
    Filter
} from 'lucide-vue-next';

const props = defineProps({
    overallOccupancy: Object,
    occupancyByProperty: Array,
    monthlyTrend: Array,
    topPerformers: Array,
    revenuePerNight: Object,
    buildings: Array,
    filters: Object,
});

const selectedYear = ref(props.filters.year);
const selectedMonth = ref(props.filters.month);
const selectedBuilding = ref(props.filters.building_id || '');

const months = [
    { value: 1, label: 'January' },
    { value: 2, label: 'February' },
    { value: 3, label: 'March' },
    { value: 4, label: 'April' },
    { value: 5, label: 'May' },
    { value: 6, label: 'June' },
    { value: 7, label: 'July' },
    { value: 8, label: 'August' },
    { value: 9, label: 'September' },
    { value: 10, label: 'October' },
    { value: 11, label: 'November' },
    { value: 12, label: 'December' },
];

const years = computed(() => {
    const currentYear = new Date().getFullYear();
    return Array.from({ length: 3 }, (_, i) => currentYear - i);
});

watch([selectedYear, selectedMonth, selectedBuilding], () => {
    applyFilters();
});

const applyFilters = () => {
    router.get(route('admin.analytics.occupancy'), {
        year: selectedYear.value,
        month: selectedMonth.value,
        building_id: selectedBuilding.value,
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

const getOccupancyColor = (rate) => {
    if (rate >= 80) return 'text-green-600 dark:text-green-400';
    if (rate >= 60) return 'text-blue-600 dark:text-blue-400';
    if (rate >= 40) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};

const getOccupancyBgColor = (rate) => {
    if (rate >= 80) return 'bg-green-500';
    if (rate >= 60) return 'bg-blue-500';
    if (rate >= 40) return 'bg-yellow-500';
    return 'bg-red-500';
};

const maxTrendRate = computed(() => {
    return Math.max(...props.monthlyTrend.map(t => t.rate), 100);
});
</script>

<template>
    <AppLayout>
        <Head title="Occupancy Analytics - Admin" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-2">
                            Occupancy Analytics
                        </h1>
                        <p class="text-lg text-gray-600 dark:text-gray-400">
                            Track property utilization and performance
                        </p>
                    </div>

                    <Link
                        :href="route('admin.dashboard')"
                        class="px-6 py-3 bg-gray-100 dark:bg-gray-900 hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-900 dark:text-white font-medium rounded-full transition-all"
                    >
                        Back to Dashboard
                    </Link>
                </div>

                <!-- Filters -->
                <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 mb-8">
                    <div class="flex items-center gap-4">
                        <Filter class="w-5 h-5 text-gray-400 flex-shrink-0" />

                        <select
                            v-model="selectedYear"
                            class="px-4 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                        >
                            <option v-for="year in years" :key="year" :value="year">
                                {{ year }}
                            </option>
                        </select>

                        <select
                            v-model="selectedMonth"
                            class="px-4 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                        >
                            <option v-for="month in months" :key="month.value" :value="month.value">
                                {{ month.label }}
                            </option>
                        </select>

                        <select
                            v-model="selectedBuilding"
                            class="px-4 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                        >
                            <option value="">All Properties</option>
                            <option v-for="building in buildings" :key="building.id" :value="building.id">
                                {{ building.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Key Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <!-- Overall Occupancy Rate -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-8 rounded-2xl border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                <BarChart3 class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                        <div class="text-4xl font-semibold text-gray-900 dark:text-white mb-2">
                            {{ overallOccupancy.rate }}%
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Occupancy Rate
                        </div>
                    </div>

                    <!-- Booked Nights -->
                    <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl border border-gray-200 dark:border-gray-800">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-green-50 dark:bg-green-900/20 flex items-center justify-center">
                                <Calendar class="w-6 h-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                        <div class="text-3xl font-semibold text-gray-900 dark:text-white mb-2">
                            {{ overallOccupancy.booked_nights }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Booked Nights
                        </div>
                    </div>

                    <!-- Available Nights -->
                    <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl border border-gray-200 dark:border-gray-800">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center">
                                <Building2 class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                            </div>
                        </div>
                        <div class="text-3xl font-semibold text-gray-900 dark:text-white mb-2">
                            {{ overallOccupancy.available_nights }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Total Available Nights
                        </div>
                    </div>

                    <!-- Revenue per Night -->
                    <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 p-8 rounded-2xl border border-green-200 dark:border-green-800">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                <DollarSign class="w-6 h-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                        <div class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">
                            {{ formatPrice(revenuePerNight.revenue_per_available_night) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Revenue per Available Night
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <!-- Monthly Trend -->
                    <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl border border-gray-200 dark:border-gray-800">
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-6">
                            Occupancy Trend (Last 6 Months)
                        </h3>
                        <div class="space-y-4">
                            <div v-for="trend in monthlyTrend" :key="trend.month" class="flex items-center gap-4">
                                <div class="w-20 text-sm text-gray-600 dark:text-gray-400">
                                    {{ trend.month }}
                                </div>
                                <div class="flex-1">
                                    <div class="h-8 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
                                        <div
                                            class="h-full bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg transition-all"
                                            :style="{ width: (trend.rate / maxTrendRate * 100) + '%' }"
                                        ></div>
                                    </div>
                                </div>
                                <div class="w-16 text-right">
                                    <span :class="getOccupancyColor(trend.rate)" class="text-sm font-medium">
                                        {{ trend.rate }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Performers -->
                    <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl border border-gray-200 dark:border-gray-800">
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-6">
                            Property Performance
                        </h3>
                        <div class="space-y-4">
                            <div v-for="(property, index) in topPerformers" :key="index" class="flex items-center justify-between">
                                <div class="flex items-center gap-3 flex-1">
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-sm font-medium text-gray-600 dark:text-gray-400">
                                        {{ index + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-900 dark:text-white font-medium">
                                            {{ property.property }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ property.booked_nights }}/{{ property.available_nights }} nights booked
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-16 h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                        <div
                                            :class="getOccupancyBgColor(property.occupancy_rate)"
                                            class="h-full rounded-full"
                                            :style="{ width: property.occupancy_rate + '%' }"
                                        ></div>
                                    </div>
                                    <span :class="getOccupancyColor(property.occupancy_rate)" class="text-sm font-semibold w-12 text-right">
                                        {{ property.occupancy_rate }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- All Properties Table -->
                <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl border border-gray-200 dark:border-gray-800">
                    <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-6">
                        All Properties
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="pb-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Property
                                </th>
                                <th class="pb-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Booked
                                </th>
                                <th class="pb-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Available
                                </th>
                                <th class="pb-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Occupancy
                                </th>
                                <th class="pb-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Rate
                                </th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            <tr v-for="property in occupancyByProperty" :key="property.property" class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                                <td class="py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ property.property }}
                                </td>
                                <td class="py-4 text-sm text-right text-gray-900 dark:text-white">
                                    {{ property.booked_nights }}
                                </td>
                                <td class="py-4 text-sm text-right text-gray-900 dark:text-white">
                                    {{ property.available_nights }}
                                </td>
                                <td class="py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="w-24 h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                            <div
                                                :class="getOccupancyBgColor(property.occupancy_rate)"
                                                class="h-full rounded-full"
                                                :style="{ width: property.occupancy_rate + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 text-right">
                                        <span :class="getOccupancyColor(property.occupancy_rate)" class="text-sm font-semibold">
                                            {{ property.occupancy_rate }}%
                                        </span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
