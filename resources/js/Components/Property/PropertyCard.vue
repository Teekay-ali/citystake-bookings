<script setup>
import { Link } from '@inertiajs/vue3'
import { Bed, Users, MapPin, Building2 } from 'lucide-vue-next'

defineProps({
    unitType: Object,
})

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price)
}
</script>

<template>
    <Link
        :href="route('properties.show', [unitType.building.slug, unitType.slug])"
        class="group block">

        <!-- Image -->
        <div class="relative aspect-[4/3] overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-900 mb-4">
            <img
                v-if="unitType.primary_image"
                :src="unitType.primary_image.image_path"
                :alt="unitType.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out" />
            <div v-else class="w-full h-full flex items-center justify-center">
                <Building2 class="w-8 h-8 text-gray-300 dark:text-gray-600" />
            </div>

            <!-- Subtle overlay on hover -->
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors duration-300"></div>
        </div>

        <!-- Content -->
        <div class="space-y-1.5">
            <h3 class="text-base font-medium text-gray-900 dark:text-white truncate group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors">
                {{ unitType.name }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                <MapPin class="w-3.5 h-3.5 flex-shrink-0" />
                {{ unitType.building.address }}, {{ unitType.building.city }}
            </p>
            <div class="flex items-baseline gap-1 pt-1">
                <span class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ formatPrice(unitType.base_price_per_night) }}
                </span>
                <span class="text-sm text-gray-500 dark:text-gray-400">/ night</span>
            </div>
            <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1">
                    <Bed class="w-3.5 h-3.5" />
                    {{ unitType.bedroom_type }}
                </span>
                <span class="flex items-center gap-1">
                    <Users class="w-3.5 h-3.5" />
                    Up to {{ unitType.max_guests }}
                </span>
            </div>
        </div>
    </Link>
</template>
