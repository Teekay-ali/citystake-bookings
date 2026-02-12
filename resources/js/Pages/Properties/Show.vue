<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import flatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';

const props = defineProps({
    building: Object,
    unitType: Object,
});


const selectedImage = ref(props.unitType.images[0]?.image_path || null);
const guests = ref(2);
const isCheckingAvailability = ref(false);
const availabilityMessage = ref('');
const availableUnitsCount = ref(0);

const dateRange = ref('');
const checkIn = ref('');
const checkOut = ref('');

const dateConfig = {
    mode: 'range',
    dateFormat: 'd M Y',
    minDate: 'today',
    onClose: (selectedDates) => {
        if (selectedDates.length === 2) {
            checkIn.value = selectedDates[0].toISOString().split('T')[0];
            checkOut.value = selectedDates[1].toISOString().split('T')[0];
        }
    }
};


const formatPrice = (price) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

const calculateNights = computed(() => {
    if (!checkIn.value || !checkOut.value) return 0;
    const start = new Date(checkIn.value);
    const end = new Date(checkOut.value);
    const diffTime = Math.abs(end - start);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
});

const estimatedTotal = computed(() => {
    if (calculateNights.value === 0) return 0;
    const subtotal = calculateNights.value * parseFloat(props.unitType.base_price_per_night);
    const serviceCharge = subtotal * (parseFloat(props.unitType.service_charge_percent) / 100);
    const total = subtotal + parseFloat(props.unitType.cleaning_fee) + serviceCharge;
    return total;
});

const allAmenities = computed(() => {
    const buildingAmenities = props.building.amenities || [];
    const unitAmenities = props.unitType.specific_amenities || [];
    return [...new Set([...buildingAmenities, ...unitAmenities])];
});

const checkAvailability = async () => {
    if (!checkIn.value || !checkOut.value) {
        availabilityMessage.value = 'Please select check-in and check-out dates';
        return;
    }

    isCheckingAvailability.value = true;
    availabilityMessage.value = '';

    try {
        const response = await fetch(route('properties.check-availability', [props.building.slug, props.unitType.slug]), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                check_in: checkIn.value,
                check_out: checkOut.value,
            }),
        });

        const data = await response.json();
        availabilityMessage.value = data.message;
        availableUnitsCount.value = data.available_units || 0;
    } catch (error) {
        availabilityMessage.value = 'Error checking availability';
    } finally {
        isCheckingAvailability.value = false;
    }
};

const proceedToBooking = () => {
    if (!checkIn.value || !checkOut.value || guests.value < 1) {
        alert('Please select dates and number of guests');
        return;
    }

    router.get(route('bookings.create', [props.building.slug, props.unitType.slug]), {
        check_in: checkIn.value,
        check_out: checkOut.value,
        guests: guests.value,
    });
};

</script>

<template>
    <AppLayout>
        <div class="bg-white dark:bg-gray-950 min-h-screen">
            <!-- Back Button -->
            <div class="max-w-7xl mx-auto px-6 lg:px-8 pt-8">
                <Link
                    :href="route('properties.index')"
                    class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors group"
                >
                    <svg class="w-4 h-4 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to properties
                </Link>
            </div>

            <!-- Image Gallery -->
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-8">
                <div class="grid grid-cols-4 gap-2 rounded-3xl overflow-hidden h-[600px]">
                    <!-- Main Image -->
                    <div class="col-span-4 md:col-span-2 md:row-span-2 relative group cursor-pointer" @click="selectedImage = unitType.images[0]?.image_path">
                        <img
                            v-if="unitType.images[0]"
                            :src="unitType.images[0].image_path"
                            :alt="unitType.name"
                            class="w-full h-full object-cover"
                        />
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>
                    </div>

                    <!-- Thumbnail Grid -->
                    <template v-if="unitType.images.length > 1">
                        <div
                            v-for="(image, index) in unitType.images.slice(1, 5)"
                            :key="image.id"
                            class="relative group cursor-pointer overflow-hidden"
                            @click="selectedImage = image.image_path"
                        >
                            <img
                                :src="image.image_path"
                                :alt="unitType.name"
                                class="w-full h-full object-cover"
                            />
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>

                            <div v-if="index === 3 && unitType.images.length > 5" class="absolute inset-0 bg-black/60 flex items-center justify-center">
                                <span class="text-white text-sm font-medium">+{{ unitType.images.length - 5 }} more</span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Main Content -->
            <div class="max-w-7xl mx-auto px-6 lg:px-8 pb-24">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-16">
                    <!-- Left Column: Details -->
                    <div class="lg:col-span-2 space-y-12">
                        <!-- Header -->
                        <div class="border-b border-gray-100 dark:border-gray-900 pb-8">
                            <h1 class="text-3xl md:text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-4">
                                {{ unitType.name }}
                            </h1>
                            <p class="text-lg text-gray-600 dark:text-gray-400 mb-2">
                                {{ building.address }}, {{ building.city }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-500">
                                {{ building.name }}
                            </p>
                            <div class="flex items-center space-x-6 mt-6 text-gray-600 dark:text-gray-400">
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                    {{ unitType.bedroom_type }}
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    Up to {{ unitType.max_guests }} guests
                                </span>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="unitType.description || building.description">
                            <h2 class="text-2xl font-light text-gray-900 dark:text-white mb-4">About this place</h2>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed text-lg mb-4">
                                {{ unitType.description }}
                            </p>
                            <p v-if="building.description" class="text-gray-600 dark:text-gray-400 leading-relaxed text-lg">
                                {{ building.description }}
                            </p>
                        </div>

                        <!-- Amenities -->
                        <div v-if="allAmenities.length">
                            <h2 class="text-2xl font-light text-gray-900 dark:text-white mb-6">What this place offers</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div
                                    v-for="amenity in allAmenities"
                                    :key="amenity"
                                    class="flex items-center space-x-3 text-gray-700 dark:text-gray-300"
                                >
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>{{ amenity }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- House Rules -->
                        <div v-if="building.house_rules && building.house_rules.length">
                            <h2 class="text-2xl font-light text-gray-900 dark:text-white mb-6">Things to know</h2>
                            <div class="space-y-3">
                                <div
                                    v-for="rule in building.house_rules"
                                    :key="rule"
                                    class="flex items-start space-x-3 text-gray-700 dark:text-gray-300"
                                >
                                    <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ rule }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Booking Card (Sticky) -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-24">
                            <div class="border border-gray-200 dark:border-gray-800 rounded-3xl p-8 bg-white dark:bg-gray-900 shadow-sm">
                                <!-- Price -->
                                <div class="mb-6 pb-6 border-b border-gray-100 dark:border-gray-800">
                                    <div class="flex items-baseline">
                                        <span class="text-3xl font-light text-gray-900 dark:text-white">
                                            {{ formatPrice(unitType.base_price_per_night) }}
                                        </span>
                                        <span class="ml-2 text-gray-500 dark:text-gray-400">/ night</span>
                                    </div>
                                </div>

                                <!-- Date Selection -->
                                <div class="space-y-4 mb-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Select dates
                                        </label>
                                        <flat-pickr
                                            v-model="dateRange"
                                            :config="dateConfig"
                                            placeholder="Check-in → Check-out"
                                            class="w-full px-4 py-3 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white focus:border-transparent transition-all"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Guests
                                        </label>
                                        <select
                                            v-model="guests"
                                            class="w-full px-4 py-3 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white focus:border-transparent transition-all"
                                        >
                                            <option v-for="n in unitType.max_guests" :key="n" :value="n">
                                                {{ n }} {{ n === 1 ? 'guest' : 'guests' }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Price Breakdown -->
                                <div v-if="calculateNights > 0" class="space-y-3 mb-6 pb-6 border-b border-gray-100 dark:border-gray-800">
                                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                        <span>{{ formatPrice(unitType.base_price_per_night) }} × {{ calculateNights }} nights</span>
                                        <span>{{ formatPrice(calculateNights * parseFloat(unitType.base_price_per_night)) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                        <span>Cleaning fee</span>
                                        <span>{{ formatPrice(unitType.cleaning_fee) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                        <span>Service charge</span>
                                        <span>{{ formatPrice((calculateNights * parseFloat(unitType.base_price_per_night)) * (parseFloat(unitType.service_charge_percent) / 100)) }}</span>
                                    </div>
                                </div>

                                <!-- Total -->
                                <div v-if="calculateNights > 0" class="mb-6">
                                    <div class="flex justify-between items-baseline">
                                        <span class="text-lg font-medium text-gray-900 dark:text-white">Total</span>
                                        <span class="text-2xl font-light text-gray-900 dark:text-white">{{ formatPrice(estimatedTotal) }}</span>
                                    </div>
                                </div>

                                <!-- Availability Message -->
                                <p v-if="availabilityMessage" class="text-sm mb-4 text-center py-2 px-4 rounded-lg" :class="availabilityMessage.includes('Available') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400'">
                                    {{ availabilityMessage }}
                                </p>

                                <!-- Reserve Button -->
                                <button
                                    v-if="$page.props.auth.user"
                                    @click="proceedToBooking"
                                    :disabled="!checkIn || !checkOut || calculateNights === 0"
                                    class="w-full bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 disabled:bg-gray-300 dark:disabled:bg-gray-700 text-white dark:text-gray-900 dark:disabled:text-gray-500 font-medium py-4 px-6 rounded-full transition-all disabled:cursor-not-allowed"
                                >
                                    Reserve
                                </button>

                                <Link
                                    v-else
                                    :href="route('login')"
                                    class="block w-full bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 text-white dark:text-gray-900 font-medium py-4 px-6 rounded-full transition-all text-center"
                                >
                                    Sign in to reserve
                                </Link>

                                <p class="text-xs text-gray-500 dark:text-gray-400 text-center mt-4">
                                    You won't be charged yet
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
