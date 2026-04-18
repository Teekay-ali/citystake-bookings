<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { useToast } from 'vue-toastification';
import {
    Calendar,
    Users,
    MapPin,
    CreditCard,
    Shield,
    CheckCircle,
    Receipt,
    Clock,
    AlertTriangle,
} from 'lucide-vue-next';
import paystackLogo from '@/assets/paystack.svg';
import monnifyLogo from '@/assets/moniepoint.svg';

const props = defineProps({
    booking: Object,
    paystackPublicKey: String,
    monnifyApiKey: String,
    monnifyContractCode: String,
    monnifyTestMode: Boolean,
});


const selectedGateway = ref('paystack'); // default
const monnifyLoaded = ref(false);

// Countdown — 30 min hold window from booking creation
const HOLD_MINUTES = 30;
const timeRemaining = ref('');
const isExpired = ref(false);
let countdownInterval = null;

const updateCountdown = () => {
    const createdAt = new Date(props.booking.created_at);
    const expiresAt = new Date(createdAt.getTime() + HOLD_MINUTES * 60 * 1000);
    const now = new Date();
    const diff = expiresAt - now;

    if (diff <= 0) {
        isExpired.value = true;
        timeRemaining.value = '00:00';
        clearInterval(countdownInterval);
        return;
    }

    const minutes = Math.floor(diff / 60000);
    const seconds = Math.floor((diff % 60000) / 1000);
    timeRemaining.value = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
};

const isProcessing = ref(false);
const paystackLoaded = ref(false);

const toast = useToast();

// Load Paystack script
onMounted(() => {

    // Start countdown
    updateCountdown();
    countdownInterval = setInterval(updateCountdown, 1000);

    // Load Paystack
    if (!window.PaystackPop) {
        const script = document.createElement('script');
        script.src = 'https://js.paystack.co/v1/inline.js';
        script.onload = () => {
            paystackLoaded.value = true;
        };
        script.onerror = () => {
            toast.error('Failed to load payment system. Please refresh the page.');
        };
        document.head.appendChild(script);
    } else {
        paystackLoaded.value = true;
    }

    // Load Monnify
    if (!window.MonnifySDK) {
        const script = document.createElement('script');
        script.src = 'https://sdk.monnify.com/plugin/monnify.js';
        script.onload = () => { monnifyLoaded.value = true; };
        script.onerror = () => { toast.error('Failed to load Monnify. Please refresh.'); };
        document.head.appendChild(script);
    } else {
        monnifyLoaded.value = true;
    }
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

const payWithPaystack = () => {
    if (!paystackLoaded.value) {
        toast.warning('Payment system is still loading, please wait a moment...');
        return;
    }

    isProcessing.value = true;

    const handler = window.PaystackPop.setup({
        key: props.paystackPublicKey,
        email: props.booking.guest_email,
        amount: props.booking.total_amount * 100, // Convert to kobo
        currency: 'NGN',
        ref: props.booking.payment_reference,
        metadata: {
            booking_reference: props.booking.booking_reference,
            guest_name: props.booking.guest_name,
            guest_phone: props.booking.guest_phone,
        },
        callback: function(response) {
            // Redirect to verify payment
            window.location.href = route('bookings.verify', {
                bookingReference: props.booking.booking_reference,
                reference: response.reference
            });
        },
        onClose: function() {
            isProcessing.value = false;
            toast.warning('Payment cancelled. Complete payment to confirm your booking.');
        }
    });

    handler.openIframe();
};

const payWithMonnify = () => {
    if (!monnifyLoaded.value) {
        toast.warning('Monnify is still loading, please wait...');
        return;
    }

    isProcessing.value = true;
    let paymentCompleted = false; // ADD THIS FLAG

    window.MonnifySDK.initialize({
        amount: props.booking.total_amount,
        currency: 'NGN',
        reference: props.booking.payment_reference,
        customerFullName: props.booking.guest_name,
        customerEmail: props.booking.guest_email,
        customerMobileNumber: props.booking.guest_phone ?? '',
        apiKey: props.monnifyApiKey,
        contractCode: props.monnifyContractCode,
        paymentDescription: `Booking ${props.booking.booking_reference} — CityStake`,
        isTestMode: props.monnifyTestMode,
        onComplete: (response) => {
            paymentCompleted = true; // MARK AS COMPLETE BEFORE REDIRECT
            window.location.href = route('bookings.verify-monnify', {
                bookingReference: props.booking.booking_reference,
                paymentReference: response.paymentReference,
            });
        },
        onClose: (data) => {
            if (paymentCompleted) return; // SUPPRESS THE WARNING IF PAYMENT SUCCEEDED
            isProcessing.value = false;
            toast.warning('Payment cancelled. Complete payment to confirm your booking.');
        },
    });
};

</script>

<template>
    <AppLayout :hide-footer="true">
        <Head title="Payment - Complete Your Booking" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
            <div class="max-w-4xl mx-auto px-6 lg:px-8">
                <!-- Expired Banner -->
                <div v-if="isExpired" class="mb-8 flex items-start gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl px-5 py-4">
                    <AlertTriangle class="w-5 h-5 text-red-500 mt-0.5 shrink-0" />
                    <div>
                        <p class="text-sm font-medium text-red-700 dark:text-red-400">Your booking reservation has expired</p>
                        <p class="text-sm text-red-600 dark:text-red-500 mt-0.5">This hold was released after 30 minutes. Please start a new booking — subject to availability.</p>
                    </div>
                </div>

                <!-- Countdown Banner -->
                <div v-else class="mb-8 flex items-center justify-between gap-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl px-5 py-4">
                    <div class="flex items-center gap-3">
                        <Clock class="w-5 h-5 text-amber-500 shrink-0" />
                        <p class="text-sm text-amber-700 dark:text-amber-400">Complete payment to secure your booking</p>
                    </div>
                    <span
                        :class="[
                            'text-sm font-mono font-semibold tabular-nums',
                            parseInt(timeRemaining) < 5 ? 'text-red-600 dark:text-red-400' : 'text-amber-700 dark:text-amber-400'
                        ]"
                                    >
                        {{ timeRemaining }}
                    </span>
                </div>
                <!-- Header -->
                <div class="text-center mb-12">
                    <div class="w-20 h-20 rounded-full bg-green-50 dark:bg-green-900/20 flex items-center justify-center mx-auto mb-6">
                        <CreditCard class="w-10 h-10 text-green-600 dark:text-green-400" />
                    </div>
                    <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-2">
                        Complete your payment
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        Booking Reference: <span class="font-medium">{{ booking.booking_reference }}</span>
                    </p>
                </div>

                <!-- Booking Summary Card -->
                <div class="border border-gray-200 dark:border-gray-800 rounded-3xl p-8 mb-8">
                    <!-- Property Details -->
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
                            <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center mb-2">
                                <MapPin class="w-4 h-4 mr-1" />
                                {{ booking.building.name }} • {{ booking.building.address }}
                            </p>
                            <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                                <span class="flex items-center">
                                    <Calendar class="w-4 h-4 mr-1" />
                                    {{ booking.nights }} nights
                                </span>
                                <span class="flex items-center">
                                    <Users class="w-4 h-4 mr-1" />
                                    {{ booking.guests }} guests
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Trip Details -->
                    <div class="grid grid-cols-2 gap-6 mb-6 pb-6 border-b border-gray-100 dark:border-gray-900">
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">CHECK-IN</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(booking.check_in) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">CHECK-OUT</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(booking.check_out) }}</p>
                        </div>
                    </div>

                    <!-- Guest Details -->
                    <div class="mb-6 pb-6 border-b border-gray-100 dark:border-gray-900">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Guest Information</h3>
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
                        </div>
                    </div>

                    <!-- Price Breakdown -->
                    <div class="space-y-3 mb-6">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                            <Receipt class="w-4 h-4 mr-2" />
                            Price Details
                        </h3>
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>{{ formatPrice(booking.subtotal / booking.nights) }} × {{ booking.nights }} nights</span>
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

                    <!-- Total -->
                    <div class="pt-6 border-t border-gray-100 dark:border-gray-900">
                        <div class="flex justify-between items-baseline">
                            <span class="text-lg font-medium text-gray-900 dark:text-white">Total Amount</span>
                            <span class="text-3xl font-light text-gray-900 dark:text-white">
                                {{ formatPrice(booking.total_amount) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Gateway Selection -->
                <div class="flex gap-3 mb-6">
                    <button
                        @click="selectedGateway = 'paystack'"
                        :class="[
                            'flex-1 py-3 px-4 rounded-2xl border-2 transition-all flex items-center justify-center gap-2',
                            selectedGateway === 'paystack'
                                ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                                : 'border-gray-200 dark:border-gray-700'
                        ]"
                    >
                        <!-- Paystack logo -->
                        <img :src="paystackLogo" class="h-6 w-auto" alt="Paystack" />
                    </button>

                    <button
                        @click="selectedGateway = 'monnify'"
                        :class="[
                            'flex-1 py-3 px-4 rounded-2xl border-2 transition-all flex items-center justify-center gap-2',
                            selectedGateway === 'monnify'
                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                : 'border-gray-200 dark:border-gray-700'
                        ]"
                    >
                        <!-- Monnify logo -->
                        <img :src="monnifyLogo" class="h-6 w-auto" alt="Monnify" />

                    </button>
                </div>

                <!-- Payment Button -->
                <button
                    @click="selectedGateway === 'paystack' ? payWithPaystack() : payWithMonnify()"
                    :disabled="isProcessing || isExpired"
                    class="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-300 dark:disabled:bg-gray-700 text-white font-medium py-4 px-6 rounded-full transition-all disabled:cursor-not-allowed flex items-center justify-center group mb-6"
                >
                    <Shield v-if="!isProcessing" class="w-5 h-5 mr-2" />
                    <span v-if="!isProcessing">
                        Pay Securely with {{ selectedGateway === 'paystack' ? 'Paystack' : 'Monnify' }}
                    </span>
                    <span v-else>Processing...</span>
                </button>

                <!-- Security Notice -->
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-2xl p-6 text-center">
                    <div class="flex items-center justify-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                        <Shield class="w-4 h-4" />
                        <span v-if="selectedGateway === 'paystack'">Secured by Paystack • Your payment information is encrypted</span>
                        <span v-else>Secured by Monnify • Card, Bank Transfer & USSD supported</span>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
