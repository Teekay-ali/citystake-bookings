<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    property: Object,
    bookingData: Object,
});

const form = useForm({
    check_in: props.bookingData.check_in,
    check_out: props.bookingData.check_out,
    guests: props.bookingData.guests,
    special_requests: '',
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
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

const submit = () => {
    form.post(route('bookings.store', props.property.slug));
};
</script>

<template>
    <AppLayout>
        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
            <div class="max-w-5xl mx-auto px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-12">
                    <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-2">
                        Confirm and pay
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        Review your booking details
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <!-- Left: Form -->
                    <div class="space-y-8">
                        <!-- Trip Details -->
                        <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-8">
                            <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6">Your stay</h2>

                            <div class="space-y-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Dates</p>
                                        <p class="text-gray-900 dark:text-white mt-1">
                                            {{ formatDate(bookingData.check_in) }} – {{ formatDate(bookingData.check_out) }}
                                        </p>
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ bookingData.nights }} nights</span>
                                </div>

                                <div class="pt-4 border-t border-gray-100 dark:border-gray-900">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Guests</p>
                                    <p class="text-gray-900 dark:text-white mt-1">{{ bookingData.guests }} guests</p>
                                </div>
                            </div>
                        </div>

                        <!-- Guest Info -->
                        <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-8">
                            <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6">Guest information</h2>

                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Name</p>
                                    <p class="text-gray-900 dark:text-white mt-1">{{ $page.props.auth.user.name }}</p>
                                </div>

                                <div class="pt-4 border-t border-gray-100 dark:border-gray-900">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                                    <p class="text-gray-900 dark:text-white mt-1">{{ $page.props.auth.user.email }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Special Requests -->
                        <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-8">
                            <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6">Special requests</h2>
                            <textarea
                                v-model="form.special_requests"
                                rows="4"
                                placeholder="Any special requirements? (Optional)"
                                class="w-full px-4 py-3 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white focus:border-transparent transition-all resize-none"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Right: Summary -->
                    <div>
                        <div class="sticky top-24">
                            <!-- Property Card -->
                            <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-8 mb-6">
                                <div class="flex items-start space-x-4 mb-6 pb-6 border-b border-gray-100 dark:border-gray-900">
                                    <img
                                        v-if="property.primary_image"
                                        :src="property.primary_image.image_path"
                                        :alt="property.name"
                                        class="w-24 h-24 rounded-xl object-cover"
                                    />
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-medium text-gray-900 dark:text-white mb-1">{{ property.name }}</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ property.bedroom_type }}</p>
                                    </div>
                                </div>

                                <!-- Price Breakdown -->
                                <div class="space-y-3 mb-6">
                                    <h3 class="font-medium text-gray-900 dark:text-white mb-4">Price details</h3>

                                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                        <span>{{ formatPrice(property.base_price_per_night) }} × {{ bookingData.nights }} nights</span>
                                        <span>{{ formatPrice(bookingData.subtotal) }}</span>
                                    </div>

                                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                        <span>Cleaning fee</span>
                                        <span>{{ formatPrice(bookingData.cleaning_fee) }}</span>
                                    </div>

                                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                        <span>Service charge</span>
                                        <span>{{ formatPrice(bookingData.service_charge) }}</span>
                                    </div>
                                </div>

                                <!-- Total -->
                                <div class="pt-6 border-t border-gray-100 dark:border-gray-900">
                                    <div class="flex justify-between items-baseline">
                                        <span class="text-lg font-medium text-gray-900 dark:text-white">Total (NGN)</span>
                                        <span class="text-2xl font-light text-gray-900 dark:text-white">{{ formatPrice(bookingData.total_amount) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Confirm Button -->
                            <button
                                @click="submit"
                                :disabled="form.processing"
                                class="w-full bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 disabled:bg-gray-300 dark:disabled:bg-gray-700 text-white dark:text-gray-900 font-medium py-4 px-6 rounded-full transition-all disabled:cursor-not-allowed"
                            >
                                {{ form.processing ? 'Processing...' : 'Confirm and pay' }}
                            </button>

                            <p class="text-xs text-gray-500 dark:text-gray-400 text-center mt-4">
                                By selecting the button above, I agree to the terms and conditions
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
