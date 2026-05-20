<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { MapPin, Building2, BedDouble, Users } from 'lucide-vue-next'

const props = defineProps({
    buildings: Array,
})

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
                        {{ buildings.length }} {{ buildings.length === 1 ? 'property' : 'properties' }} in Abuja
                    </p>
                </div>

                <!-- Empty state -->
                <div v-if="!buildings.length" class="text-center py-24">
                    <Building2 class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                    <p class="text-gray-500">No properties available right now.</p>
                </div>

                <!-- Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link
                        v-for="building in buildings"
                        :key="building.id"
                        :href="route('properties.building', building.slug)"
                        class="group block"
                    >
                        <!-- Image -->
                        <div class="relative aspect-[4/3] rounded-2xl overflow-hidden bg-gray-100 dark:bg-gray-900 mb-3">
                            <img
                                v-if="building.primary_image"
                                :src="building.primary_image.url"
                                :alt="building.name"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <Building2 class="w-10 h-10 text-gray-300 dark:text-gray-700" />
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="space-y-1">
                            <div class="flex items-start justify-between gap-2">
                                <h2 class="font-medium text-gray-900 dark:text-white group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors">
                                    {{ building.name }}
                                </h2>
                                <div v-if="startingPrice(building)" class="text-right flex-shrink-0">
                                    <span class="font-semibold text-gray-900 dark:text-white text-sm">
                                        {{ formatPrice(startingPrice(building)) }}
                                    </span>
                                    <span class="text-xs text-gray-400"> / night</span>
                                </div>
                            </div>

                            <p class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                                <MapPin class="w-3.5 h-3.5 flex-shrink-0" />
                                {{ building.address }}, {{ building.city }}
                            </p>

                            <p class="text-sm text-gray-400 dark:text-gray-500 truncate">
                                {{ unitTypeSummary(building) }}
                            </p>
                        </div>
                    </Link>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
