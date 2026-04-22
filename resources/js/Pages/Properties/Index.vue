<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import PropertyCard from '@/Components/Property/PropertyCard.vue'
import { Head, router } from '@inertiajs/vue3'
import { Building2 } from 'lucide-vue-next'
import { ref, watch } from 'vue'

const props = defineProps({
    unitTypes: Object,
    buildings: Array,
    filters: Object,
})

const bedroomFilter = ref(props.filters.bedroom_type || '')
const guestsFilter  = ref(props.filters.guests || '')
const buildingFilter = ref(props.filters.building || '')
const sortBy        = ref(props.filters.sort_by || '')

const applyFilters = () => {
    router.get(route('properties.index'), {
        bedroom_type: bedroomFilter.value,
        guests:       guestsFilter.value,
        building:     buildingFilter.value,
        sort:         sortBy.value,
    }, {
        preserveState:  true,
        preserveScroll: true,
    })
}

watch([bedroomFilter, guestsFilter, buildingFilter, sortBy], applyFilters)

const selectClass = "px-4 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
</script>

<template>
    <AppLayout :hide-footer="true">
        <Head title="Browse Properties" />

        <div class="min-h-screen bg-white dark:bg-gray-950">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16">

                <!-- Header -->
                <div class="mb-10">
                    <h1 class="text-4xl md:text-5xl font-light tracking-tight text-gray-900 dark:text-white mb-3">
                        Available properties
                    </h1>
                    <p class="text-lg text-gray-500 dark:text-gray-400 font-light">
                        {{ unitTypes.total }} apartment type{{ unitTypes.total !== 1 ? 's' : '' }} ready for your next stay
                    </p>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-2 mb-8">
                    <select v-model="buildingFilter" :class="selectClass">
                        <option value="">All locations</option>
                        <option v-for="building in buildings" :key="building.id" :value="building.slug">
                            {{ building.name }}
                        </option>
                    </select>

                    <select v-model="bedroomFilter" :class="selectClass">
                        <option value="">All bedrooms</option>
                        <option value="2-bed">2 Bedrooms</option>
                        <option value="3-bed">3 Bedrooms</option>
                        <option value="4-bed">4+ Bedrooms</option>
                    </select>

                    <select v-model="guestsFilter" :class="selectClass">
                        <option value="">Any guests</option>
                        <option :value="2">2+ guests</option>
                        <option :value="4">4+ guests</option>
                        <option :value="6">6+ guests</option>
                        <option :value="8">8+ guests</option>
                    </select>

                    <select v-model="sortBy" :class="selectClass">
                        <option value="">Newest first</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                    </select>

                    <!-- Active filter count indicator -->
                    <button
                        v-if="bedroomFilter || guestsFilter || buildingFilter || sortBy"
                        @click="bedroomFilter = ''; guestsFilter = ''; buildingFilter = ''; sortBy = ''"
                        class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                        Clear filters
                    </button>
                </div>

                <!-- Property Grid -->
                <div v-if="unitTypes.data.length > 0"
                     class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-10 mb-16">
                    <PropertyCard
                        v-for="unitType in unitTypes.data"
                        :key="unitType.id"
                        :unit-type="unitType" />
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-20">
                    <div class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-4">
                        <Building2 class="w-6 h-6 text-gray-400" />
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">
                        No properties found
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                        Try adjusting your filters to see more results.
                    </p>
                    <button
                        @click="bedroomFilter = ''; guestsFilter = ''; buildingFilter = ''; sortBy = ''"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 transition-all">
                        Clear all filters
                    </button>
                </div>

                <!-- Pagination -->
                <div v-if="unitTypes.links.length > 3" class="flex justify-center items-center gap-1.5">
                    <component
                        v-for="(link, index) in unitTypes.links"
                        :key="index"
                        :is="link.url ? 'button' : 'span'"
                        @click="link.url && router.visit(link.url)"
                        v-html="link.label"
                        :disabled="!link.url"
                        :class="[
                            'min-w-[36px] h-9 flex items-center justify-center rounded-lg text-sm font-medium transition-all px-3',
                            link.active
                                ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                                : link.url
                                ? 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-800'
                                : 'text-gray-300 dark:text-gray-600 cursor-not-allowed'
                        ]" />
                </div>

            </div>
        </div>
    </AppLayout>
</template>
