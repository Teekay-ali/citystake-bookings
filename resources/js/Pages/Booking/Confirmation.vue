<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { useToast } from 'vue-toastification';
import { CheckCircle, Calendar, MapPin, Users, Download, Home } from 'lucide-vue-next';

const props = defineProps({
    booking: Object,
});

const toast = useToast();

onMounted(() => {
    // Show toast after a small delay to ensure page is fully loaded
    setTimeout(() => {
        toast.success('🎉 Payment successful! Your booking is confirmed.', {
            timeout: 6000, // Show for 6 seconds
        });
    }, 300);
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
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Booking Confirmed!" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
            <div class="max-w-3xl mx-auto px-6 lg:px-8">
                <!-- Success Icon -->
                <div class="text-center mb-12">
                    <div class="w-24 h-24 rounded-full bg-green-50 dark:bg-green-900/20 flex items-center justify-center mx-auto mb-6">
                        <CheckCircle class="w-12 h-12 text-green-600 dark:text-green-400" />
                    </div>
                    <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-3">
                        Booking Confirmed!
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        Your reservation has been successfully confirmed
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">
                        Booking Reference: <span class="font-mono font-medium">{{ booking.booking_reference }}</span>
                    </p>
                </div>

                <!-- Booking Details Card -->
                <div class="border border-gray-200 dark:border-gray-800 rounded-3xl p-8 mb-8">
                    <!-- Property Info -->
                    <div class="flex items-start space-x-4 pb-6 mb-6 border-b border-gray-100 dark:border-gray-900">
                        <img
                            v-if="booking.unit_type.images && booking.unit_type.images[0]"
                            :src="booking.unit_type.images[0].image_path"
                            :alt="booking.unit_type.name"
                            class="w-24 h-24 rounded-xl object-cover"
                        />
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                {{ booking.unit_type.name }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                <MapPin class="w-4 h-4 mr-1" />
                                {{ booking.building.name }} • {{ booking.building.address }}
                            </p>
                        </div>
                    </div>

                    <!-- Stay Details -->
                    <div class="grid grid-cols-2 gap-6 mb-6 pb-6 border-b border-gray-100 dark:border-gray-900">
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 flex items-center">
                                <Calendar class="w-3 h-3 mr-1" />
                                CHECK-IN
                            </p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ formatDate(booking.check_in) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 flex items-center">
                                <Calendar class="w-3 h-3 mr-1" />
                                CHECK-OUT
                            </p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ formatDate(booking.check_out) }}
                            </p>
                        </div>
                    </div>

                    <!-- Guest Info -->
                    <div class="mb-6 pb-6 border-b border-gray-100 dark:border-gray-900">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                            <Users class="w-4 h-4 mr-2" />
                            Guest Details
                        </h3>
                        <div class="space-y-2 text-sm">
                            <p class="text-gray-600 dark:text-gray-400">
                                <span class="font-medium text-gray-900 dark:text-white">Name:</span> {{ booking.guest_name }}
                            </p>
                            <p class="text-gray-600 dark:text-gray-400">
                                <span class="font-medium text-gray-900 dark:text-white">Email:</span> {{ booking.guest_email }}
                            </p>
                            <p class="text-gray-600 dark:text-gray-400">
                                <span class="font-medium text-gray-900 dark:text-white">Phone:</span> {{ booking.guest_phone }}
                            </p>
                            <p class="text-gray-600 dark:text-gray-400">
                                <span class="font-medium text-gray-900 dark:text-white">Guests:</span> {{ booking.guests }}
                            </p>
                        </div>
                    </div>

                    <!-- Payment Summary -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Payment Summary</h3>
                        <div class="space-y-2 text-sm mb-4">
                            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                <span>{{ booking.nights }} nights</span>
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
                        </div>
                        <div class="pt-4 border-t border-gray-100 dark:border-gray-900">
                            <div class="flex justify-between items-baseline">
                                <span class="text-base font-medium text-gray-900 dark:text-white">Total Paid</span>
                                <span class="text-2xl font-light text-green-600 dark:text-green-400">
                                    {{ formatPrice(booking.total_amount) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Important Info -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900 rounded-2xl p-6 mb-8">
                    <h3 class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-3">Important Information</h3>
                    <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                        <li class="flex items-start">
                            <CheckCircle class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                            <span>A confirmation email has been sent to {{ booking.guest_email }}</span>
                        </li>
                        <li class="flex items-start">
                            <CheckCircle class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                            <span>Check-in time is after 2:00 PM</span>
                        </li>
                        <li class="flex items-start">
                            <CheckCircle class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                            <span>Please bring a valid ID for verification</span>
                        </li>
                    </ul>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <Link
                        :href="route('home')"
                        class="flex-1 flex items-center justify-center px-6 py-3 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 text-white dark:text-gray-900 font-medium rounded-full transition-all"
                    >
                        <Home class="w-5 h-5 mr-2" />
                        Back to Home
                    </Link>
                    <Link
                        :href="route('properties.index')"
                        class="flex-1 flex items-center justify-center px-6 py-3 border-2 border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 text-gray-900 dark:text-white font-medium rounded-full transition-all"
                    >
                        Browse Properties
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
