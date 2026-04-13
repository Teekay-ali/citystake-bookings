<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { useToast } from 'vue-toastification';
import {
    ArrowLeft,
    Calendar,
    MapPin,
    Users,
    Mail,
    Phone,
    CreditCard,
    User,
    MessageSquare,
    Building2,
    Home,
    DollarSign,
    Receipt
} from 'lucide-vue-next';

const props = defineProps({
    buildings: Array,
});

const toast = useToast();

const form = useForm({
    building_id: '',
    unit_type_id: '',
    check_in: '',
    check_out: '',
    guests: 1,
    guest_name: '',
    guest_email: '',
    guest_phone: '',
    special_requests: '',
    payment_method: 'cash',
    payment_reference: '',
});

const selectedBuilding = computed(() => {
    return props.buildings.find(b => b.id == form.building_id);
});

const availableUnitTypes = computed(() => {
    return selectedBuilding.value?.unit_types || [];
});

const selectedUnitType = computed(() => {
    return availableUnitTypes.value.find(ut => ut.id == form.unit_type_id);
});

const calculateNights = computed(() => {
    if (!form.check_in || !form.check_out) return 0;
    const checkIn = new Date(form.check_in);
    const checkOut = new Date(form.check_out);
    const diffTime = checkOut - checkIn;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays > 0 ? diffDays : 0;
});

const pricing = computed(() => {
    if (!selectedUnitType.value || calculateNights.value === 0) {
        return { subtotal: 0, cleaning: 0, service: 0, discountAmount: 0, discountPercent: 0, discountType: null, total: 0 };
    }

    const nights   = calculateNights.value;
    const subtotal = selectedUnitType.value.base_price_per_night * nights;
    const cleaning = parseFloat(selectedUnitType.value.cleaning_fee) || 0;
    const service  = subtotal * ((parseFloat(selectedUnitType.value.service_charge_percent) || 0) / 100);

    // Mirror backend discount rules
    let discountPercent = 0;
    let discountType    = null;

    if (nights >= 7) {
        // Check bulk first (higher discount) — but walk-in is single unit
        // so only long_stay applies here; bulk handled server-side for group bookings
    }
    if (nights >= 5) {
        discountPercent = 5;
        discountType    = 'long_stay';
    }

    const discountAmount = discountPercent > 0 ? Math.round(subtotal * (discountPercent / 100) * 100) / 100 : 0;
    const total = (subtotal - discountAmount) + cleaning + service;

    return { subtotal, cleaning, service, discountAmount, discountPercent, discountType, total };
});

// Reset unit type when building changes
watch(() => form.building_id, () => {
    form.unit_type_id = '';
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

const submit = () => {
    form.post(route('manage.bookings.store'));
};
</script>

<template>
    <ManageLayout>
        <Head title="Create Booking - Admin" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
            <div class="max-w-5xl mx-auto px-6 lg:px-8">
                <!-- Back Button -->
                <div class="mb-8">
                    <Link
                        :href="route('manage.bookings.index')"
                        class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                    >
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Back to bookings
                    </Link>
                </div>

                <!-- Header -->
                <div class="mb-12">
                    <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-2">
                        Create New Booking
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        Create a booking for walk-in guests or phone reservations
                    </p>
                </div>

                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Main Form -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Property Selection -->
                            <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                                <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6 flex items-center">
                                    <Building2 class="w-5 h-5 mr-2" />
                                    Property Selection
                                </h2>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Building -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Building <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            v-model="form.building_id"
                                            required
                                            :class="[
                                                'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                                form.errors.building_id
                                                    ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                                    : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                            ]"
                                        >
                                            <option value="">Select building</option>
                                            <option v-for="building in buildings" :key="building.id" :value="building.id">
                                                {{ building.name }}
                                            </option>
                                        </select>
                                        <p v-if="form.errors.building_id" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                            {{ form.errors.building_id }}
                                        </p>
                                    </div>

                                    <!-- Unit Type -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Unit Type <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            v-model="form.unit_type_id"
                                            :disabled="!form.building_id"
                                            required
                                            :class="[
                                                'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                                form.errors.unit_type_id
                                                    ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                                    : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white',
                                                !form.building_id && 'opacity-50 cursor-not-allowed'
                                            ]"
                                        >
                                            <option value="">Select unit type</option>
                                            <option v-for="unitType in availableUnitTypes" :key="unitType.id" :value="unitType.id">
                                                {{ unitType.name }} - {{ formatPrice(unitType.base_price_per_night) }}/night
                                            </option>
                                        </select>
                                        <p v-if="form.errors.unit_type_id" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                            {{ form.errors.unit_type_id }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Dates & Guests -->
                            <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                                <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6 flex items-center">
                                    <Calendar class="w-5 h-5 mr-2" />
                                    Stay Details
                                </h2>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Check-in <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.check_in"
                                            type="date"
                                            required
                                            :class="[
                                                'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                                form.errors.check_in
                                                    ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                                    : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                            ]"
                                        />
                                        <p v-if="form.errors.check_in" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                            {{ form.errors.check_in }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Check-out <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.check_out"
                                            type="date"
                                            required
                                            :class="[
                                                'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                                form.errors.check_out
                                                    ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                                    : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                            ]"
                                        />
                                        <p v-if="form.errors.check_out" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                            {{ form.errors.check_out }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Guests <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model.number="form.guests"
                                            type="number"
                                            min="1"
                                            :max="selectedUnitType?.max_guests || 10"
                                            required
                                            :class="[
                                                'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                                form.errors.guests
                                                    ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                                    : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                            ]"
                                        />
                                        <p v-if="form.errors.guests" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                            {{ form.errors.guests }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="calculateNights > 0" class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                                    <p class="text-sm text-blue-900 dark:text-blue-300">
                                        {{ calculateNights }} night{{ calculateNights > 1 ? 's' : '' }} selected
                                    </p>
                                </div>
                            </div>

                            <!-- Guest Information -->
                            <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                                <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6 flex items-center">
                                    <User class="w-5 h-5 mr-2" />
                                    Guest Information
                                </h2>

                                <div class="space-y-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Full Name <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.guest_name"
                                            type="text"
                                            required
                                            placeholder="Enter guest's full name"
                                            :class="[
                                                'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                                form.errors.guest_name
                                                    ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                                    : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                            ]"
                                        />
                                        <p v-if="form.errors.guest_name" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                            {{ form.errors.guest_name }}
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Email <span class="text-red-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.guest_email"
                                                type="email"
                                                required
                                                placeholder="guest@example.com"
                                                :class="[
                                                    'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                                    form.errors.guest_email
                                                        ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                                        : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                                ]"
                                            />
                                            <p v-if="form.errors.guest_email" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                                {{ form.errors.guest_email }}
                                            </p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Phone <span class="text-red-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.guest_phone"
                                                type="tel"
                                                required
                                                placeholder="+234 800 000 0000"
                                                :class="[
                                                    'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                                    form.errors.guest_phone
                                                        ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                                        : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                                ]"
                                            />
                                            <p v-if="form.errors.guest_phone" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                                {{ form.errors.guest_phone }}
                                            </p>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Special Requests
                                        </label>
                                        <textarea
                                            v-model="form.special_requests"
                                            rows="3"
                                            placeholder="Any special requirements or requests..."
                                            class="w-full px-4 py-3 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all resize-none"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Method -->
                            <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                                <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6 flex items-center">
                                    <CreditCard class="w-5 h-5 mr-2" />
                                    Payment Method
                                </h2>

                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <label class="relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all" :class="form.payment_method === 'cash' ? 'border-gray-900 dark:border-white bg-gray-50 dark:bg-gray-900' : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'">
                                            <input
                                                v-model="form.payment_method"
                                                type="radio"
                                                value="cash"
                                                class="sr-only"
                                            />
                                            <div class="flex-1">
                                                <p class="font-medium text-gray-900 dark:text-white">Cash</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Cash payment</p>
                                            </div>
                                            <div v-if="form.payment_method === 'cash'" class="w-5 h-5 rounded-full bg-gray-900 dark:bg-white flex items-center justify-center">
                                                <div class="w-2 h-2 rounded-full bg-white dark:bg-gray-900"></div>
                                            </div>
                                        </label>

                                        <label class="relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all" :class="form.payment_method === 'pos' ? 'border-gray-900 dark:border-white bg-gray-50 dark:bg-gray-900' : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'">
                                            <input
                                                v-model="form.payment_method"
                                                type="radio"
                                                value="pos"
                                                class="sr-only"
                                            />
                                            <div class="flex-1">
                                                <p class="font-medium text-gray-900 dark:text-white">POS</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Card payment</p>
                                            </div>
                                            <div v-if="form.payment_method === 'pos'" class="w-5 h-5 rounded-full bg-gray-900 dark:bg-white flex items-center justify-center">
                                                <div class="w-2 h-2 rounded-full bg-white dark:bg-gray-900"></div>
                                            </div>
                                        </label>

                                        <label class="relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all" :class="form.payment_method === 'bank_transfer' ? 'border-gray-900 dark:border-white bg-gray-50 dark:bg-gray-900' : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'">
                                            <input
                                                v-model="form.payment_method"
                                                type="radio"
                                                value="bank_transfer"
                                                class="sr-only"
                                            />
                                            <div class="flex-1">
                                                <p class="font-medium text-gray-900 dark:text-white">Transfer</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Bank transfer</p>
                                            </div>
                                            <div v-if="form.payment_method === 'bank_transfer'" class="w-5 h-5 rounded-full bg-gray-900 dark:bg-white flex items-center justify-center">
                                                <div class="w-2 h-2 rounded-full bg-white dark:bg-gray-900"></div>
                                            </div>
                                        </label>
                                    </div>

                                    <div v-if="form.payment_method === 'bank_transfer' || form.payment_method === 'pos'">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Payment Reference (Optional)
                                        </label>
                                        <input
                                            v-model="form.payment_reference"
                                            type="text"
                                            placeholder="Enter transaction reference"
                                            class="w-full px-4 py-3 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar - Summary -->
                        <div class="lg:col-span-1">
                            <div class="sticky top-24 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6 flex items-center">
                                    <Receipt class="w-5 h-5 mr-2" />
                                    Booking Summary
                                </h3>

                                <div v-if="selectedUnitType && calculateNights > 0" class="space-y-4">
                                    <!-- Property -->
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Property</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedUnitType.name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedBuilding.name }}</p>
                                    </div>

                                    <!-- Dates -->
                                    <div class="pt-4 border-t border-gray-100 dark:border-gray-900">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Stay Duration</p>
                                        <p class="text-sm text-gray-900 dark:text-white">{{ calculateNights }} night{{ calculateNights > 1 ? 's' : '' }}</p>
                                    </div>

                                    <!-- Pricing -->
                                    <div class="pt-4 border-t border-gray-100 dark:border-gray-900 space-y-3">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">{{ formatPrice(selectedUnitType.base_price_per_night) }} × {{ calculateNights }} nights</span>
                                            <span class="text-gray-900 dark:text-white">{{ formatPrice(pricing.subtotal) }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">Cleaning fee</span>
                                            <span class="text-gray-900 dark:text-white">{{ formatPrice(pricing.cleaning) }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">Service charge</span>
                                            <span class="text-gray-900 dark:text-white">{{ formatPrice(pricing.service) }}</span>
                                        </div>
                                        <!-- After service charge row, before total -->
                                        <div v-if="pricing.discountAmount > 0" class="flex justify-between text-sm text-emerald-600 dark:text-emerald-400">
                                            <span>Long stay discount ({{ pricing.discountPercent }}% off)</span>
                                            <span>−{{ formatPrice(pricing.discountAmount) }}</span>
                                        </div>
                                    </div>

                                    <!-- Total -->
                                    <div class="pt-4 border-t border-gray-200 dark:border-gray-800">
                                        <div class="flex justify-between items-baseline">
                                            <span class="text-base font-medium text-gray-900 dark:text-white">Total</span>
                                            <span class="text-2xl font-light text-gray-900 dark:text-white">{{ formatPrice(pricing.total) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="text-center py-8">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Select property and dates to see pricing
                                    </p>
                                </div>

                                <!-- Submit Button -->
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full mt-6 px-6 py-4 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 disabled:bg-gray-300 dark:disabled:bg-gray-700 text-white dark:text-gray-900 font-medium rounded-full transition-all disabled:cursor-not-allowed flex items-center justify-center"
                                >
                                    <span v-if="form.processing" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Creating Booking...
                                    </span>
                                    <span v-else>Create Booking</span>
                                </button>

                                <p class="text-xs text-center text-gray-500 dark:text-gray-400 mt-4">
                                    Booking will be marked as confirmed and paid
                                </p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </ManageLayout>
</template>
