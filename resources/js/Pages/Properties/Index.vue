<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PropertyCard from '@/Components/Property/PropertyCard.vue';
import { Head, router } from '@inertiajs/vue3';
import {
    Building2
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps({
    unitTypes: Object,
    buildings: Array,
    filters: Object,
});

const bedroomFilter = ref(props.filters.bedroom_type || '');
const guestsFilter = ref(props.filters.guests || '');
const buildingFilter = ref(props.filters.building || '');
const sortBy = ref(props.filters.sort_by || '');

const applyFilters = () => {
    router.get(route('properties.index'), {
        bedroom_type: bedroomFilter.value,
        guests: guestsFilter.value,
        building: buildingFilter.value,
        sort: sortBy.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch([bedroomFilter, guestsFilter, buildingFilter, sortBy], () => {
    applyFilters();
});
</script>

<template>
    <AppLayout>
        <Head title="Browse Properties" />

        <div class="min-h-screen bg-white dark:bg-gray-950">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16">
                <!-- Header -->
                <div class="mb-16">
                    <h1 class="text-4xl md:text-5xl font-light tracking-tight text-gray-900 dark:text-white mb-4">
                        Available properties
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400 font-light">
                        {{ unitTypes.total }} apartment types ready for your next stay
                    </p>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-3 mb-8">
                    <!-- Location Filter -->
                    <select
                        v-model="buildingFilter"
                        class="px-5 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-full text-sm font-medium text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white focus:border-transparent transition-all"
                    >
                        <option value="">
                            <Building2 class="w-4 h-4 inline mr-2" />
                            All locations
                        </option>
                        <option v-for="building in buildings" :key="building.id" :value="building.slug">
                            {{ building.name }}
                        </option>
                    </select>

                    <!-- Bedroom Filter -->
                    <select
                        v-model="bedroomFilter"
                        class="px-5 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-full text-sm font-medium text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white focus:border-transparent transition-all"
                    >
                        <option value="">All bedrooms</option>
                        <option value="2-bed">2 Bedrooms</option>
                        <option value="3-bed">3 Bedrooms</option>
                        <option value="4-bed">4+ Bedrooms</option>
                    </select>

                    <!-- Guests Filter -->
                    <select
                        v-model="guestsFilter"
                        class="px-5 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-full text-sm font-medium text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white focus:border-transparent transition-all"
                    >
                        <option value="">Any guests</option>
                        <option :value="2">2+ guests</option>
                        <option :value="4">4+ guests</option>
                        <option :value="6">6+ guests</option>
                        <option :value="8">8+ guests</option>
                    </select>

                    <!-- Sort Filter -->
                    <select
                        v-model="sortBy"
                        class="px-5 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-full text-sm font-medium text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white focus:border-transparent transition-all"
                    >
                        <option value="">Newest first</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                    </select>
                </div>

                <!-- Property Grid -->
                <div v-if="unitTypes.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12 mb-16">
                    <PropertyCard
                        v-for="unitType in unitTypes.data"
                        :key="unitType.id"
                        :unit-type="unitType"
                    />
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-20">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <p class="text-lg text-gray-500 dark:text-gray-400">No properties match your criteria</p>
                </div>

                <!-- Pagination -->
                <div v-if="unitTypes.links.length > 3" class="flex justify-center items-center space-x-2">
                    <component
                        v-for="(link, index) in unitTypes.links"
                        :key="index"
                        :is="link.url ? 'button' : 'span'"
                        @click="link.url && router.visit(link.url)"
                        v-html="link.label"
                        :class="[
                            'min-w-[40px] h-10 flex items-center justify-center rounded-full text-sm font-medium transition-all',
                            link.active
                                ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                                : link.url
                                ? 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-800'
                                : 'text-gray-400 cursor-not-allowed'
                        ]"
                        :disabled="!link.url"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
