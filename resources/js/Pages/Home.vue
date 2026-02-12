<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import flatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';

const dateRange = ref({ start: '', end: '' });
const guests = ref(2);

const dateConfig = {
    mode: 'range',
    dateFormat: 'd M Y',
    minDate: 'today',
    onChange: (selectedDates) => {
        if (selectedDates.length === 2) {
            dateRange.value.start = selectedDates[0].toISOString().split('T')[0];
            dateRange.value.end = selectedDates[1].toISOString().split('T')[0];
        }
    }
};

const searchProperties = () => {
    if (!dateRange.value.start || !dateRange.value.end) {
        router.get(route('properties.index'));
        return;
    }

    router.get(route('properties.index'), {
        check_in: dateRange.value.start,
        check_out: dateRange.value.end,
        guests: guests.value
    });
};


const featuredDestinations = [
    {
        name: 'Asokoro',
        description: 'Luxurious apartments in Abuja\'s prestigious diplomatic enclave',
        image: 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=600&h=400&fit=crop',
    },
    {
        name: 'Maitama',
        description: 'Premium stays in the heart of the exclusive district',
        image: 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=600&h=400&fit=crop',
    },
    {
        name: 'Wuse',
        description: 'Modern living in Abuja\'s vibrant commercial center',
        image: 'https://images.unsplash.com/photo-1600047509358-9dc75507daeb?w=600&h=400&fit=crop',
    },
];
</script>

<template>
    <AppLayout>
        <!-- Hero Section with Search -->
        <div class="relative bg-white dark:bg-gray-950">
            <!-- Hero Image/Background -->
            <div class="relative h-[600px] bg-gradient-to-br from-gray-900 to-gray-800 dark:from-gray-950 dark:to-gray-900">
                <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=1600&h=900&fit=crop')] bg-cover bg-center opacity-20"></div>

                <div class="relative max-w-7xl mx-auto px-6 lg:px-8 h-full flex flex-col justify-center">
                    <div class="max-w-2xl">
                        <h1 class="text-5xl md:text-6xl font-light tracking-tight text-white mb-6 leading-tight">
                            Your perfect stay<br />
                            <span class="text-gray-300">in Abuja</span>
                        </h1>
                        <p class="text-xl text-gray-200 font-light mb-12">
                            Premium short-let apartments across Asokoro, Maitama, and Wuse
                        </p>
                    </div>

                    <!-- Search Card -->
                    <div class="bg-white dark:bg-gray-900 rounded-3xl p-8 shadow-2xl max-w-4xl">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Dates -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Dates
                                </label>
                                <flat-pickr
                                    v-model="dateRange"
                                    :config="dateConfig"
                                    placeholder="Check-in → Check-out"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white focus:border-transparent"
                                />
                            </div>

                            <!-- Guests -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Guests
                                </label>
                                <select
                                    v-model="guests"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white focus:border-transparent"
                                >
                                    <option :value="1">1 guest</option>
                                    <option :value="2">2 guests</option>
                                    <option :value="4">4 guests</option>
                                    <option :value="6">6 guests</option>
                                    <option :value="8">8+ guests</option>
                                </select>
                            </div>
                        </div>

                        <!-- Search Button -->
                        <div class="mt-4">
                            <button
                                @click="searchProperties"
                                class="w-full bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 text-white dark:text-gray-900 font-medium py-3 px-6 rounded-xl transition-all"
                            >
                                Search properties
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Featured Destinations -->
        <div class="bg-gray-50 dark:bg-gray-900/50 py-24">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="mb-12">
                    <h2 class="text-3xl md:text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-4">
                        Explore by location
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400 font-light">
                        Discover our properties across Abuja's finest neighborhoods
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <Link
                        v-for="destination in featuredDestinations"
                        :key="destination.name"
                        :href="route('properties.index')"
                        class="group relative overflow-hidden rounded-2xl aspect-[4/3] bg-gray-200 dark:bg-gray-800"
                    >
                        <img
                            :src="destination.image"
                            :alt="destination.name"
                            class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
                            <h3 class="text-2xl font-medium mb-2">{{ destination.name }}</h3>
                            <p class="text-sm text-gray-200">{{ destination.description }}</p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Why Choose Us -->
        <div class="bg-white dark:bg-gray-950 py-24">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-4">
                        Why stay with us
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400 font-light">
                        Experience the difference with CityStake
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                    <div class="text-center">
                        <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-6">
                            <svg class="w-8 h-8 text-gray-900 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">Prime locations</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Carefully selected properties in Asokoro, Maitama, and Wuse
                        </p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-6">
                            <svg class="w-8 h-8 text-gray-900 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">Secure booking</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Safe payments with Paystack and instant confirmation
                        </p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-6">
                            <svg class="w-8 h-8 text-gray-900 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">Premium amenities</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            WiFi, gym, pool, 24/7 security, and more in every property
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="bg-gray-900 dark:bg-gray-950 py-24">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-light tracking-tight text-white mb-6">
                    Ready to find your perfect stay?
                </h2>
                <p class="text-xl text-gray-300 mb-10 max-w-2xl mx-auto">
                    Browse our collection of premium apartments and book your next Abuja experience
                </p>
                <Link
                    :href="route('properties.index')"
                    class="inline-flex items-center px-8 py-4 bg-white hover:bg-gray-100 text-gray-900 text-base font-medium rounded-full transition-all group"
                >
                    View all properties
                    <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
