<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { MapPin, Building2, BedDouble, Users, Search, SlidersHorizontal, X } from 'lucide-vue-next'
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
    buildings: Array,
})

// ── Filters ───────────────────────────────────────────────────
const search    = ref('')
const cityFilter = ref('')
const bedroomFilter = ref('')

// Seed from URL query params (home page search passes check_in etc — just pre-fill city if present)
onMounted(() => {
    const params = new URLSearchParams(window.location.search)
    if (params.get('city')) cityFilter.value = params.get('city')
})

const cities = computed(() => [...new Set(props.buildings.map(b => b.city))].sort())

const bedroomTypes = computed(() => {
    const types = new Set()
    props.buildings.forEach(b => b.unit_types?.forEach(ut => types.add(ut.bedroom_type)))
    return [...types].sort()
})

const filteredBuildings = computed(() => {
    return props.buildings.filter(b => {
        const matchesSearch = !search.value ||
            b.name.toLowerCase().includes(search.value.toLowerCase()) ||
            b.city.toLowerCase().includes(search.value.toLowerCase()) ||
            b.address.toLowerCase().includes(search.value.toLowerCase())

        const matchesCity = !cityFilter.value || b.city === cityFilter.value

        const matchesBedroom = !bedroomFilter.value ||
            b.unit_types?.some(ut => ut.bedroom_type === bedroomFilter.value)

        return matchesSearch && matchesCity && matchesBedroom
    })
})

const hasFilters = computed(() => search.value || cityFilter.value || bedroomFilter.value)

function clearFilters() {
    search.value = ''
    cityFilter.value = ''
    bedroomFilter.value = ''
}

// ── Formatting ────────────────────────────────────────────────
const formatPrice = (price) => new Intl.NumberFormat('en-NG', {
    style: 'currency', currency: 'NGN', minimumFractionDigits: 0,
}).format(price)

const startingPrice = (building) => {
    if (!building.unit_types?.length) return null
    return Math.min(...building.unit_types.map(ut => parseFloat(ut.base_price_per_night)))
}

const unitTypeSummary = (building) => {
    if (!building.unit_types?.length) return ''
    return building.unit_types.map(ut => ut.name).join(' · ')
}
</script>

<template>
    <AppLayout>
        <Head title="Our Properties" />

        <div class="min-h-screen bg-white dark:bg-gray-950">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-12">

                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-light tracking-tight text-gray-900 dark:text-white mb-1">
                        Our properties
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400">
                        {{ filteredBuildings.length }} {{ filteredBuildings.length === 1 ? 'property' : 'properties' }} in Abuja
                    </p>
                </div>

                <!-- Filters -->
                <div class="flex flex-col sm:flex-row gap-3 mb-8">
                    <!-- Search -->
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by name or location..."
                            class="w-full pl-9 pr-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
                        />
                    </div>

                    <!-- City filter -->
                    <select
                        v-model="cityFilter"
                        class="px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
                    >
                        <option value="">All locations</option>
                        <option v-for="city in cities" :key="city" :value="city">{{ city }}</option>
                    </select>

                    <!-- Bedroom filter -->
                    <select
                        v-model="bedroomFilter"
                        class="px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
                    >
                        <option value="">All types</option>
                        <option v-for="type in bedroomTypes" :key="type" :value="type">{{ type }}</option>
                    </select>

                    <!-- Clear -->
                    <button
                        v-if="hasFilters"
                        @click="clearFilters"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-800 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-900 transition-all"
                    >
                        <X class="w-3.5 h-3.5" />
                        Clear
                    </button>
                </div>

                <!-- Empty state -->
                <div v-if="!filteredBuildings.length" class="text-center py-24">
                    <Building2 class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                    <p class="text-gray-500 dark:text-gray-400 mb-3">
                        {{ hasFilters ? 'No properties match your filters.' : 'No properties available right now.' }}
                    </p>
                    <button
                        v-if="hasFilters"
                        @click="clearFilters"
                        class="text-sm text-gray-900 dark:text-white underline underline-offset-2"
                    >
                        Clear filters
                    </button>
                </div>

                <!-- Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link
                        v-for="building in filteredBuildings"
                        :key="building.id"
                        :href="route('properties.building', building.slug)"
                        class="group block"
                    >
                        <!-- Image -->
                        <div class="relative aspect-[4/3] rounded-2xl overflow-hidden bg-gray-100 dark:bg-gray-900 mb-3">
                            <img
                                v-if="building.primary_image"
                                :src="building.primary_image?.url ?? building.primary_image"
                                :alt="building.name"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <Building2 class="w-10 h-10 text-gray-300 dark:text-gray-700" />
                            </div>
                            <!-- City badge -->
                            <div class="absolute top-3 left-3">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm rounded-full text-xs font-medium text-gray-700 dark:text-gray-300">
                                    <MapPin class="w-3 h-3 text-amber-500" />
                                    {{ building.city }}
                                </span>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="px-1">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-1 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors">
                                {{ building.name }}
                            </h3>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">
                                {{ unitTypeSummary(building) }}
                            </p>
                            <p v-if="startingPrice(building)" class="text-sm text-gray-900 dark:text-white">
                                From <span class="font-semibold">{{ formatPrice(startingPrice(building)) }}</span>
                                <span class="text-gray-400 font-normal"> / night</span>
                            </p>
                        </div>
                    </Link>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
