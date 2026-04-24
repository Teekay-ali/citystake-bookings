<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { useToast } from "vue-toastification";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import {
    ArrowLeft, MessageSquare,
    FileText,
    Calendar,
    CreditCard,
    MapPin,
    Users,
    Download,
    Phone,
    Mail,
    Clock,
    Info,
    XCircle,
    CheckCircle,
    AlertCircle,
    Home as HomeIcon,
} from "lucide-vue-next";

const props = defineProps({
    booking: Object,
    messages: Array,
});

const toast = useToast();

const showCancelModal = ref(false);
const isCancelling = ref(false);

const messageBody    = ref('')
const sendingMessage = ref(false)

const openCancelModal = () => {
    showCancelModal.value = true;
};

const formatPrice = (price) => {
    return new Intl.NumberFormat("en-NG", {
        style: "currency",
        currency: "NGN",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

const formatDateTime = (date) => {
    return new Date(date).toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const printConfirmation = () => {
    window.print();
};

const cancelBooking = () => {
    isCancelling.value = true;
    router.post(
        route("bookings.cancel", props.booking.id),
        {},
        {
            onSuccess: () => {
                showCancelModal.value = false;
            },
            onError: () => {
                toast.error("Failed to cancel booking. Please try again.");
            },
            onFinish: () => {
                isCancelling.value = false;
            },
        },
    );
};

const closeCancelModal = () => {
    if (!isCancelling.value) {
        showCancelModal.value = false;
    }
};

const canCancel = computed(() => {
    return (
        props.booking.status !== "cancelled" &&
        props.booking.status !== "completed" &&
        new Date(props.booking.check_in) > new Date()
    );
});

const getStatusBadge = computed(() => {
    return resolveStatusBadge(props.booking.display_status);
});

function resolveStatusBadge(displayStatus) {
    const map = {
        cancelled: {
            icon: XCircle,
            text: "Cancelled",
            class: "bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-2 border-red-200 dark:border-red-800",
        },
        payment_pending: {
            icon: AlertCircle,
            text: "Payment Pending",
            class: "bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 border-2 border-yellow-200 dark:border-yellow-800",
        },
        active: {
            icon: Clock,
            text: "Active Stay",
            class: "bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-2 border-blue-200 dark:border-blue-800",
        },
        checked_in: {
            icon: Clock,
            text: "Checked In",
            class: "bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-2 border-blue-200 dark:border-blue-800",
        },
        completed: {
            icon: CheckCircle,
            text: "Completed",
            class: "bg-gray-50 dark:bg-gray-900/20 text-gray-700 dark:text-gray-400 border-2 border-gray-200 dark:border-gray-800",
        },
        confirmed: {
            icon: CheckCircle,
            text: "Confirmed",
            class: "bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-2 border-green-200 dark:border-green-800",
        },
    };
    return map[displayStatus] ?? map["confirmed"];
}

function sendMessage() {
    if (!messageBody.value.trim()) return
    sendingMessage.value = true
    useForm({ body: messageBody.value }).post(
        route('bookings.messages.send', props.booking.id),
        {
            preserveScroll: true,
            onSuccess: () => { messageBody.value = '' },
            onFinish: () => { sendingMessage.value = false },
        }
    )
}

const daysUntilCheckIn = computed(() => {
    const today = new Date();
    const checkIn = new Date(props.booking.check_in);
    const diffTime = checkIn - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
});
</script>

<template>
    <AppLayout :hide-footer="true">
        <Head title="Booking Details" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
            <div class="max-w-5xl mx-auto px-6 lg:px-8">
                <!-- Back Button -->
                <div class="mb-8 no-print">
                    <Link
                        :href="route('bookings.index')"
                        class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                    >
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Back to My Bookings
                    </Link>
                </div>

                <!-- Header -->
                <div
                    class="flex flex-col md:flex-row md:items-start md:justify-between gap-6 mb-8"
                >
                    <div>
                        <h1
                            class="text-3xl font-light tracking-tight text-gray-900 dark:text-white mb-2"
                        >
                            Booking Details
                        </h1>
                        <div class="flex items-center gap-3 flex-wrap">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Reference:
                                <span
                                    class="font-mono font-medium text-gray-900 dark:text-white"
                                    >{{ booking.booking_reference }}</span
                                >
                            </p>
                            <span class="text-gray-300 dark:text-gray-700"
                                >•</span
                            >
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Booked {{ formatDateTime(booking.created_at) }}
                            </p>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    <div
                        :class="getStatusBadge.class"
                        class="flex items-center px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap"
                    >
                        <component
                            :is="getStatusBadge.icon"
                            class="w-4 h-4 mr-2"
                        />
                        {{ getStatusBadge.text }}
                    </div>
                </div>

                <!-- Countdown Alert (if upcoming) -->
                <div
                    v-if="
                        daysUntilCheckIn > 0 &&
                        daysUntilCheckIn <= 7 &&
                        booking.status !== 'cancelled'
                    "
                    class="mb-8 no-print"
                >
                    <div
                        class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-4"
                    >
                        <div class="flex items-center">
                            <Clock
                                class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-3"
                            />
                            <div>
                                <p
                                    class="text-sm font-medium text-blue-900 dark:text-blue-300"
                                >
                                    {{
                                        daysUntilCheckIn === 1
                                            ? "Check-in is tomorrow!"
                                            : `${daysUntilCheckIn} days until check-in`
                                    }}
                                </p>
                                <p
                                    class="text-xs text-blue-700 dark:text-blue-400 mt-0.5"
                                >
                                    Remember to contact the property 24 hours
                                    before arrival
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Property Card -->
                        <div
                            class="border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden"
                        >
                            <div class="flex flex-col sm:flex-row">
                                <div
                                    class="sm:w-48 h-48 bg-gray-100 dark:bg-gray-900 flex-shrink-0"
                                >
                                    <img
                                        v-if="
                                            booking.unit_type.images &&
                                            booking.unit_type.images[0]
                                        "
                                        :src="
                                            booking.unit_type.images[0]
                                                .image_path
                                        "
                                        :alt="booking.unit_type.name"
                                        class="w-full h-full object-cover"
                                    />
                                </div>
                                <div class="p-6 flex-1">
                                    <h2
                                        class="text-xl font-medium text-gray-900 dark:text-white mb-2"
                                    >
                                        {{ booking.unit_type.name }}
                                    </h2>
                                    <p
                                        class="text-sm text-gray-600 dark:text-gray-400 flex items-center mb-4"
                                    >
                                        <MapPin class="w-4 h-4 mr-1" />
                                        {{ booking.building.name }} •
                                        {{ booking.building.city }}
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <a
                                            :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(booking.building.name + ', ' + booking.building.address + ', ' + booking.building.city)}`"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center px-3 py-1.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-900 dark:text-white text-xs font-medium rounded-lg transition-all"
                                        >
                                            <MapPin
                                                class="w-3.5 h-3.5 mr-1.5"
                                            />
                                            Directions
                                        </a>
                                        <Link
                                            :href="
                                                route('properties.show', [
                                                    booking.building.slug,
                                                    booking.unit_type.slug,
                                                ])
                                            "
                                            class="inline-flex items-center px-3 py-1.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-900 dark:text-white text-xs font-medium rounded-lg transition-all"
                                        >
                                            <HomeIcon
                                                class="w-3.5 h-3.5 mr-1.5"
                                            />
                                            View Property
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stay Details -->
                        <div
                            class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6"
                        >
                            <h3
                                class="text-lg font-medium text-gray-900 dark:text-white mb-4"
                            >
                                Stay Details
                            </h3>
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <p
                                        class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 flex items-center"
                                    >
                                        <Calendar class="w-3.5 h-3.5 mr-1" />
                                        Check-in
                                    </p>
                                    <p
                                        class="text-base font-medium text-gray-900 dark:text-white"
                                    >
                                        {{ formatDate(booking.check_in) }}
                                    </p>
                                    <p
                                        class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"
                                    >
                                        After 2:00 PM
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 flex items-center"
                                    >
                                        <Calendar class="w-3.5 h-3.5 mr-1" />
                                        Check-out
                                    </p>
                                    <p
                                        class="text-base font-medium text-gray-900 dark:text-white"
                                    >
                                        {{ formatDate(booking.check_out) }}
                                    </p>
                                    <p
                                        class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"
                                    >
                                        Before 12:00 PM
                                    </p>
                                </div>
                            </div>
                            <div
                                class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-900"
                            >
                                <div class="grid grid-cols-2 gap-6">
                                    <div>
                                        <p
                                            class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                                        >
                                            Duration
                                        </p>
                                        <p
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {{ booking.nights }} night{{
                                                booking.nights > 1 ? "s" : ""
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p
                                            class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 flex items-center"
                                        >
                                            <Users class="w-3.5 h-3.5 mr-1" />
                                            Guests
                                        </p>
                                        <p
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {{ booking.guests }} guest{{
                                                booking.guests > 1 ? "s" : ""
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-900"
                            >
                                <p
                                    class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                                >
                                    Unit Assignment
                                </p>
                                <p
                                    class="text-sm text-gray-900 dark:text-white"
                                >
                                    Unit {{ booking.unit.unit_number }} •
                                    {{ booking.unit.floor }} Floor
                                </p>
                            </div>
                        </div>

                        <!-- Guest Information -->
                        <div
                            class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6"
                        >
                            <h3
                                class="text-lg font-medium text-gray-900 dark:text-white mb-4"
                            >
                                Guest Information
                            </h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span
                                        class="text-gray-600 dark:text-gray-400"
                                        >Name</span
                                    >
                                    <span
                                        class="font-medium text-gray-900 dark:text-white"
                                        >{{ booking.guest_name }}</span
                                    >
                                </div>
                                <div class="flex justify-between">
                                    <span
                                        class="text-gray-600 dark:text-gray-400"
                                        >Email</span
                                    >
                                    <a
                                        :href="`mailto:${booking.guest_email}`"
                                        class="font-medium text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300"
                                    >
                                        {{ booking.guest_email }}
                                    </a>
                                </div>
                                <div class="flex justify-between">
                                    <span
                                        class="text-gray-600 dark:text-gray-400"
                                        >Phone</span
                                    >
                                    <a
                                        :href="`tel:${booking.guest_phone}`"
                                        class="font-medium text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300"
                                    >
                                        {{ booking.guest_phone }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Payment Summary -->
                        <div
                            class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6"
                        >
                            <h3
                                class="text-lg font-medium text-gray-900 dark:text-white mb-4"
                            >
                                Payment Summary
                            </h3>
                            <div class="space-y-3 text-sm mb-4">
                                <div
                                    class="flex justify-between text-gray-600 dark:text-gray-400"
                                >
                                    <span
                                        >{{ booking.nights }} night{{
                                            booking.nights > 1 ? "s" : ""
                                        }}</span
                                    >
                                    <span>{{
                                        formatPrice(booking.subtotal)
                                    }}</span>
                                </div>
                                <div
                                    class="flex justify-between text-gray-600 dark:text-gray-400"
                                >
                                    <span>Cleaning fee</span>
                                    <span>{{
                                        formatPrice(booking.cleaning_fee)
                                    }}</span>
                                </div>
                                <div
                                    class="flex justify-between text-gray-600 dark:text-gray-400"
                                >
                                    <span>Service charge</span>
                                    <span>{{
                                        formatPrice(booking.service_charge)
                                    }}</span>
                                </div>
                            </div>
                            <div
                                class="pt-4 border-t border-gray-200 dark:border-gray-800"
                            >
                                <div
                                    class="flex justify-between items-baseline mb-2"
                                >
                                    <span
                                        class="text-sm font-medium text-gray-900 dark:text-white"
                                        >Total</span
                                    >
                                    <span
                                        class="text-xl font-medium text-gray-900 dark:text-white"
                                    >
                                        {{ formatPrice(booking.total_amount) }}
                                    </span>
                                </div>
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    Status:
                                    <span
                                        :class="
                                            booking.payment_status === 'paid'
                                                ? 'text-green-600 dark:text-green-400'
                                                : 'text-yellow-600 dark:text-yellow-400'
                                        "
                                        class="font-medium"
                                    >
                                        {{
                                            booking.payment_status === "paid"
                                                ? "Paid"
                                                : "Pending"
                                        }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Contact Property -->
                        <div
                            class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6"
                        >
                            <h3
                                class="text-sm font-medium text-gray-900 dark:text-white mb-4 flex items-center"
                            >
                                <Info class="w-4 h-4 mr-2" />
                                Need Help?
                            </h3>
                            <div class="space-y-3">
                                <a
                                    href="tel:+2348012345678"
                                    class="flex items-center text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"
                                >
                                    <Phone class="w-4 h-4 mr-2" />
                                    +234 801 234 5678
                                </a>
                                <a
                                    href="mailto:help@citystake.com"
                                    class="flex items-center text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"
                                >
                                    <Mail class="w-4 h-4 mr-2" />
                                    help@citystake.com
                                </a>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="space-y-3 no-print">
                            <!-- Complete Payment — only if unpaid -->
                            <Link
                                v-if="booking.payment_status !== 'paid'"
                                :href="
                                    route(
                                        'bookings.payment',
                                        booking.booking_reference,
                                    )
                                "
                                class="w-full flex items-center justify-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl transition-all"
                            >
                                <CreditCard class="w-4 h-4 mr-2" />
                                Complete Payment
                            </Link>

                            <!-- Print Receipt — only if paid -->
                            <button
                                v-if="booking.payment_status === 'paid'"
                                @click="printConfirmation"
                                class="w-full flex items-center justify-center px-4 py-2.5 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 text-white dark:text-gray-900 text-sm font-medium rounded-xl transition-all"
                            >
                                <Download class="w-4 h-4 mr-2" />
                                Print Receipt
                            </button>

                            <!-- Download Invoice — only if paid -->
                            <a
                                v-if="booking.payment_status === 'paid'"
                                :href="route('bookings.invoice', booking.id)"
                                target="_blank"
                                class="w-full flex items-center justify-center px-4 py-2.5 border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-xl transition-all"
                            >
                                <FileText class="w-4 h-4 mr-2" />
                                Download Invoice
                            </a>

                            <button
                                v-if="canCancel"
                                @click="openCancelModal"
                                class="w-full flex items-center justify-center px-4 py-2.5 border-2 border-red-200 dark:border-red-800 hover:border-red-300 dark:hover:border-red-700 text-red-600 dark:text-red-400 text-sm font-medium rounded-xl transition-all"
                            >
                                <XCircle class="w-4 h-4 mr-2" />
                                Cancel Booking
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Print Receipt (Hidden on screen) -->
                <div class="print-receipt">
                    <!-- Same compact receipt from Confirmation.vue -->
                    <div class="mb-6 pb-4 border-b border-gray-300">
                        <p class="text-xs text-gray-600 mb-1">
                            Booking Reference
                        </p>
                        <p class="text-lg font-mono font-bold text-gray-900">
                            {{ booking.booking_reference }}
                        </p>
                        <p class="text-xs text-gray-600 mt-1">
                            Booked on
                            {{
                                new Date(booking.created_at).toLocaleDateString(
                                    "en-GB",
                                    {
                                        day: "2-digit",
                                        month: "short",
                                        year: "numeric",
                                    },
                                )
                            }}
                        </p>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">
                            Property Details
                        </h3>
                        <p class="font-medium text-gray-900">
                            {{ booking.unit_type.name }}
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ booking.building.name }}
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ booking.building.address }},
                            {{ booking.building.city }}
                        </p>
                        <p class="text-sm text-gray-600 mt-1">
                            Unit: {{ booking.unit.unit_number }} -
                            {{ booking.unit.floor }} Floor
                        </p>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">
                            Stay Details
                        </h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-xs text-gray-600">Check-in</p>
                                <p class="font-medium text-gray-900">
                                    {{ formatDate(booking.check_in) }}
                                </p>
                                <p class="text-xs text-gray-600">
                                    After 2:00 PM
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600">Check-out</p>
                                <p class="font-medium text-gray-900">
                                    {{ formatDate(booking.check_out) }}
                                </p>
                                <p class="text-xs text-gray-600">
                                    Before 12:00 PM
                                </p>
                            </div>
                        </div>
                        <div class="mt-3 text-sm">
                            <p class="text-xs text-gray-600">
                                Duration & Guests
                            </p>
                            <p class="font-medium text-gray-900">
                                {{ booking.nights }} night(s) •
                                {{ booking.guests }} guest(s)
                            </p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">
                            Guest Information
                        </h3>
                        <div class="text-sm space-y-1">
                            <p>
                                <span class="text-gray-600">Name:</span>
                                <span class="font-medium text-gray-900">{{
                                    booking.guest_name
                                }}</span>
                            </p>
                            <p>
                                <span class="text-gray-600">Email:</span>
                                <span class="text-gray-900">{{
                                    booking.guest_email
                                }}</span>
                            </p>
                            <p>
                                <span class="text-gray-600">Phone:</span>
                                <span class="text-gray-900">{{
                                    booking.guest_phone
                                }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="mb-6 pb-4 border-b border-gray-300">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">
                            Payment Summary
                        </h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600"
                                    >{{ booking.nights }} night(s) ×
                                    {{
                                        formatPrice(
                                            booking.subtotal / booking.nights,
                                        )
                                    }}</span
                                >
                                <span class="text-gray-900">{{
                                    formatPrice(booking.subtotal)
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Cleaning fee</span>
                                <span class="text-gray-900">{{
                                    formatPrice(booking.cleaning_fee)
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600"
                                    >Service charge</span
                                >
                                <span class="text-gray-900">{{
                                    formatPrice(booking.service_charge)
                                }}</span>
                            </div>
                            <div
                                class="flex justify-between pt-2 border-t border-gray-200 font-semibold text-base"
                            >
                                <span class="text-gray-900">Total Paid</span>
                                <span class="text-gray-900">{{
                                    formatPrice(booking.total_amount)
                                }}</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-600 mt-2">
                            Payment Status:
                            <span class="font-medium text-green-600">{{
                                booking.payment_status
                            }}</span>
                        </p>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">
                            Important Information
                        </h3>
                        <ul class="text-xs text-gray-600 space-y-1">
                            <li>• Please bring a valid government-issued ID</li>
                            <li>• Contact us 24 hours before arrival</li>
                            <li>
                                • Free cancellation up to 24 hours before
                                check-in
                            </li>
                        </ul>
                    </div>

                    <div
                        class="text-center text-xs text-gray-500 pt-4 border-t border-gray-300"
                    >
                        <p>Thank you for choosing CityStake</p>
                        <p class="mt-1">info@csbookings.ninetentech.net</p>
                    </div>
                </div>

                <!-- Print-only Header -->
                <div class="print-only text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        CityStake
                    </h1>
                    <p class="text-sm text-gray-600">
                        Premium Short-let Apartments
                    </p>
                    <div class="mt-4 pt-4 border-t border-gray-300">
                        <h2 class="text-xl font-semibold text-gray-900">
                            Booking Receipt
                        </h2>
                    </div>
                </div>
            </div>
            <!-- ── Message Thread ── -->
            <div
                class="border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden mt-6"
            >
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2"
                >
                    <MessageSquare class="w-4 h-4 text-gray-400" />
                    <h3
                        class="text-sm font-medium text-gray-900 dark:text-white"
                    >
                        Messages
                    </h3>
                    <span
                        class="text-xs text-gray-400 dark:text-gray-500 ml-auto"
                        >Communicate with the property team</span
                    >
                </div>

                <!-- Thread -->
                <div
                    class="divide-y divide-gray-50 dark:divide-gray-800/50 max-h-96 overflow-y-auto"
                >
                    <div
                        v-if="!booking.messages?.length"
                        class="px-6 py-10 text-center"
                    >
                        <MessageSquare
                            class="w-8 h-8 text-gray-200 dark:text-gray-700 mx-auto mb-2"
                        />
                        <p class="text-sm text-gray-400 dark:text-gray-500">
                            No messages yet. Send a message to the property team
                            below.
                        </p>
                    </div>
                    <div
                        v-for="msg in booking.messages"
                        :key="msg.id"
                        class="px-6 py-4"
                        :class="
                            msg.sender_type === 'guest'
                                ? 'bg-white dark:bg-gray-950'
                                : 'bg-gray-50 dark:bg-gray-900/60'
                        "
                    >
                        <div class="flex items-center gap-2 mb-1.5">
                            <span
                                class="text-xs font-semibold"
                                :class="
                                    msg.sender_type === 'guest'
                                        ? 'text-gray-900 dark:text-white'
                                        : 'text-gray-500 dark:text-gray-400'
                                "
                            >
                                {{
                                    msg.sender_type === "guest"
                                        ? "You"
                                        : "Property Team"
                                }}
                            </span>
                            <span
                                class="text-[10px] text-gray-400 dark:text-gray-500"
                            >
                                {{
                                    new Date(msg.created_at).toLocaleString(
                                        "en-GB",
                                        {
                                            day: "2-digit",
                                            month: "short",
                                            hour: "2-digit",
                                            minute: "2-digit",
                                        },
                                    )
                                }}
                            </span>
                        </div>
                        <p
                            class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed"
                        >
                            {{ msg.body }}
                        </p>
                    </div>
                </div>

                <!-- Input -->
                <div
                    class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-950"
                >
                    <form @submit.prevent="sendMessage">
                        <textarea
                            v-model="messageBody"
                            rows="3"
                            placeholder="Type your message..."
                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none transition-all"
                        />
                        <div class="flex justify-end mt-2">
                            <button
                                type="submit"
                                :disabled="
                                    sendingMessage || !messageBody.trim()
                                "
                                class="px-5 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-xl hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all"
                            >
                                {{
                                    sendingMessage
                                        ? "Sending..."
                                        : "Send Message"
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Cancellation Modal -->
        <ConfirmationModal
            :show="showCancelModal"
            :processing="isCancelling"
            title="Cancel Booking?"
            message="Are you sure you want to cancel this booking? This action cannot be undone and you may be subject to our cancellation policy."
            confirm-text="Yes, Cancel Booking"
            cancel-text="Keep Booking"
            variant="danger"
            @confirm="cancelBooking"
            @close="closeCancelModal"
        />
    </AppLayout>
</template>
