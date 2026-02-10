<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <AppLayout>
        <!-- Hero Section - Clean & Spacious -->
        <div class="relative">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="pt-24 pb-32 md:pt-32 md:pb-40">
                    <div class="max-w-3xl">
                        <h1 class="text-5xl md:text-7xl font-light tracking-tight text-gray-900 dark:text-white mb-6 leading-tight">
                            Luxury living,<br />
                            <span class="text-gray-400 dark:text-gray-500">simplified</span>
                        </h1>
                        <p class="text-xl md:text-2xl text-gray-600 dark:text-gray-400 font-light leading-relaxed mb-12">
                            Premium short-let apartments in Lagos' most sought-after locations.
                        </p>
                        <Link
                            :href="route('properties.index')"
                            class="inline-flex items-center px-8 py-4 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-base font-medium rounded-full hover:bg-gray-800 dark:hover:bg-gray-100 transition-all group"
                        >
                            Explore properties
                            <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features - Minimal Cards -->
        <div class="bg-gray-50 dark:bg-gray-900/50 py-24">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                    <div class="group">
                        <div class="w-12 h-12 rounded-full bg-gray-900 dark:bg-white flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white dark:text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">Prime locations</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Handpicked properties in Lekki, Victoria Island, and Ikoyi.
                        </p>
                    </div>

                    <div class="group">
                        <div class="w-12 h-12 rounded-full bg-gray-900 dark:bg-white flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white dark:text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">Secure booking</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Safe payments powered by Paystack with instant confirmation.
                        </p>
                    </div>

                    <div class="group">
                        <div class="w-12 h-12 rounded-full bg-gray-900 dark:bg-white flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white dark:text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">Premium amenities</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Fully furnished with high-speed WiFi, gym, pool, and 24/7 security.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
