<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Home, Award, Shield, Heart } from 'lucide-vue-next';
</script>

<template>
    <AppLayout>
        <Head title="About Us" />

        <div class="bg-white dark:bg-gray-950 min-h-screen">
            <!-- Hero Section -->
            <div class="bg-gradient-to-br from-gray-900 to-gray-800 dark:from-gray-950 dark:to-gray-900 text-white py-20">
                <div class="max-w-7xl mx-auto px-6 lg:px-8">
                    <div class="max-w-3xl">
                        <h1 class="text-5xl font-light tracking-tight mb-6">
                            About {{ $page.props.appName }}
                        </h1>
                        <p class="text-xl text-gray-300 leading-relaxed">
                            Premium apartment rentals in Abuja's most prestigious neighborhoods.
                            We provide exceptional accommodation experiences for business travelers,
                            expatriates, and visitors seeking comfort and convenience.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Our Story -->
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-20">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                    <div>
                        <h2 class="text-3xl font-light tracking-tight text-gray-900 dark:text-white mb-6">
                            Our Story
                        </h2>
                        <div class="space-y-4 text-lg text-gray-600 dark:text-gray-400">
                            <p>
                                Founded with a vision to redefine short-term accommodation in Abuja,
                                {{ $page.props.appName }} has grown to become a trusted name in premium apartment rentals.
                            </p>
                            <p>
                                We understand that whether you're traveling for business, relocating, or
                                simply seeking a comfortable place to call home temporarily, you deserve
                                nothing but the best. That's why we've carefully curated our portfolio
                                of properties in Abuja's finest locations.
                            </p>
                            <p>
                                Each of our apartments is meticulously maintained, fully furnished, and
                                equipped with modern amenities to ensure your stay is comfortable,
                                convenient, and memorable.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div class="bg-gray-50 dark:bg-gray-900 p-8 rounded-2xl border border-gray-200 dark:border-gray-800">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-xl flex items-center justify-center mb-4">
                                <Home class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <h3 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">50+</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Premium Units</p>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900 p-8 rounded-2xl border border-gray-200 dark:border-gray-800">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-xl flex items-center justify-center mb-4">
                                <Award class="w-6 h-6 text-green-600 dark:text-green-400" />
                            </div>
                            <h3 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">5 Star</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Guest Rating</p>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900 p-8 rounded-2xl border border-gray-200 dark:border-gray-800">
                            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/20 rounded-xl flex items-center justify-center mb-4">
                                <Shield class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <h3 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">24/7</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Security</p>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900 p-8 rounded-2xl border border-gray-200 dark:border-gray-800">
                            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/20 rounded-xl flex items-center justify-center mb-4">
                                <Heart class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                            </div>
                            <h3 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">1000+</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Happy Guests</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Our Values -->
            <div class="bg-gray-50 dark:bg-gray-900 py-20">
                <div class="max-w-7xl mx-auto px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl font-light tracking-tight text-gray-900 dark:text-white mb-4">
                            Our Values
                        </h2>
                        <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                            The principles that guide everything we do
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="bg-white dark:bg-gray-950 p-8 rounded-2xl border border-gray-200 dark:border-gray-800">
                            <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-4">
                                Excellence
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                We maintain the highest standards in property quality, cleanliness,
                                and guest service. Excellence isn't just a goal—it's our baseline.
                            </p>
                        </div>

                        <div class="bg-white dark:bg-gray-950 p-8 rounded-2xl border border-gray-200 dark:border-gray-800">
                            <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-4">
                                Integrity
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                Transparency and honesty in all our dealings. What you see is what
                                you get—no hidden fees, no surprises, just straightforward service.
                            </p>
                        </div>

                        <div class="bg-white dark:bg-gray-950 p-8 rounded-2xl border border-gray-200 dark:border-gray-800">
                            <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-4">
                                Hospitality
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                We treat every guest like family. From the moment you book to the
                                day you check out, your comfort and satisfaction are our priorities.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Locations -->
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-20">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-light tracking-tight text-gray-900 dark:text-white mb-4">
                        Prime Locations
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                        Our properties are strategically located in Abuja's most prestigious areas
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl">🏛️</span>
                        </div>
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">Asokoro</h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Diplomatic enclave with embassies and high-profile residences
                        </p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl">🌳</span>
                        </div>
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">Maitama</h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Upscale district known for its serenity and affluent community
                        </p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl">🏢</span>
                        </div>
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">Wuse</h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Central business district with easy access to commerce and entertainment
                        </p>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="bg-gray-900 dark:bg-gray-950 text-white py-20">
                <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
                    <h2 class="text-3xl font-light tracking-tight mb-6">
                        Ready to Experience Premium Living?
                    </h2>
                    <p class="text-xl text-gray-300 mb-8">
                        Browse our collection of carefully curated apartments and find your perfect stay
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">

                        <a href="/"
                        class="inline-flex items-center justify-center px-8 py-4 bg-white text-gray-900 font-medium rounded-full hover:bg-gray-100 transition-all"
                        >
                        Browse Properties
                        </a>

                        <a href="/contact"
                        class="inline-flex items-center justify-center px-8 py-4 border-2 border-white text-white font-medium rounded-full hover:bg-white hover:text-gray-900 transition-all"
                        >
                        Contact Us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
