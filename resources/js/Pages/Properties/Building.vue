<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import {
    MapPin, Users, Bed, ChevronRight, ChevronLeft, ArrowLeft,
    Wifi, Wind, Car, Waves, Dumbbell, Shield,
    Coffee, Tv, UtensilsCrossed, WashingMachine,
    Sparkles, Check, Star, LayoutGrid, X
} from 'lucide-vue-next'
import BookingBanner from '@/Components/BookingBanner.vue'
import { ref, computed, nextTick } from 'vue'

const props = defineProps({
    building:       Object,
    otherBuildings: Array,
    userBuildingBookings: { type: Array, default: () => [] },
})

const selectedImage = ref(props.building.images?.[0]?.url ?? null)

const gridViewOpen = ref(false)

// Lightbox
const lightboxOpen  = ref(false)
const lightboxIndex = ref(0)
const lightboxEl    = ref(null)

const openLightbox = (index) => {
    lightboxIndex.value = index
    lightboxOpen.value  = true
    nextTick(() => lightboxEl.value?.focus())
}
const closeLightbox = () => { lightboxOpen.value = false }
const lightboxPrev  = () => {
    lightboxIndex.value = (lightboxIndex.value - 1 + props.building.images.length) % props.building.images.length
}
const lightboxNext  = () => {
    lightboxIndex.value = (lightboxIndex.value + 1) % props.building.images.length
}

// Grid → lightbox bridge
const openFromGrid = (index) => {
    gridViewOpen.value = false
    setTimeout(() => openLightbox(index), 200)
}

const amenityIcon = (amenity) => {
    const a = amenity.toLowerCase()
    if (a.includes('wifi') || a.includes('internet'))  return Wifi
    if (a.includes('air') || a.includes('ac'))          return Wind
    if (a.includes('parking'))                          return Car
    if (a.includes('pool') || a.includes('swimming'))   return Waves
    if (a.includes('gym') || a.includes('fitness'))     return Dumbbell
    if (a.includes('security'))                         return Shield
    if (a.includes('tv') || a.includes('netflix'))      return Tv
    if (a.includes('kitchen') || a.includes('chef'))    return UtensilsCrossed
    if (a.includes('washing') || a.includes('laundry')) return WashingMachine
    if (a.includes('coffee') || a.includes('breakfast'))return Coffee
    if (a.includes('generator') || a.includes('power')) return Sparkles
    return Check
}

const formatPrice = (price) => new Intl.NumberFormat('en-NG', {
    style: 'currency', currency: 'NGN', minimumFractionDigits: 0,
}).format(price)

const lowestPrice = computed(() =>
    props.building.unit_types?.length
        ? Math.min(...props.building.unit_types.map(ut => parseFloat(ut.base_price_per_night)))
        : 0
)

function bookingForUnitType(unitTypeId) {
    return props.userBuildingBookings?.find(b => b.unit_type_id === unitTypeId) ?? null
}

</script>

<template>
    <AppLayout :hide-footer="true">
        <Head :title="building.name" />

        <div class="min-h-screen bg-white dark:bg-gray-950">

            <!-- Back Button -->
            <div class="max-w-7xl mx-auto px-6 lg:px-8 pt-8">
                <Link :href="route('properties.index')"
                      class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors group"
                >
                    <ArrowLeft class="w-4 h-4 mr-2 group-hover:-translate-x-1 transition-transform" />
                    All properties
                </Link>
            </div>

            <!-- ── Image Gallery ── -->
            <div class="max-w-7xl mx-auto px-6 lg:px-8 pt-8">
                <div class="relative">
                    <div class="grid grid-cols-4 gap-2 rounded-3xl overflow-hidden h-[420px] mb-10">
                        <div class="col-span-4 md:col-span-2 md:row-span-2 relative group cursor-pointer"
                             @click="openLightbox(0)">
                            <img :src="selectedImage ?? building.images?.[0]?.url"
                                 :alt="building.name"
                                 class="w-full h-full object-cover" />
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors" />
                        </div>
                        <template v-if="building.images?.length > 1">
                            <div v-for="(img, i) in building.images.slice(1, 5)" :key="i"
                                 @click="openLightbox(i + 1)"
                                 class="relative cursor-pointer overflow-hidden group hidden md:block">
                                <img :src="img.url" :alt="building.name"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors" />
                            </div>
                        </template>
                    </div>

                    <!-- Show all photos button -->
                    <button
                        v-if="building.images?.length > 5"
                        @click="gridViewOpen = true"
                        class="absolute bottom-4 right-4 flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white text-sm font-medium rounded-xl shadow-lg transition-all"
                    >
                        <LayoutGrid class="w-4 h-4" />
                        Show all {{ building.images.length }} photos
                    </button>
                </div>
            </div>

            <!-- ── Content ── -->
            <div class="max-w-7xl mx-auto px-6 lg:px-8 pb-20">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

                    <!-- Left - main content -->
                    <div class="lg:col-span-2 space-y-12">

                        <!-- Header -->
                        <div>
                            <div class="flex items-center gap-2 text-sm text-amber-500 font-medium mb-3">
                                <MapPin class="w-4 h-4" />
                                {{ building.city }} · {{ building.address }}
                            </div>
                            <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-4">
                                {{ building.name }}
                            </h1>
                            <p class="text-lg text-gray-500 dark:text-gray-400 font-light leading-relaxed">
                                {{ building.description }}
                            </p>
                        </div>

                        <!-- Unit Types -->
                        <div>
                            <h2 class="text-2xl font-light tracking-tight text-gray-900 dark:text-white mb-6">
                                Available apartments
                            </h2>

                            <div class="space-y-4">
                                <div v-for="unitType in building.unit_types" :key="unitType.id"
                                     class="border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden hover:border-gray-300 dark:hover:border-gray-700 transition-all group">

                                    <div class="flex flex-col sm:flex-row">
                                        <!-- Image -->
                                        <div class="w-full sm:w-48 h-48 sm:h-auto flex-shrink-0 overflow-hidden">
                                            <img v-if="unitType.primary_image"
                                                 :src="unitType.primary_image.url"
                                                 :alt="unitType.name"
                                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                            <div v-else class="w-full h-full bg-gray-100 dark:bg-gray-900 flex items-center justify-center">
                                                <Bed class="w-8 h-8 text-gray-300" />
                                            </div>
                                        </div>

                                        <!-- Info -->
                                        <div class="flex-1 p-6 flex flex-col justify-between">
                                            <div>
                                                <div class="flex items-start justify-between gap-4 mb-2">
                                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                                        {{ unitType.name }}
                                                    </h3>
                                                    <div class="text-right flex-shrink-0">
                                                        <p class="text-xl font-light text-gray-900 dark:text-white">
                                                            {{ formatPrice(unitType.base_price_per_night) }}
                                                        </p>
                                                        <p class="text-xs text-gray-400">per night</p>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-3">
                                                    <span class="flex items-center gap-1.5">
                                                        <Bed class="w-4 h-4" />
                                                        {{ unitType.bedroom_type.replace('-bed', '') }} bed
                                                    </span>
                                                    <span class="flex items-center gap-1.5">
                                                        <Users class="w-4 h-4" />
                                                        Up to {{ unitType.max_guests }} guests
                                                    </span>
                                                    <span :class="[
                                                        'text-xs font-medium px-2 py-0.5 rounded-full',
                                                        unitType.available_now > 0
                                                            ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400'
                                                            : 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400'
                                                    ]">
                                                        {{ unitType.available_now > 0 ? `${unitType.available_now} available` : 'Fully booked today' }}
                                                    </span>
                                                </div>
                                            </div>

                                            <BookingBanner
                                                v-if="bookingForUnitType(unitType.id)"
                                                :booking="bookingForUnitType(unitType.id)"
                                                class="mb-4"
                                            />

                                            <div class="flex items-center justify-end">
                                                <Link :href="route('properties.show', [building.slug, unitType.slug])"
                                                      class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-xl hover:opacity-90 transition-all group/btn">
                                                    View & Book
                                                    <ChevronRight class="w-4 h-4 group-hover/btn:translate-x-0.5 transition-transform" />
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Amenities -->
                        <div v-if="building.amenities?.length">
                            <h2 class="text-2xl font-light tracking-tight text-gray-900 dark:text-white mb-6">
                                Building amenities
                            </h2>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div v-for="amenity in building.amenities" :key="amenity"
                                     class="flex items-center gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-900 rounded-xl">
                                    <component :is="amenityIcon(amenity)"
                                               class="w-4 h-4 text-gray-500 dark:text-gray-400 flex-shrink-0" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ amenity }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- House Rules -->
                        <div v-if="building.house_rules?.length">
                            <h2 class="text-2xl font-light tracking-tight text-gray-900 dark:text-white mb-6">
                                House rules
                            </h2>
                            <div class="space-y-2">
                                <div v-for="rule in building.house_rules" :key="rule"
                                     class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                                    <div class="w-1.5 h-1.5 rounded-full bg-gray-400 flex-shrink-0"></div>
                                    {{ rule }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right - sticky summary -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-8 space-y-4">

                            <!-- Price summary card -->
                            <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Apartments from</p>
                                <p class="text-3xl font-light text-gray-900 dark:text-white mb-1">
                                    {{ formatPrice(lowestPrice) }}
                                </p>
                                <p class="text-sm text-gray-400 mb-6">per night</p>

                                <div class="space-y-2 mb-6">
                                    <div v-for="unitType in building.unit_types" :key="unitType.id"
                                         class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">{{ unitType.name }}</span>
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ formatPrice(unitType.base_price_per_night) }}
                                        </span>
                                    </div>
                                </div>

                                <Link :href="route('properties.show', [building.slug, building.unit_types?.[0]?.slug])"
                                      class="block w-full text-center px-6 py-3.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium rounded-xl hover:opacity-90 transition-all">
                                    Check availability
                                </Link>
                            </div>

                            <!-- Location card -->
                            <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Location</h3>
                                <div class="flex items-start gap-2 text-sm text-gray-500 dark:text-gray-400">
                                    <MapPin class="w-4 h-4 mt-0.5 flex-shrink-0 text-amber-500" />
                                    <span>{{ building.address }}, {{ building.city }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Other Buildings ── -->
                <div v-if="otherBuildings.length" class="mt-20 pt-12 border-t border-gray-100 dark:border-gray-800">
                    <h2 class="text-2xl font-light tracking-tight text-gray-900 dark:text-white mb-8">
                        Explore our other properties
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <Link v-for="b in otherBuildings" :key="b.id"
                              :href="route('properties.building', b.slug)"
                              class="group border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden hover:border-gray-300 dark:hover:border-gray-700 transition-all">
                            <div class="h-48 overflow-hidden">
                                <img v-if="b.images?.[0]"
                                     :src="b.images[0].url"
                                     :alt="b.name"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                <div v-else class="w-full h-full bg-gray-100 dark:bg-gray-900" />
                            </div>
                            <div class="p-5">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">{{ b.name }}</h3>
                                <div class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                                    <MapPin class="w-3.5 h-3.5 text-amber-500" />
                                    {{ b.city }}
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- All Photos Grid Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="gridViewOpen" class="fixed inset-0 z-50 bg-white dark:bg-gray-950 overflow-y-auto">
                    <div class="sticky top-0 z-10 flex items-center justify-between px-6 py-4 bg-white/95 dark:bg-gray-950/95 backdrop-blur border-b border-gray-100 dark:border-gray-900">
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ building.name }} - {{ building.images.length }} photos
                </span>
                        <button @click="gridViewOpen = false" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors">
                            <X class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                        </button>
                    </div>
                    <div class="max-w-6xl mx-auto px-6 py-8">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                            <div
                                v-for="(img, i) in building.images" :key="img.id"
                                @click="openFromGrid(i)"
                                class="relative aspect-[4/3] rounded-xl overflow-hidden cursor-pointer group"
                            >
                                <img :src="img.url" :alt="building.name" loading="lazy"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors" />
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Lightbox -->
        <Teleport to="body">
            <div
                v-if="lightboxOpen"
                ref="lightboxEl"
                tabindex="0"
                class="fixed inset-0 z-[60] bg-black/95 flex items-center justify-center outline-none"
                @click.self="closeLightbox"
                @keydown.right="lightboxNext"
                @keydown.left="lightboxPrev"
                @keydown.esc="closeLightbox"
            >
                <!-- Close -->
                <button @click="closeLightbox"
                        class="absolute top-4 right-4 text-white/70 hover:text-white p-2 rounded-full hover:bg-white/10 transition-all">
                    <X class="w-6 h-6" />
                </button>

                <!-- Counter -->
                <div class="absolute top-4 left-4 text-white/60 text-sm font-medium">
                    {{ lightboxIndex + 1 }} / {{ building.images.length }}
                </div>

                <!-- Prev -->
                <button @click="lightboxPrev"
                        class="absolute left-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white p-3 rounded-full hover:bg-white/10 transition-all">
                    <ChevronLeft class="w-8 h-8" />
                </button>

                <!-- Image -->
                <img
                    :src="building.images[lightboxIndex]?.url"
                    :alt="building.name"
                    class="max-h-[90vh] max-w-[90vw] object-contain rounded-lg"
                />

                <!-- Next -->
                <button @click="lightboxNext"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white p-3 rounded-full hover:bg-white/10 transition-all">
                    <ChevronRight class="w-8 h-8" />
                </button>

                <!-- Thumbnail strip -->
                <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-2 px-4 overflow-x-auto">
                    <button
                        v-for="(img, i) in building.images"
                        :key="i"
                        @click="lightboxIndex = i"
                        :class="[
                    'w-12 h-12 rounded-lg overflow-hidden flex-shrink-0 border-2 transition-all',
                    i === lightboxIndex ? 'border-white' : 'border-transparent opacity-50 hover:opacity-75'
                ]"
                    >
                        <img :src="img.url" :alt="building.name" class="w-full h-full object-cover" />
                    </button>
                </div>
            </div>
        </Teleport>

        <!-- Mobile sticky bottom bar -->
        <div class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white dark:bg-gray-950 border-t border-gray-200 dark:border-gray-800 px-4 py-3 flex items-center justify-between shadow-lg">
            <div>
                <p class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ formatPrice(lowestPrice) }}
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">/ night</span>
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500">Apartments from</p>
            </div>
            <Link
                :href="route('properties.show', [building.slug, building.unit_types?.[0]?.slug])"
                class="px-6 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-full hover:bg-gray-800 dark:hover:bg-gray-100 transition-all"
            >
                View apartments
            </Link>
        </div>

    </AppLayout>
</template>
