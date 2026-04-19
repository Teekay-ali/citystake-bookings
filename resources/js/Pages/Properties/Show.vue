<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import {
    Calendar,
    Users, AlertCircle,
    Home, CheckCircle,
    Wifi, ChevronLeft, ChevronRight,
    Wind, X, Navigation,
    Car,
    Waves,
    Dumbbell,
    Shield,
    Coffee,
    Tv,
    UtensilsCrossed,
    WashingMachine,
    Check,
    Info,
    ArrowLeft,
    MapPin,
    Sparkles,
    Cigarette,    // For no smoking
    Dog,           // For no pets
    PartyPopper,   // For no parties
    Clock,         // For check-in/out times
    Volume2,       // For noise/quiet hours
    Baby           // For children rules
} from 'lucide-vue-next';
import { ref, computed, nextTick } from 'vue';
import flatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';

const props = defineProps({
    building: Object,
    unitType: Object,
    userBooking: Object,
    similarProperties: Array,
});



const isReserving = ref(false);
const toast = useToast();

const lightboxOpen = ref(false);
const lightboxIndex = ref(0);

const lightboxEl = ref(null);

const openLightbox = (index) => {
    lightboxIndex.value = index;
    lightboxOpen.value = true;
    nextTick(() => lightboxEl.value?.focus());
};

const closeLightbox = () => { lightboxOpen.value = false; };
const lightboxPrev = () => { lightboxIndex.value = (lightboxIndex.value - 1 + props.unitType.images.length) % props.unitType.images.length; };
const lightboxNext = () => { lightboxIndex.value = (lightboxIndex.value + 1) % props.unitType.images.length; };

const selectedImage = ref(props.unitType.images[0]?.image_path || null);
const guests = ref(2);
const isCheckingAvailability = ref(false);
const availabilityMessage = ref('');
const availableUnitsCount = ref(0);

const dateRange = ref(null);
const checkIn = ref('');
const checkOut = ref('');


const dateConfig = {
    mode: 'range',
    dateFormat: 'd M Y',
    minDate: 'today',
    onClose: (selectedDates) => {
        if (selectedDates.length === 2) {
            // Fix timezone issue by using local date string
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
    if (calculateNights.value === 0) return 0

    const subtotal = calculateNights.value * parseFloat(props.unitType.base_price_per_night)
    const cleaning = parseFloat(props.unitType.cleaning_fee) || 0
    const service  = subtotal * ((parseFloat(props.unitType.service_charge_percent) || 0) / 100)

    // Mirror backend discount rules
    let discountAmount = 0
    if (calculateNights.value >= 5) {
        discountAmount = Math.round(subtotal * 0.05 * 100) / 100
    }

    return (subtotal - discountAmount) + cleaning + service
})

const allAmenities = computed(() => {
    const buildingAmenities = props.building.amenities || [];
    const unitAmenities = props.unitType.specific_amenities || [];
    return [...new Set([...buildingAmenities, ...unitAmenities])];
});

const userBookingBanner = computed(() => {
    if (!props.userBooking) return null;

    const isPending = props.userBooking.payment_status === 'pending';
    const checkIn = new Date(props.userBooking.check_in).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    const checkOut = new Date(props.userBooking.check_out).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

    return { isPending, checkIn, checkOut };
});

const getAmenityIcon = (amenity) => {
    const amenityLower = amenity.toLowerCase();

    if (amenityLower.includes('wifi') || amenityLower.includes('internet')) return Wifi;
    if (amenityLower.includes('air conditioning') || amenityLower.includes('ac')) return Wind;
    if (amenityLower.includes('parking')) return Car;
    if (amenityLower.includes('pool') || amenityLower.includes('swimming')) return Waves;
    if (amenityLower.includes('gym') || amenityLower.includes('fitness')) return Dumbbell;
    if (amenityLower.includes('security')) return Shield;
    if (amenityLower.includes('tv') || amenityLower.includes('television') || amenityLower.includes('netflix')) return Tv;
    if (amenityLower.includes('kitchen') || amenityLower.includes('chef')) return UtensilsCrossed;
    if (amenityLower.includes('washing') || amenityLower.includes('laundry') || amenityLower.includes('dishwasher')) return WashingMachine;
    if (amenityLower.includes('coffee') || amenityLower.includes('breakfast')) return Coffee;
    if (amenityLower.includes('generator') || amenityLower.includes('power')) return Sparkles;

    return Check; // Default icon
};

const getHouseRuleIcon = (rule) => {
    const ruleLower = rule.toLowerCase();

    if (ruleLower.includes('smoking') || ruleLower.includes('smoke')) return Cigarette;
    if (ruleLower.includes('pet') || ruleLower.includes('animal')) return Dog;
    if (ruleLower.includes('party') || ruleLower.includes('parties') || ruleLower.includes('event')) return PartyPopper;
    if (ruleLower.includes('check-in') || ruleLower.includes('check in')) return Clock;
    if (ruleLower.includes('check-out') || ruleLower.includes('check out')) return Clock;
    if (ruleLower.includes('quiet') || ruleLower.includes('noise')) return Volume2;
    if (ruleLower.includes('children') || ruleLower.includes('kids')) return Baby;

    return Info; // Default icon
};

const checkAvailability = async () => {
    if (!checkIn.value || !checkOut.value) {
        availabilityMessage.value = 'Please select check-in and check-out dates';
        toast.warning('Please select your dates first');
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

        if (data.available_units > 0) {
            toast.success(`Great! ${data.available_units} unit(s) available for your dates`);
        } else {
            toast.error('Sorry, no units available for selected dates');
        }
    } catch (error) {
        availabilityMessage.value = 'Error checking availability';
        toast.error('Failed to check availability. Please try again.');
    } finally {
        isCheckingAvailability.value = false;
    }
};

const proceedToBooking = () => {
    if (!checkIn.value || !checkOut.value || guests.value < 1) {
        toast.error('Please select dates and number of guests');
        return;
    }

    if (calculateNights.value < 1) {
        toast.error('Minimum stay is 1 night');
        return;
    }

    isReserving.value = true;

    router.get(route('bookings.create', [props.building.slug, props.unitType.slug]), {
        check_in: checkIn.value,
        check_out: checkOut.value,
        guests: guests.value,
    }, {
        onFinish: () => {
            isReserving.value = false;
        }
    });

};

</script>

<template>
    <AppLayout :hide-footer="true">
        <Head :title="`${unitType.name} - ${building.name}`" />

        <div class="bg-white dark:bg-gray-950 min-h-screen">
            <!-- Back Button -->
            <div class="max-w-7xl mx-auto px-6 lg:px-8 pt-8">
                <Link
                    :href="route('properties.index')"
                    class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors group"
                >
                    <ArrowLeft class="w-4 h-4 mr-2 group-hover:-translate-x-1 transition-transform" />
                    Back to properties
                </Link>
            </div>

            <!-- Image Gallery -->
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-8">
                <div class="grid grid-cols-4 gap-2 rounded-3xl overflow-hidden h-[500px] md:h-[600px]">
                    <!-- Main Image -->
                    <div
                        class="col-span-4 md:col-span-2 md:row-span-2 relative group cursor-pointer"
                        @click="openLightbox(0)"
                    >
                        <img
                            v-if="unitType.images[0]"
                            :src="unitType.images[0].image_path"
                            :alt="unitType.name"
                            fetchpriority="high"
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
                            @click="openLightbox(index + 1)"
                        >
                            <img
                                :src="image.image_path"
                                :alt="unitType.name"
                                loading="lazy"
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

            <!-- Lightbox -->
            <Teleport to="body">
                <div
                    v-if="lightboxOpen"
                    ref="lightboxEl"
                    tabindex="0"
                    class="fixed inset-0 z-50 bg-black/95 flex items-center justify-center outline-none"
                    @click.self="closeLightbox"
                    @keydown.right="lightboxNext"
                    @keydown.left="lightboxPrev"
                    @keydown.esc="closeLightbox"
                >
                    <!-- Close -->
                    <button @click="closeLightbox" class="absolute top-4 right-4 text-white/70 hover:text-white p-2 rounded-full hover:bg-white/10 transition-all">
                        <X class="w-6 h-6" />
                    </button>
                    <!-- Counter -->
                    <span class="absolute top-4 left-1/2 -translate-x-1/2 text-white/60 text-sm">
            {{ lightboxIndex + 1 }} / {{ unitType.images.length }}
        </span>
                    <!-- Prev -->
                    <button
                        v-if="unitType.images.length > 1"
                        @click="lightboxPrev"
                        class="absolute left-4 text-white/70 hover:text-white p-3 rounded-full hover:bg-white/10 transition-all"
                    >
                        <ChevronLeft class="w-7 h-7" />
                    </button>
                    <!-- Image -->
                    <img
                        :src="unitType.images[lightboxIndex]?.image_path"
                        :alt="unitType.name"
                        class="max-h-[85vh] max-w-[90vw] object-contain rounded-xl shadow-2xl"
                    />
                    <!-- Next -->
                    <button
                        v-if="unitType.images.length > 1"
                        @click="lightboxNext"
                        class="absolute right-4 text-white/70 hover:text-white p-3 rounded-full hover:bg-white/10 transition-all"
                    >
                        <ChevronRight class="w-7 h-7" />
                    </button>
                </div>
            </Teleport>

            <!-- Main Content -->
            <div class="max-w-7xl mx-auto px-6 lg:px-8 pb-20 lg:pb-24">
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
                                    <Home class="w-5 h-5 mr-2" />
                                    {{ unitType.bedroom_type }}
                                </span>
                                <span class="flex items-center">
                                    <Users class="w-5 h-5 mr-2" />
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
                                    <component
                                        :is="getAmenityIcon(amenity)"
                                        class="w-5 h-5 text-gray-400"
                                    />
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
                                    <component
                                        :is="getHouseRuleIcon(rule)"
                                        class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0"
                                    />
                                    <span>{{ rule }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Location -->
                        <div>
                            <h2 class="text-2xl font-light text-gray-900 dark:text-white mb-6">Location</h2>
                            <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white mb-1">{{ building.name }}</p>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm">{{ building.address }}</p>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm">{{ building.city }}, Nigeria</p>
                                    </div>

                                    <a :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(building.address + ', ' + building.city + ', Nigeria')}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center px-4 py-2 border border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 text-sm text-gray-700 dark:text-gray-300 rounded-full transition-all flex-shrink-0"
                                    >
                                    <Navigation class="w-4 h-4 mr-2" />
                                    Get directions
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Similar Properties -->
                        <div v-if="similarProperties && similarProperties.length">
                            <h2 class="text-2xl font-light text-gray-900 dark:text-white mb-6">Similar properties</h2>
                            <div class="space-y-4">
                                <Link
                                    v-for="prop in similarProperties"
                                    :key="prop.id"
                                    :href="route('properties.show', [prop.building.slug, prop.slug])"
                                    class="flex items-center gap-4 p-4 border border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 rounded-2xl transition-all group"
                                >
                                    <div class="w-20 h-20 rounded-xl overflow-hidden flex-shrink-0 bg-gray-100 dark:bg-gray-900">
                                        <img
                                            v-if="prop.primary_image"
                                            :src="prop.primary_image.image_path"
                                            :alt="prop.name"
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 dark:text-white truncate">{{ prop.name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ prop.building.name }} · {{ prop.bedroom_type }}</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">
                                            {{ formatPrice(prop.base_price_per_night) }}<span class="font-normal text-gray-500"> / night</span>
                                        </p>
                                    </div>
                                    <ChevronRight class="w-5 h-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 flex-shrink-0 group-hover:translate-x-1 transition-transform" />
                                </Link>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Booking Card (Sticky) -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-24">
                            <!-- Existing Booking Notice -->
                            <div
                                v-if="userBooking && userBookingBanner"
                                :class="userBookingBanner.isPending
        ? 'bg-yellow-50 dark:bg-yellow-900/10 border-yellow-200 dark:border-yellow-800'
        : 'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800'"
                                class="border rounded-2xl p-5 mb-6"
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        :class="userBookingBanner.isPending ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400'"
                                        class="mt-0.5 flex-shrink-0"
                                    >
                                        <AlertCircle v-if="userBookingBanner.isPending" class="w-5 h-5" />
                                        <CheckCircle v-else class="w-5 h-5" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p
                                            :class="userBookingBanner.isPending ? 'text-yellow-800 dark:text-yellow-300' : 'text-green-800 dark:text-green-300'"
                                            class="text-sm font-medium mb-1"
                                        >
                                            {{ userBookingBanner.isPending ? 'You have a pending booking' : 'You have a confirmed booking' }}
                                        </p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
                                            {{ userBookingBanner.checkIn }} → {{ userBookingBanner.checkOut }}
                                        </p>
                                        <div class="flex gap-2 flex-wrap">
                                            <Link
                                                v-if="userBookingBanner.isPending"
                                                :href="route('bookings.payment', userBooking.booking_reference)"
                                                class="inline-flex items-center px-3 py-1.5 bg-yellow-600 hover:bg-yellow-700 text-white text-xs font-medium rounded-lg transition-all"
                                            >
                                                Complete Payment
                                            </Link>
                                            <Link
                                                :href="route('bookings.show', userBooking.id)"
                                                class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-gray-400 dark:hover:border-gray-600 text-xs font-medium rounded-lg transition-all"
                                            >
                                                View Booking
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="border border-gray-200 dark:border-gray-800 rounded-3xl p-8 bg-white dark:bg-gray-900 shadow-sm">
                                <!-- Price -->
                                <div class="mb-6 pb-6 border-b border-gray-100 dark:border-gray-800">
                                    <div class="flex items-baseline">
                                        <span class="text-3xl font-light text-gray-900 dark:text-white">
                                            {{ formatPrice(unitType.base_price_per_night) }}
                                        </span>
                                        <span class="ml-2 text-gray-500 dark:text-gray-400">/ night</span>
                                    </div>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                        Excl. cleaning fee & service charge
                                    </p>
                                </div>

                                <!-- Date Selection -->
                                <div class="space-y-4 mb-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                                            <Calendar class="w-4 h-4 mr-1.5" />
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
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                                            <Users class="w-4 h-4 mr-1.5" />
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
                                    <div v-if="calculateNights >= 5" class="flex justify-between text-sm text-emerald-600 dark:text-emerald-400">
                                        <span>Long stay discount (5% off)</span>
                                        <span>−{{ formatPrice(calculateNights * parseFloat(unitType.base_price_per_night) * 0.05) }}</span>
                                    </div>
                                </div>

                                <!-- Total -->
                                <div v-if="calculateNights > 0" class="mb-6">
                                    <div class="flex justify-between items-baseline">
                                        <span class="text-lg font-medium text-gray-900 dark:text-white">Total</span>
                                        <span class="text-2xl font-light text-gray-900 dark:text-white">{{ formatPrice(estimatedTotal) }}</span>
                                    </div>
                                </div>

                                <!-- Availability feedback -->
                                <div v-if="availabilityMessage" class="mb-4">
                                    <p class="text-sm text-center py-2 px-4 rounded-lg"
                                       :class="availabilityMessage.includes('Available') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400'">
                                        {{ availabilityMessage }}
                                    </p>
                                    <p
                                        v-if="availableUnitsCount > 0 && availableUnitsCount <= 2"
                                        class="text-xs text-center text-amber-600 dark:text-amber-400 font-medium mt-1"
                                    >
                                        ⚡ Only {{ availableUnitsCount }} unit{{ availableUnitsCount > 1 ? 's' : '' }} left for these dates
                                    </p>
                                </div>

                                <!-- Reserve Button (if authenticated) -->
                                <button
                                    v-if="$page.props.auth.user"
                                    @click="proceedToBooking"
                                    :disabled="isReserving"
                                    class="w-full px-6 py-4 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 text-white dark:text-gray-900 font-medium rounded-full transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                                >
                                    <span v-if="isReserving" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Processing...
                                    </span>
                                    <span v-else>Reserve</span>
                                </button>

                                <!-- Sign in link (if not authenticated) -->
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

        <!-- Mobile sticky bottom bar -->
        <div class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white dark:bg-gray-950 border-t border-gray-200 dark:border-gray-800 px-4 py-3 flex items-center justify-between shadow-lg">
            <div>
                <p class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ formatPrice(unitType.base_price_per_night) }}
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">/ night</span>
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500">Excl. fees</p>
            </div>
            <button
                v-if="!userBooking"
                @click="proceedToBooking"
                :disabled="isReserving"
                class="px-6 py-2.5 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 disabled:bg-gray-300 dark:disabled:bg-gray-700 text-white dark:text-gray-900 text-sm font-medium rounded-full transition-all"
            >
                {{ isReserving ? 'Loading...' : 'Reserve' }}
            </button>
            <Link
                v-else-if="userBooking && userBookingBanner?.isPending"
                :href="route('bookings.payment', userBooking.booking_reference)"
                class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-full transition-all"
            >
                Complete Payment
            </Link>
            <Link
                v-else
                :href="route('bookings.show', userBooking.id)"
                class="px-6 py-2.5 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-full transition-all"
            >
                View Booking
            </Link>
        </div>

    </AppLayout>
</template>
