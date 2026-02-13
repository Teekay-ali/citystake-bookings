<script setup>
import { Link } from '@inertiajs/vue3';
import {
    Bed,
    Users,
    MapPin
} from 'lucide-vue-next';

defineProps({
    unitType: Object,
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};
</script>

<template>
    <Link
        :href="route('properties.show', [unitType.building.slug, unitType.slug])"
        class="group block"
    >
        <!-- Image -->
        <div class="relative aspect-[4/3] overflow-hidden rounded-2xl bg-gray-100 dark:bg-gray-900 mb-4">
            <img
                v-if="unitType.primary_image"
                :src="unitType.primary_image.image_path"
                :alt="unitType.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out"
            />
            <div v-else class="w-full h-full flex items-center justify-center">
                <span class="text-gray-400">No image</span>
            </div>

            <!-- Subtle overlay on hover -->
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors duration-300"></div>
        </div>

        <!-- Content -->
        <div class="space-y-2">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white truncate group-hover:text-gray-700 dark:group-hover:text-gray-300 transition-colors">
                        {{ unitType.name }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 flex items-center">
                        <MapPin class="w-4 h-4 mr-1 flex-shrink-0" />
                        {{ unitType.building.address }}, {{ unitType.building.city }}
                    </p>
                </div>
            </div>

            <div class="flex items-baseline space-x-1">
                <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ formatPrice(unitType.base_price_per_night) }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">/ night</span>
            </div>

            <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                <span class="flex items-center">
                    <Bed class="w-4 h-4 mr-1.5" />
                    {{ unitType.bedroom_type }}
                </span>
                            <span class="flex items-center">
                    <Users class="w-4 h-4 mr-1.5" />
                    Up to {{ unitType.max_guests }}
                </span>
            </div>
        </div>
    </Link>
</template>
