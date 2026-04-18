<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { Calendar, Users, MapPin, User, Mail, Phone, MessageSquare, Receipt, CheckCircle } from 'lucide-vue-next';

const props = defineProps({
    building: Object,
    unitType: Object,
    bookingData: Object,
});

const toast = useToast();

const user = usePage().props.auth.user;

const form = useForm({
    check_in: props.bookingData.check_in,
    check_out: props.bookingData.check_out,
    guests: props.bookingData.guests,
    guest_name: user?.name ?? '',
    guest_email: user?.email ?? '',
    guest_phone: '',
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
    form.post(route('bookings.store', {
        building: props.building.slug,
        unitType: props.unitType.slug,
    }), {
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            if (firstError) {
                toast.error(firstError);
            }
        },
    });
};
</script>

<template>
    <AppLayout :hide-footer="true">
        <Head title="Confirm Booking" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
            <div class="max-w-5xl mx-auto px-6 lg:px-8">
                <!-- Back Button -->
                <Link
                    :href="building && unitType ? route('properties.show', [building?.slug, unitType?.slug]) : '#'"
                    class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors group mb-8"
                >
                    <svg class="w-4 h-4 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to property
                </Link>

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
                    <form @submit.prevent="submit" class="space-y-8">
                        <!-- Trip Details -->
                        <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-8">
                            <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6 flex items-center">
                                <Calendar class="w-5 h-5 mr-2" />
                                Your stay
                            </h2>
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

                        <!-- Guest Information Form -->
                        <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-8">
                            <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6 flex items-center">
                                <User class="w-5 h-5 mr-2" />
                                Guest information
                            </h2>
                            <div class="space-y-6">
                                <!-- Full Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                                        <User class="w-4 h-4 mr-1.5" />
                                        Full name <span class="text-red-500 ml-1">*</span>
                                    </label>
                                    <input
                                        v-model="form.guest_name"
                                        type="text"
                                        required
                                        :class="[
                                            'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                            form.errors.guest_name
                                                ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500 focus:border-red-500'
                                                : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white focus:border-transparent'
                                        ]"
                                        placeholder="Enter full name"
                                    />
                                    <p v-if="form.errors.guest_name" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.guest_name }}
                                    </p>
                                </div>

                                <!-- Email -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                                        <Mail class="w-4 h-4 mr-1.5" />
                                        Email address <span class="text-red-500 ml-1">*</span>
                                    </label>
                                    <input
                                        v-model="form.guest_email"
                                        type="email"
                                        required
                                        :class="[
                                            'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                            form.errors.guest_email
                                                ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500 focus:border-red-500'
                                                : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white focus:border-transparent'
                                        ]"
                                        placeholder="your.email@example.com"
                                    />
                                    <p v-if="form.errors.guest_email" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.guest_email }}
                                    </p>
                                </div>

                                <!-- Phone -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                                        <Phone class="w-4 h-4 mr-1.5" />
                                        Phone number <span class="text-red-500 ml-1">*</span>
                                    </label>
                                    <input
                                        v-model="form.guest_phone"
                                        type="tel"
                                        required
                                        :class="[
                                            'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                            form.errors.guest_phone
                                                ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500 focus:border-red-500'
                                                : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white focus:border-transparent'
                                        ]"
                                        placeholder="+234 800 000 0000"
                                    />
                                    <p v-if="form.errors.guest_phone" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.guest_phone }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Special Requests -->
                        <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-8">
                            <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6 flex items-center">
                                <MessageSquare class="w-5 h-5 mr-2" />
                                Special requests
                            </h2>
                            <textarea
                                v-model="form.special_requests"
                                rows="4"
                                placeholder="Any special requirements? (Optional)"
                                :class="[
                                    'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 transition-all resize-none',
                                    form.errors.special_requests
                                        ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500 focus:border-red-500'
                                        : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white focus:border-transparent'
                                ]"
                            ></textarea>
                            <p v-if="form.errors.special_requests" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.special_requests }}
                            </p>
                        </div>
                    </form>

                    <!-- Right: Summary -->
                    <div>
                        <div class="sticky top-24">
                            <!-- Property Card -->
                            <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-8 mb-6">
                                <div class="flex items-start space-x-4 mb-6 pb-6 border-b border-gray-100 dark:border-gray-900">
                                    <img
                                        v-if="unitType?.images && unitType?.images[0]"
                                        :src="unitType?.images[0].image_path"
                                        :alt="unitType?.name"
                                        class="w-24 h-24 rounded-xl object-cover"
                                    />
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-medium text-gray-900 dark:text-white mb-1">{{ unitType?.name }}</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3 flex items-center">
                                            <MapPin class="w-4 h-4 mr-1" />
                                            {{ building?.name }} • {{ building?.address }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ unitType?.bedroom_type }}</p>
                                    </div>
                                </div>

                                <!-- Price Breakdown -->
                                <div class="space-y-3 mb-6">
                                    <h3 class="font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                                        <Receipt class="w-5 h-5 mr-2" />
                                        Price details
                                    </h3>
                                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                        <span>{{ formatPrice(bookingData.subtotal / bookingData.nights) }} × {{ bookingData.nights }} nights</span>
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

                                    <div v-if="bookingData.discount_amount > 0" class="flex justify-between text-sm text-emerald-600 dark:text-emerald-400">
                                        <span>
                                            {{ bookingData.discount_type === 'long_stay' ? 'Long stay discount' : 'Bulk booking discount' }}
                                            ({{ bookingData.discount_percent }}% off)
                                        </span>
                                        <span>−{{ formatPrice(bookingData.discount_amount) }}</span>
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
                                type="button"
                                @click="submit"
                                :disabled="form.processing"
                                class="w-full bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 disabled:bg-gray-300 dark:disabled:bg-gray-700 text-white dark:text-gray-900 font-medium py-4 px-6 rounded-full transition-all disabled:cursor-not-allowed flex items-center justify-center"
                            >
                                <span v-if="form.processing" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Processing...
                                </span>
                                <span v-else>Confirm and pay</span>
                            </button>

                            <p class="text-xs text-gray-500 dark:text-gray-400 text-center mt-4">
                                You won't be charged until payment is complete
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
