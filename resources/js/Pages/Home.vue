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

const formatPrice = (price) => {
    if (!price) return '0';
    return new Intl.NumberFormat('en-NG').format(price);
};

const dateRange = ref(null);
const checkIn = ref('');
const checkOut = ref('');
const guests = ref(2);

const dateConfig = {
    mode: 'range',
    dateFormat: 'd M Y',
    minDate: 'today',
    onChange: (selectedDates) => {
        if (selectedDates.length === 2) {
            const formatLocalDate = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };
            checkIn.value = formatLocalDate(selectedDates[0]);
            checkOut.value = formatLocalDate(selectedDates[1]);
        }
    }
};

const searchProperties = () => {
    if (!checkIn.value || !checkOut.value) {
        router.get(route('properties.index'));
        return;
    }
    router.get(route('properties.index'), {
        check_in: checkIn.value,
        check_out: checkOut.value,
        guests: guests.value
    });
};

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

const featuredProperties = computed(() => props.buildings.slice(0, 3));
</script>

<template>
    <AppLayout>
        <Head title="Exclusive Apartment Stays in Abuja" />

        <!-- ─── Hero ─────────────────────────────────────────────── -->
        <div class="relative min-h-[700px] bg-gray-950 overflow-hidden flex flex-col justify-center">

            <!-- Bold editorial image — opacity-40, not ghosted at 15% -->
            <img
                src="https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=1600&h=900&fit=crop&q=80"
                alt=""
                fetchpriority="high"
                class="absolute inset-0 w-full h-full object-cover object-center opacity-40"
                aria-hidden="true"
            />

            <!-- Single clean gradient — no pulsing grid -->
            <div class="absolute inset-0 bg-gradient-to-b from-gray-950/60 via-gray-950/40 to-gray-950/90"></div>

            <!-- Thin gold accent line at very top -->
            <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-amber-400/60 to-transparent"></div>

            <div class="relative max-w-7xl mx-auto px-6 lg:px-8 py-20 w-full">

                <!-- Stats row with dividers -->
                <div class="mb-10 flex items-center text-white/70 w-fit">
                    <div class="flex items-center gap-2 pr-6">
                        <Building2 class="w-4 h-4 text-amber-400" />
                        <span class="text-sm font-light tracking-wide">{{ stats.total_properties }}+ Properties</span>
                    </div>
                    <div class="w-px h-4 bg-white/20"></div>
                    <div class="flex items-center gap-2 px-6">
                        <Users class="w-4 h-4 text-amber-400" />
                        <span class="text-sm font-light tracking-wide">{{ stats.happy_guests }}+ Happy Guests</span>
                    </div>
                    <div class="w-px h-4 bg-white/20"></div>
                    <div class="flex items-center gap-2 pl-6">
                        <MapPin class="w-4 h-4 text-amber-400" />
                        <span class="text-sm font-light tracking-wide">{{ stats.locations }} Prime Locations</span>
                    </div>
                </div>

                <!-- Headline — clean white, amber accent word, no weak gradient -->
                <div class="max-w-3xl mb-10">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-amber-400/10 border border-amber-400/30 rounded-full text-amber-300 text-xs font-medium tracking-widest uppercase mb-6">
                        <Star class="w-3.5 h-3.5 fill-amber-400 text-amber-400" />
                        5-star rated properties
                    </div>

                    <h1 class="text-6xl md:text-7xl font-extralight tracking-tight text-white leading-[1.05] mb-5">
                        Your perfect<br />
                        <span class="text-amber-300 font-light">Abuja stay</span>
                    </h1>
                    <p class="text-lg text-gray-300 font-light leading-relaxed max-w-xl">
                        Premium short-let apartments across Abuja's most prestigious neighborhoods.
                        Book instantly with best price guarantee.
                    </p>
                </div>

                <!-- Search card — branded top accent, cleaner layout -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-4xl border border-gray-200/80 dark:border-gray-800 overflow-hidden">
                    <!-- Gold accent top border -->
                    <div class="h-0.5 bg-gradient-to-r from-amber-400 via-amber-300 to-amber-500"></div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                            <div class="md:col-span-5">
                                <label class="block text-xs font-medium text-gray-400 dark:text-gray-500 mb-1.5 tracking-widest uppercase flex items-center gap-1.5">
                                    <Calendar class="w-3.5 h-3.5" />
                                    Dates
                                </label>
                                <flat-pickr
                                    v-model="dateRange"
                                    :config="dateConfig"
                                    placeholder="Check-in → Check-out"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-400/40 focus:border-amber-400 transition-all"
                                />
                            </div>

                            <div class="md:col-span-3">
                                <label class="block text-xs font-medium text-gray-400 dark:text-gray-500 mb-1.5 tracking-widest uppercase flex items-center gap-1.5">
                                    <Users class="w-3.5 h-3.5" />
                                    Guests
                                </label>
                                <select
                                    v-model="guests"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-400/40 focus:border-amber-400 appearance-none cursor-pointer transition-all"
                                >
                                    <option :value="1">1 guest</option>
                                    <option :value="2">2 guests</option>
                                    <option :value="4">4 guests</option>
                                    <option :value="6">6 guests</option>
                                    <option :value="8">8+ guests</option>
                                </select>
                            </div>

                            <div class="md:col-span-4">
                                <button
                                    @click="searchProperties"
                                    class="w-full bg-gray-950 dark:bg-amber-400 hover:bg-gray-800 dark:hover:bg-amber-300 text-white dark:text-gray-900 font-medium py-3 px-6 rounded-xl transition-all flex items-center justify-center gap-2 group"
                                >
                                    <Search class="w-4 h-4" />
                                    Search
                                    <ArrowRight class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Featured Properties ───────────────────────────────── -->
        <div class="bg-white dark:bg-gray-950 py-24">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="flex items-end justify-between mb-12">
                    <div>
                        <p class="text-xs font-medium text-amber-500 tracking-widest uppercase mb-3">Curated Selection</p>
                        <h2 class="text-4xl md:text-5xl font-extralight tracking-tight text-gray-900 dark:text-white mb-3">
                            Featured properties
                        </h2>
                        <p class="text-base text-gray-500 dark:text-gray-400 font-light">
                            Handpicked apartments for your perfect stay
                        </p>
                    </div>
                    <Link
                        :href="route('properties.index')"
                        class="hidden md:flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors group"
                    >
                        View all
                        <ArrowRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Unified card — image + info as one hover unit -->
                    <div
                        v-for="building in featuredProperties"
                        :key="building.id"
                        @click="router.get(route('properties.index', { building: building.slug }))"
                        class="group cursor-pointer rounded-2xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden hover:shadow-xl hover:shadow-gray-200/60 dark:hover:shadow-black/40 hover:-translate-y-1 transition-all duration-300"
                    >
                        <!-- Image -->
                        <div class="relative overflow-hidden aspect-[4/3] bg-gray-100 dark:bg-gray-800">
                            <img
                                :src="building.primary_image"
                                :alt="building.name"
                                loading="lazy"
                                class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>

                            <!-- Amber premium badge -->
                            <div class="absolute top-3 left-3">
                                <div class="px-2.5 py-1 bg-amber-400 rounded-full text-xs font-semibold text-gray-900 flex items-center gap-1">
                                    <Star class="w-3 h-3 fill-gray-900 text-gray-900" />
                                    Premium
                                </div>
                            </div>

                            <div class="absolute bottom-3 left-3 flex items-center gap-1.5 text-white">
                                <MapPin class="w-3.5 h-3.5" />
                                <span class="text-xs font-light">{{ building.city }}</span>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="p-5">
                            <h3 class="text-base font-medium text-gray-900 dark:text-white mb-1 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                                {{ building.name }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 font-light mb-4 line-clamp-2">
                                {{ building.description }}
                            </p>
                            <div class="flex items-end justify-between">
                                <div>
                                    <span class="text-xl font-semibold text-gray-900 dark:text-white">₦{{ formatPrice(building.starting_price) }}</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500 ml-1">/night</span>
                                </div>
                                <div class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                                    <BedDouble class="w-3.5 h-3.5" />
                                    <span>{{ building.unit_types.length }} type{{ building.unit_types.length !== 1 ? 's' : '' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- View All (Mobile) -->
                <div class="mt-10 md:hidden text-center">
                    <Link
                        :href="route('properties.index')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-xl transition-all group"
                    >
                        View all properties
                        <ArrowRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                    </Link>
                </div>
            </div>
        </div>

        <!-- ─── Explore by Location ───────────────────────────────── -->
        <div class="bg-gray-50 dark:bg-gray-900/50 py-24">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="mb-12 text-center">
                    <p class="text-xs font-medium text-amber-500 tracking-widest uppercase mb-3">Browse by District</p>
                    <h2 class="text-4xl md:text-5xl font-extralight tracking-tight text-gray-900 dark:text-white mb-3">
                        Explore by location
                    </h2>
                    <p class="text-base text-gray-500 dark:text-gray-400 font-light">
                        Discover our properties across Abuja's finest neighborhoods
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Link
                        v-for="destination in featuredDestinations"
                        :key="destination.name"
                        :href="route('properties.index', { location: destination.name })"
                        class="group relative overflow-hidden rounded-2xl aspect-[4/3] bg-gray-200 dark:bg-gray-800"
                    >
                        <img
                            :src="destination.image"
                            :alt="destination.name"
                            loading="lazy"
                            class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>

                        <div class="absolute inset-0 flex flex-col justify-end p-6 text-white">
                            <div class="flex items-center gap-2 mb-1">
                                <MapPin class="w-4 h-4 text-amber-400" />
                                <h3 class="text-2xl font-light">{{ destination.name }}</h3>
                            </div>
                            <p class="text-xs text-gray-300 font-light mb-3">
                                {{ destination.buildings.length }} premium {{ destination.buildings.length === 1 ? 'property' : 'properties' }}
                            </p>
                            <div class="flex items-center gap-2 text-xs text-amber-300 opacity-0 group-hover:opacity-100 translate-y-1 group-hover:translate-y-0 transition-all duration-300">
                                <span class="tracking-wider uppercase font-medium">Explore</span>
                                <ArrowRight class="w-3.5 h-3.5" />
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <!-- ─── Why Stay With Us ──────────────────────────────────── -->
        <div class="bg-white dark:bg-gray-950 py-24">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="text-center mb-16">
                    <p class="text-xs font-medium text-amber-500 tracking-widest uppercase mb-3">Our Promise</p>
                    <h2 class="text-4xl md:text-5xl font-extralight tracking-tight text-gray-900 dark:text-white mb-3">
                        Why stay with us
                    </h2>
                    <p class="text-base text-gray-500 dark:text-gray-400 font-light max-w-xl mx-auto">
                        Experience premium hospitality with our carefully curated apartments
                    </p>
                </div>

                <!-- Monochromatic feature cards — icon color animates to amber on hover -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="group p-8 rounded-2xl border border-gray-100 dark:border-gray-800 hover:border-amber-200 dark:hover:border-amber-900/50 hover:shadow-lg hover:shadow-amber-50 dark:hover:shadow-amber-900/10 transition-all duration-300">
                        <div class="w-12 h-12 rounded-xl bg-gray-950 dark:bg-white flex items-center justify-center mb-6 group-hover:bg-amber-400 transition-colors duration-300">
                            <MapPin class="w-5 h-5 text-white dark:text-gray-900" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Prime Locations</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-light leading-relaxed">
                            Properties in Asokoro, Maitama, and Wuse - Abuja's most prestigious addresses
                        </p>
                    </div>

                    <div class="group p-8 rounded-2xl border border-gray-100 dark:border-gray-800 hover:border-amber-200 dark:hover:border-amber-900/50 hover:shadow-lg hover:shadow-amber-50 dark:hover:shadow-amber-900/10 transition-all duration-300">
                        <div class="w-12 h-12 rounded-xl bg-gray-950 dark:bg-white flex items-center justify-center mb-6 group-hover:bg-amber-400 transition-colors duration-300">
                            <Shield class="w-5 h-5 text-white dark:text-gray-900" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Secure Booking</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-light leading-relaxed">
                            Safe payments, instant confirmation, and 24/7 support throughout your stay
                        </p>
                    </div>

                    <div class="group p-8 rounded-2xl border border-gray-100 dark:border-gray-800 hover:border-amber-200 dark:hover:border-amber-900/50 hover:shadow-lg hover:shadow-amber-50 dark:hover:shadow-amber-900/10 transition-all duration-300">
                        <div class="w-12 h-12 rounded-xl bg-gray-950 dark:bg-white flex items-center justify-center mb-6 group-hover:bg-amber-400 transition-colors duration-300">
                            <Sparkles class="w-5 h-5 text-white dark:text-gray-900" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Premium Amenities</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-light leading-relaxed">
                            High-speed WiFi, gym, pool, 24/7 security, and modern furnishings throughout
                        </p>
                    </div>
                </div>

                <!-- Trust badges — above a real border, not faded out -->
                <div class="mt-14 pt-10 border-t border-gray-100 dark:border-gray-800 grid grid-cols-2 md:grid-cols-4 gap-6 items-center justify-items-center">
                    <div class="flex items-center gap-2">
                        <CheckCircle class="w-4 h-4 text-amber-500" />
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400 tracking-wide">Verified Properties</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <CheckCircle class="w-4 h-4 text-amber-500" />
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400 tracking-wide">24/7 Support</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <CheckCircle class="w-4 h-4 text-amber-500" />
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400 tracking-wide">Best Price Guarantee</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <CheckCircle class="w-4 h-4 text-amber-500" />
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400 tracking-wide">Instant Confirmation</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── CTA ───────────────────────────────────────────────── -->
        <!-- Solid dark, distinct from hero — no repeated gradient -->
        <div class="relative bg-gray-950 py-28 overflow-hidden">
            <!-- Single subtle ambient glow — not two mirrored blobs -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="w-[600px] h-[300px] bg-amber-400/5 rounded-full blur-3xl"></div>
            </div>
            <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-amber-400/40 to-transparent"></div>

            <div class="relative max-w-3xl mx-auto px-6 lg:px-8 text-center">
                <p class="text-xs font-medium text-amber-500 tracking-widest uppercase mb-4">Book Your Stay</p>
                <h2 class="text-4xl md:text-5xl font-extralight tracking-tight text-white mb-5">
                    Ready to experience<br />Abuja's finest?
                </h2>
                <p class="text-base text-gray-400 mb-10 max-w-xl mx-auto font-light leading-relaxed">
                    Browse our collection of premium apartments and book your perfect stay today
                </p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <Link
                        :href="route('properties.index')"
                        class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-amber-400 hover:bg-amber-300 text-gray-900 text-sm font-semibold rounded-xl transition-all group"
                    >
                        View all properties
                        <ArrowRight class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
                    </Link>
                    <Link
                        :href="route('contact')"
                        class="inline-flex items-center justify-center px-8 py-3.5 border border-gray-700 hover:border-gray-500 text-gray-300 hover:text-white text-sm font-medium rounded-xl transition-all"
                    >
                        Contact us
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
