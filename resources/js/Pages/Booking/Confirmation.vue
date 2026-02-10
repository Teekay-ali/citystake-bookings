<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    booking: Object,
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-GB', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};
</script>

<template>
    <AppLayout>
        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
            <div class="max-w-3xl mx-auto px-6 lg:px-8">
                <!-- Success State -->
                <div class="text-center mb-16">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-green-50 dark:bg-green-900/20 rounded-full mb-6">
                        <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-3">
                        Booking confirmed
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        Your reservation has been successfully created
                    </p>
                </div>

                <!-- Booking Reference -->
                <div class="bg-gray-50 dark:bg-gray-900 rounded-2xl p-8 mb-8">
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Booking reference</p>
                        <p class="text-3xl font-light tracking-tight text-gray-900 dark:text-white">{{ booking.booking_reference }}</p>
                    </div>
                </div>

                <!-- Booking Details -->
                <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-8 mb-8">
                    <!-- Property -->
                    <div class="flex items-start space-x-4 pb-8 mb-8 border-b border-gray-100 dark:border-gray-900">
                        <img
                            v-if="booking.building.primary_image"
                            :src="booking.building.primary_image.image_path"
                            :alt="booking.building.name"
                            class="w-28 h-28 rounded-xl object-cover"
                        />
                        <div class="flex-1 min-w-0">
                            <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-2">
                                {{ booking.unit_type.name }}
                            </h2>
                            <p class="text-gray-600 dark:text-gray-400 mb-1">{{ booking.building.name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-500">{{ booking.building.address }} • {{ booking.unit_type.bedroom_type }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-600 mt-2">Unit: {{ booking.unit.unit_number }}</p>
                        </div>
                    </div>

                    <!-- Stay Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pb-8 mb-8 border-b border-gray-100 dark:border-gray-900">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Check-in</p>
                            <p class="text-lg font-medium text-gray-900 dark:text-white">{{ formatDate(booking.check_in) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Check-out</p>
                            <p class="text-lg font-medium text-gray-900 dark:text-white">{{ formatDate(booking.check_out) }}</p>
                        </div>
                    </div>

                    <!-- Price Summary -->
                    <div class="space-y-3">
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>{{ formatPrice(booking.unit_type.base_price_per_night) }} × {{ booking.nights }} nights</span>
                            <span>{{ formatPrice(booking.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Cleaning fee</span>
                            <span>{{ formatPrice(booking.cleaning_fee) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Service charge</span>
                            <span>{{ formatPrice(booking.service_charge) }}</span>
                        </div>
                        <div class="flex justify-between text-xl font-medium text-gray-900 dark:text-white pt-4 border-t border-gray-100 dark:border-gray-900">
                            <span>Total</span>
                            <span>{{ formatPrice(booking.total_amount) }}</span>
                        </div>
                    </div>

                    <!-- Special Requests -->
                    <div v-if="booking.special_requests" class="mt-8 pt-8 border-t border-gray-100 dark:border-gray-900">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Special requests</p>
                        <p class="text-gray-700 dark:text-gray-300">{{ booking.special_requests }}</p>
                    </div>
                </div>

                <!-- Payment Notice -->
                <div v-if="booking.payment_status === 'pending'" class="bg-yellow-50 dark:bg-yellow-900/10 border border-yellow-200 dark:border-yellow-800/50 rounded-2xl p-6 mb-8">
                    <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <h3 class="font-medium text-yellow-900 dark:text-yellow-200 mb-1">Payment pending</h3>
                            <p class="text-sm text-yellow-800 dark:text-yellow-300/90">
                                Your booking has been created but payment is pending. Please complete payment to confirm your reservation.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <Link
                        :href="route('home')"
                        class="flex-1 text-center px-6 py-4 bg-white dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 font-medium rounded-full hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
                    >
                        Back to home
                    </Link>
                    <Link
                        :href="route('properties.index')"
                        class="flex-1 text-center px-6 py-4 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium rounded-full hover:bg-gray-800 dark:hover:bg-gray-100 transition-all"
                    >
                        Browse more properties
                    </Link>
                </div>

                <!-- Email Notice -->
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center mt-8">
                    A confirmation email has been sent to {{ $page.props.auth.user.email }}
                </p>
            </div>
        </div>
    </AppLayout>
</template>
