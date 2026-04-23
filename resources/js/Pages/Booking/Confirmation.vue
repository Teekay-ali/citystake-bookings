<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { useToast } from 'vue-toastification';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import { CheckCircle, Calendar, MapPin, Users, Download, Home, Phone, Mail, Clock, Info, XCircle } from 'lucide-vue-next';

const props = defineProps({
    booking: Object,
});

const toast = useToast();
const showCancelModal = ref(false);
const isCancelling = ref(false);

onMounted(() => {
    // Show toast after a small delay to ensure page is fully loaded
    setTimeout(() => {
        toast.success('🎉 Payment successful! Your booking is confirmed.', {
            timeout: 6000,
        });
    }, 300);
});


const openCancelModal = () => {
    showCancelModal.value = true;
};


const cancelBooking = () => {
    isCancelling.value = true;
    router.post(route('bookings.cancel', props.booking.id), {}, {
        onSuccess: () => {
            showCancelModal.value = false;
        },
        onError: () => {
            toast.error('Failed to cancel booking. Please try again.');
        },
        onFinish: () => {
            isCancelling.value = false;
        }
    });
};

const closeCancelModal = () => {
    if (!isCancelling.value) {
        showCancelModal.value = false;
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

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const printConfirmation = () => {
    window.print();
};


const canCancel = computed(() => {
    return props.booking.status !== 'cancelled'
        && props.booking.status !== 'completed'
        && new Date(props.booking.check_in) > new Date();
});
</script>

<template>
    <AppLayout :hide-footer="true">
        <Head title="Booking Confirmed!" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
            <div class="max-w-3xl mx-auto px-6 lg:px-8">
                <!-- Print-only Header (hidden on screen) -->
                <div class="print-only text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">CityStake</h1>
                    <p class="text-sm text-gray-600">Premium Short-let Apartments</p>
                    <div class="mt-4 pt-4 border-t border-gray-300">
                        <h2 class="text-xl font-semibold text-gray-900">Booking Confirmation</h2>
                    </div>
                </div>

                <!-- Screen-only Success Icon (hidden when printing) -->
                <div class="text-center mb-12 no-print">
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

                <!-- Compact Booking Details for Print -->
                <div class="print-receipt">
                    <!-- Reference Number -->
                    <div class="mb-6 pb-4 border-b border-gray-300">
                        <p class="text-xs text-gray-600 mb-1">Booking Reference</p>
                        <p class="text-lg font-mono font-bold text-gray-900">{{ booking.booking_reference }}</p>
                        <p class="text-xs text-gray-600 mt-1">Booked on {{ new Date(booking.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}</p>
                    </div>

                    <!-- Property Info -->
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Property Details</h3>
                        <p class="font-medium text-gray-900">{{ booking.unit_type.name }}</p>
                        <p class="text-sm text-gray-600">{{ booking.building.name }}</p>
                        <p class="text-sm text-gray-600">{{ booking.building.address }}, {{ booking.building.city }}</p>
                        <p class="text-sm text-gray-600 mt-1">Unit: {{ booking.unit.unit_number }} - {{ booking.unit.floor }} Floor</p>
                    </div>

                    <!-- Stay Details -->
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Stay Details</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-xs text-gray-600">Check-in</p>
                                <p class="font-medium text-gray-900">{{ formatDate(booking.check_in) }}</p>
                                <p class="text-xs text-gray-600">After 2:00 PM</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600">Check-out</p>
                                <p class="font-medium text-gray-900">{{ formatDate(booking.check_out) }}</p>
                                <p class="text-xs text-gray-600">Before 12:00 PM</p>
                            </div>
                        </div>
                        <div class="mt-3 text-sm">
                            <p class="text-xs text-gray-600">Duration & Guests</p>
                            <p class="font-medium text-gray-900">{{ booking.nights }} night(s) • {{ booking.guests }} guest(s)</p>
                        </div>
                    </div>

                    <!-- Guest Info -->
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Guest Information</h3>
                        <div class="text-sm space-y-1">
                            <p><span class="text-gray-600">Name:</span> <span class="font-medium text-gray-900">{{ booking.guest_name }}</span></p>
                            <p><span class="text-gray-600">Email:</span> <span class="text-gray-900">{{ booking.guest_email }}</span></p>
                            <p><span class="text-gray-600">Phone:</span> <span class="text-gray-900">{{ booking.guest_phone }}</span></p>
                        </div>
                    </div>

                    <!-- Payment Summary -->
                    <div class="mb-6 pb-4 border-b border-gray-300">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Payment Summary</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">{{ booking.nights }} night(s) × {{ formatPrice(booking.subtotal / booking.nights) }}</span>
                                <span class="text-gray-900">{{ formatPrice(booking.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Cleaning fee</span>
                                <span class="text-gray-900">{{ formatPrice(booking.cleaning_fee) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Service charge</span>
                                <span class="text-gray-900">{{ formatPrice(booking.service_charge) }}</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-gray-200 font-semibold text-base">
                                <span class="text-gray-900">Total Paid</span>
                                <span class="text-gray-900">{{ formatPrice(booking.total_amount) }}</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-600 mt-2">Payment Status: <span class="font-medium text-green-600">Paid</span></p>
                    </div>

                    <!-- Important Notes -->
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Important Information</h3>
                        <ul class="text-xs text-gray-600 space-y-1">
                            <li>• Please bring a valid government-issued ID</li>
                            <li>• Call 24 hours before arrival: +234 801 234 5678</li>
                            <li>• Free cancellation up to 24 hours before check-in</li>
                        </ul>
                    </div>

                    <!-- Footer -->
                    <div class="text-center text-xs text-gray-500 pt-4 border-t border-gray-300">
                        <p>Thank you for choosing CityStake</p>
                        <p class="mt-1">help@citystake.com • +234 801 234 5678</p>
                        <p class="mt-2">www.citystake.com</p>
                    </div>
                </div>

                <!-- Screen-only detailed content -->
                <div class="no-print">
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

                    <!-- Property Contact & Directions -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-8 mb-8">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6 flex items-center">
                            <MapPin class="w-5 h-5 mr-2" />
                            Property Information
                        </h3>

                        <!-- Address -->
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Address</h4>
                            <p class="text-gray-900 dark:text-white">{{ booking.building.name }}</p>
                            <p class="text-gray-600 dark:text-gray-400">{{ booking.building.address }}, {{ booking.building.city }}</p>
                        </div>

                        <!-- Directions Button -->

                        <a :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(booking.building.name + ', ' + booking.building.address + ', ' + booking.building.city)}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-900 dark:text-white text-sm font-medium rounded-lg transition-all"
                        >
                        <MapPin class="w-4 h-4 mr-2" />
                        Get Directions
                        </a>
                    </div>

                    <!-- Check-in Instructions -->
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-100 dark:border-blue-900 rounded-3xl p-8 mb-8">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6 flex items-center">
                            <Clock class="w-5 h-5 mr-2" />
                            Check-in Instructions
                        </h3>

                        <div class="space-y-4">
                            <div class="flex items-start">
                                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span class="text-blue-600 dark:text-blue-400 font-medium text-sm">1</span>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-1">Arrive after 2:00 PM</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Check-in time starts at 2:00 PM. Early check-in may be available upon request.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span class="text-blue-600 dark:text-blue-400 font-medium text-sm">2</span>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-1">Bring Valid ID</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        A government-issued ID is required for all guests during check-in.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span class="text-blue-600 dark:text-blue-400 font-medium text-sm">3</span>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-1">Contact Property</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Call us 24 hours before arrival to confirm your check-in time and receive access codes.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span class="text-blue-600 dark:text-blue-400 font-medium text-sm">4</span>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-1">Unit Assignment</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Your unit: <span class="font-medium text-gray-900 dark:text-white">{{ booking.unit.unit_number }}</span> - {{ booking.unit.floor }} Floor
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Info -->
                        <div class="mt-6 pt-6 border-t border-blue-200 dark:border-blue-800">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Need Help?</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <a href="tel:+2348012345678" class="flex items-center text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    <Phone class="w-4 h-4 mr-2" />
                                    +234 801 234 5678
                                </a>
                                <a href="mailto:help@citystake.com" class="flex items-center text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    <Mail class="w-4 h-4 mr-2" />
                                    help@citystake.com
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Important Information -->
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-900 rounded-2xl p-6 mb-8">
                        <h3 class="text-sm font-medium text-green-900 dark:text-green-300 mb-3 flex items-center">
                            <Info class="w-4 h-4 mr-2" />
                            What to Expect
                        </h3>
                        <ul class="space-y-2 text-sm text-green-800 dark:text-green-200">
                            <li class="flex items-start">
                                <CheckCircle class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                                <span>Confirmation email sent to {{ booking.guest_email }}</span>
                            </li>
                            <li class="flex items-start">
                                <CheckCircle class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                                <span>Check-out time is before 12:00 PM</span>
                            </li>
                            <li class="flex items-start">
                                <CheckCircle class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                                <span>Free cancellation up to 24 hours before check-in</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Actions -->
                    <div class="space-y-4">
                        <!-- Primary Actions -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Link
                                :href="route('bookings.index')"
                                class="flex items-center justify-center px-6 py-3 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 text-white dark:text-gray-900 font-medium rounded-full transition-all group"
                            >
                                <Calendar class="w-5 h-5 mr-2" />
                                My Bookings
                            </Link>
                            <Link
                                :href="route('properties.index')"
                                class="flex items-center justify-center px-6 py-3 border-2 border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 text-gray-900 dark:text-white font-medium rounded-full transition-all"
                            >
                                Browse Properties
                            </Link>
                        </div>

                        <!-- Print/Download Button -->
                        <button
                            @click="printConfirmation"
                            class="w-full flex items-center justify-center px-6 py-3 border border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-full transition-all"
                        >
                            <Download class="w-5 h-5 mr-2" />
                            Print Confirmation
                        </button>
                    </div>

                    <!-- Cancel Booking Section -->
                    <div v-if="canCancel" class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-800 no-print">
                        <button
                            @click="openCancelModal"
                            class="flex items-center justify-center w-full px-6 py-3 border-2 border-red-200 dark:border-red-800 hover:border-red-300 dark:hover:border-red-700 text-red-600 dark:text-red-400 font-medium rounded-full transition-all"
                        >
                            <XCircle class="w-5 h-5 mr-2" />
                            Cancel Booking
                        </button>
                        <p class="text-xs text-center text-gray-500 dark:text-gray-400 mt-3">
                            Free cancellation up to 24 hours before check-in
                        </p>
                    </div>


                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="showCancelModal"
            :processing="isCancelling"
            title="Cancel Booking?"
            message="Are you sure you want to cancel this booking? This action cannot be undone. Free cancellation is only available up to 24 hours before check-in."
            confirm-text="Yes, Cancel Booking"
            cancel-text="Keep Booking"
            variant="danger"
            @confirm="cancelBooking"
            @close="closeCancelModal"
        />

    </AppLayout>
</template>
