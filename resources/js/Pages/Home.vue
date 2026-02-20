<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    MapPin,
    Shield,
    Sparkles,
    Search,
    ArrowRight,
    Star,
    Users,
    Building2,
    CheckCircle,
    BedDouble,
    Calendar
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import flatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';

const props = defineProps({
    buildings: Array,
    stats: Object,
});

// Helper function
const formatPrice = (price) => {
    if (!price) return '0';
    return new Intl.NumberFormat('en-NG').format(price);
};

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

// Group buildings by city for featured destinations
const featuredDestinations = computed(() => {
    const cities = {};
    props.buildings.forEach(building => {
        if (!cities[building.city]) {
            cities[building.city] = {
                name: building.city,
                buildings: [],
                image: building.primary_image,
            };
        }
        cities[building.city].buildings.push(building);
    });
    return Object.values(cities);
});

// Get featured properties (first 3)
const featuredProperties = computed(() => props.buildings.slice(0, 3));
</script>

<template>
    <AppLayout>
        <Head title="Exclusive Apartment Stays in Abuja" />

        <!-- Hero Section with Search -->
        <div class="relative bg-white dark:bg-gray-950">
            <div class="relative h-[700px] bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 dark:from-gray-950 dark:to-gray-900 overflow-hidden">
                <!-- Animated Background Pattern -->
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] animate-pulse"></div>
                </div>

                <!-- Hero Image Overlay -->
                <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=1600&h=900&fit=crop')] bg-cover bg-center opacity-15"></div>

                <div class="relative max-w-7xl mx-auto px-6 lg:px-8 h-full flex flex-col justify-center">
                    <!-- Stats Bar at Top -->
                    <div class="mb-8 flex items-center gap-8 text-white/80">
                        <div class="flex items-center gap-2">
                            <Building2 class="w-5 h-5" />
                            <span class="text-sm font-light">{{ stats.total_properties }}+ Properties</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Users class="w-5 h-5" />
                            <span class="text-sm font-light">{{ stats.happy_guests }}+ Happy Guests</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <MapPin class="w-5 h-5" />
                            <span class="text-sm font-light">{{ stats.locations }} Prime Locations</span>
                        </div>
                    </div>

                    <div class="max-w-3xl">
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full text-white text-sm font-light mb-6 border border-white/20">
                            <Star class="w-4 h-4 fill-yellow-400 text-yellow-400" />
                            <span>5-star rated properties</span>
                        </div>

                        <h1 class="text-6xl md:text-7xl font-light tracking-tight text-white mb-6 leading-tight">
                            Your perfect<br />
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-400">Abuja stay</span>
                        </h1>
                        <p class="text-xl text-gray-200 font-light mb-12 max-w-2xl">
                            Premium short-let apartments across Abuja's most prestigious neighborhoods. Book instantly with best price guarantee.
                        </p>
                    </div>

                    <!-- Enhanced Search Card -->
                    <div class="bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl rounded-3xl p-8 shadow-2xl max-w-5xl border border-gray-200/50 dark:border-gray-800/50">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                            <!-- Dates -->
                            <div class="md:col-span-5">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                                    <Calendar class="w-4 h-4" />
                                    When
                                </label>
                                <flat-pickr
                                    v-model="dateRange"
                                    :config="dateConfig"
                                    placeholder="Check-in → Check-out"
                                    class="w-full px-4 py-4 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-2xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white focus:border-transparent transition-all"
                                />
                            </div>

                            <!-- Guests -->
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                                    <Users class="w-4 h-4" />
                                    Guests
                                </label>
                                <select
                                    v-model="guests"
                                    class="w-full px-4 py-4 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-2xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white focus:border-transparent appearance-none cursor-pointer transition-all"
                                >
                                    <option :value="1">1 guest</option>
                                    <option :value="2">2 guests</option>
                                    <option :value="4">4 guests</option>
                                    <option :value="6">6 guests</option>
                                    <option :value="8">8+ guests</option>
                                </select>
                            </div>

                            <!-- Search Button -->
                            <div class="md:col-span-4">
                                <button
                                    @click="searchProperties"
                                    class="w-full bg-gradient-to-r from-gray-900 to-gray-800 dark:from-white dark:to-gray-100 hover:from-gray-800 hover:to-gray-700 dark:hover:from-gray-100 dark:hover:to-white text-white dark:text-gray-900 font-semibold py-4 px-6 rounded-2xl transition-all flex items-center justify-center group shadow-lg hover:shadow-xl"
                                >
                                    <Search class="w-5 h-5 mr-2" />
                                    Search
                                    <ArrowRight class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Featured Properties -->
        <div class="bg-white dark:bg-gray-950 py-24">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="flex items-end justify-between mb-12">
                    <div>
                        <h2 class="text-4xl md:text-5xl font-light tracking-tight text-gray-900 dark:text-white mb-4">
                            Featured properties
                        </h2>
                        <p class="text-lg text-gray-600 dark:text-gray-400 font-light">
                            Handpicked apartments for your perfect stay
                        </p>
                    </div>
                    <Link
                        :href="route('properties.index')"
                        class="hidden md:flex items-center text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300 transition-colors group"
                    >
                        View all
                        <ArrowRight class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" />
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div
                        v-for="building in featuredProperties"
                        :key="building.id"
                        class="group cursor-pointer"
                    >
                        <!-- Property Card -->
                        <div
                            @click="router.get(route('properties.index', { building: building.slug }))"
                            class="relative overflow-hidden rounded-3xl aspect-[4/3] bg-gray-200 dark:bg-gray-800 mb-4"
                        >
                            <img
                                :src="building.primary_image"
                                :alt="building.name"
                                class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

                            <!-- Badge -->
                            <div class="absolute top-4 left-4">
                                <div class="px-3 py-1.5 bg-white/95 backdrop-blur-sm rounded-full text-xs font-medium text-gray-900 flex items-center gap-1.5">
                                    <Star class="w-3.5 h-3.5 fill-yellow-400 text-yellow-400" />
                                    Premium
                                </div>
                            </div>

                            <!-- Location -->
                            <div class="absolute bottom-4 left-4 right-4">
                                <div class="flex items-center gap-2 text-white mb-2">
                                    <MapPin class="w-4 h-4" />
                                    <span class="text-sm font-light">{{ building.city }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Property Info -->
                        <div @click="router.get(route('properties.index', { building: building.slug }))">
                            <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors">
                                {{ building.name }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                                {{ building.description }}
                            </p>
                            <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-semibold text-gray-900 dark:text-white">
                            ₦{{ formatPrice(building.starting_price) }}
                        </span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">/night</span>
                            </div>
                            <div class="mt-3 flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                                <div class="flex items-center gap-1.5">
                                    <BedDouble class="w-4 h-4" />
                                    <span>{{ building.unit_types.length }} unit type{{ building.unit_types.length !== 1 ? 's' : '' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- View All Button (Mobile) -->
                <div class="mt-12 md:hidden text-center">
                    <Link
                        :href="route('properties.index')"
                        class="inline-flex items-center px-6 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium rounded-full transition-all group"
                    >
                        View all properties
                        <ArrowRight class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" />
                    </Link>
                </div>
            </div>
        </div>

        <!-- Explore by Location -->
        <div class="bg-gray-50 dark:bg-gray-900/50 py-24">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="mb-12 text-center">
                    <h2 class="text-4xl md:text-5xl font-light tracking-tight text-gray-900 dark:text-white mb-4">
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
                        :href="route('properties.index', { location: destination.name })"
                        class="group relative overflow-hidden rounded-3xl aspect-[4/3] bg-gray-200 dark:bg-gray-800"
                    >
                        <img
                            :src="destination.image"
                            :alt="destination.name"
                            class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>

                        <!-- Content -->
                        <div class="absolute inset-0 flex flex-col justify-end p-6 text-white">
                            <h3 class="text-3xl font-light mb-2 flex items-center gap-2">
                                <MapPin class="w-6 h-6" />
                                {{ destination.name }}
                            </h3>
                            <p class="text-sm text-gray-200 mb-3">
                                {{ destination.buildings.length }} premium {{ destination.buildings.length === 1 ? 'property' : 'properties' }}
                            </p>
                            <div class="flex items-center gap-2 text-sm opacity-0 group-hover:opacity-100 transition-opacity">
                                <span>Explore</span>
                                <ArrowRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Why Choose Us -->
        <div class="bg-white dark:bg-gray-950 py-24">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-light tracking-tight text-gray-900 dark:text-white mb-4">
                        Why stay with us
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400 font-light max-w-2xl mx-auto">
                        Experience premium hospitality with our carefully curated apartments
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                    <div class="text-center group">
                        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                            <MapPin class="w-10 h-10 text-blue-600 dark:text-blue-400" />
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Prime Locations</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Properties in Asokoro, Maitama, and Wuse - Abuja's most prestigious addresses
                        </p>
                    </div>

                    <div class="text-center group">
                        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                            <Shield class="w-10 h-10 text-green-600 dark:text-green-400" />
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Secure Booking</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Safe payments, instant confirmation, and 24/7 support
                        </p>
                    </div>

                    <div class="text-center group">
                        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                            <Sparkles class="w-10 h-10 text-purple-600 dark:text-purple-400" />
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Premium Amenities</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            High-speed WiFi, gym, pool, 24/7 security, and modern furnishings
                        </p>
                    </div>
                </div>

                <!-- Trust Badges -->
                <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-8 items-center justify-items-center opacity-60">
                    <div class="flex items-center gap-2">
                        <CheckCircle class="w-5 h-5 text-green-600 dark:text-green-400" />
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Verified Properties</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <CheckCircle class="w-5 h-5 text-green-600 dark:text-green-400" />
                        <span class="text-sm font-medium text-gray-900 dark:text-white">24/7 Support</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <CheckCircle class="w-5 h-5 text-green-600 dark:text-green-400" />
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Best Price Guarantee</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <CheckCircle class="w-5 h-5 text-green-600 dark:text-green-400" />
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Instant Confirmation</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="relative bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 dark:from-gray-950 dark:to-gray-900 py-24 overflow-hidden">
            <!-- Decorative Elements -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl"></div>
            </div>

            <div class="relative max-w-4xl mx-auto px-6 lg:px-8 text-center">
                <h2 class="text-4xl md:text-5xl font-light tracking-tight text-white mb-6">
                    Ready to experience Abuja's finest?
                </h2>
                <p class="text-xl text-gray-300 mb-10 max-w-2xl mx-auto font-light">
                    Browse our collection of premium apartments and book your perfect stay today
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link
                        :href="route('properties.index')"
                        class="inline-flex items-center px-8 py-4 bg-white hover:bg-gray-100 text-gray-900 text-base font-semibold rounded-full transition-all group shadow-lg"
                    >
                        View all properties
                        <ArrowRight class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" />
                    </Link>
                    <Link
                        :href="route('contact')"
                        class="inline-flex items-center px-8 py-4 border-2 border-white text-white hover:bg-white hover:text-gray-900 text-base font-semibold rounded-full transition-all"
                    >
                        Contact us
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@keyframes pulse {
    0%, 100% {
        opacity: 0.1;
    }
    50% {
        opacity: 0.2;
    }
}

.animate-pulse {
    animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
