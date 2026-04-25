<script setup>
import ManageLayout from "@/Layouts/ManageLayout.vue";
import { LogIn } from "lucide-vue-next";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { useToast } from "vue-toastification";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import {
    ArrowLeft,
    Mail,
    Phone,
    Download,
    XCircle,
    CheckCircle,
    Clock,
    AlertCircle,
    Home as HomeIcon,
    User,
    CreditCard,
    MessageSquare,
} from "lucide-vue-next";

const props = defineProps({
    booking: Object,
    messages: Array,
    unreadMessageCount: Number,
});

const toast = useToast();

const replyBody = ref("");
const sendingReply = ref(false);

const showCancelModal = ref(false);
const isCancelling = ref(false);

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

const getStatusBadge = () => {
    if (props.booking.status === "cancelled") {
        return {
            icon: XCircle,
            text: "Cancelled",
            class: "bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-2 border-red-200 dark:border-red-800",
        };
    } else if (props.booking.payment_status === "pending") {
        return {
            icon: AlertCircle,
            text: "Payment Pending",
            class: "bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 border-2 border-yellow-200 dark:border-yellow-800",
        };
    } else if (props.booking.check_out < new Date().toISOString()) {
        return {
            icon: CheckCircle,
            text: "Completed",
            class: "bg-gray-50 dark:bg-gray-900/20 text-gray-700 dark:text-gray-400 border-2 border-gray-200 dark:border-gray-800",
        };
    } else if (
        props.booking.check_in <= new Date().toISOString() &&
        props.booking.check_out >= new Date().toISOString()
    ) {
        return {
            icon: Clock,
            text: "Active Stay",
            class: "bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-2 border-blue-200 dark:border-blue-800",
        };
    } else {
        return {
            icon: CheckCircle,
            text: "Confirmed",
            class: "bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-2 border-green-200 dark:border-green-800",
        };
    }
};

const openCancelModal = () => {
    showCancelModal.value = true;
};

const cancelBooking = () => {
    isCancelling.value = true;
    router.post(
        route("bookings.cancel", props.booking.id),
        {},
        {
            onSuccess: () => {
                showCancelModal.value = false;
                toast.success("Booking cancelled successfully.");
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

const canCancel = () => {
    return (
        props.booking.status !== "cancelled" &&
        props.booking.status !== "completed" &&
        new Date(props.booking.check_in) > new Date()
    );
};

const checkInForm = useForm({
    amount_received: props.booking.total_amount,
    checkin_payment_method: "",
    checkin_notes: "",
});

function sendReply() {
    if (!replyBody.value.trim()) return;
    sendingReply.value = true;
    useForm({ body: replyBody.value }).post(
        route("manage.bookings.messages.send", props.booking.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                replyBody.value = "";
            },
            onFinish: () => {
                sendingReply.value = false;
            },
        },
    );
}

function submitCheckIn() {
    checkInForm.post(route("manage.bookings.check-in", props.booking.id), {
        preserveScroll: true,
    });
}

</script>

<template>
    <ManageLayout>
        <Head :title="`Booking ${booking.booking_reference} - Admin`" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <!-- Back Button -->
                <div class="mb-8">
                    <Link
                        :href="route('manage.bookings.index')"
                        class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                    >
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Back to all bookings
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
                            <span class="text-gray-300 dark:text-gray-700">•</span>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Created {{ formatDateTime(booking.created_at) }}
                            </p>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    <div
                        :class="getStatusBadge().class"
                        class="flex items-center px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap"
                    >
                        <component
                            :is="getStatusBadge().icon"
                            class="w-4 h-4 mr-2"
                        />
                        {{ getStatusBadge().text }}
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Guest Information -->
                        <div
                            class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6"
                        >
                            <h2
                                class="text-xl font-medium text-gray-900 dark:text-white mb-6 flex items-center"
                            >
                                <User class="w-5 h-5 mr-2" />
                                Guest Information
                            </h2>
                            <div class="space-y-4">
                                <div class="flex justify-between items-start">
                                    <span
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                        >Full Name</span
                                    >
                                    <span
                                        class="text-sm font-medium text-gray-900 dark:text-white"
                                        >{{ booking.guest_name }}</span
                                    >
                                </div>
                                <div class="flex justify-between items-start">
                                    <span
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                        >Email</span
                                    >
                                    <a
                                        :href="`mailto:${booking.guest_email}`"
                                        class="text-sm font-medium text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300 flex items-center"
                                    >
                                        <Mail class="w-4 h-4 mr-1" />
                                        {{ booking.guest_email }}
                                    </a>
                                </div>
                                <div class="flex justify-between items-start">
                                    <span
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                        >Phone</span
                                    >
                                    <a
                                        :href="`tel:${booking.guest_phone}`"
                                        class="text-sm font-medium text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300 flex items-center"
                                    >
                                        <Phone class="w-4 h-4 mr-1" />
                                        {{ booking.guest_phone }}
                                    </a>
                                </div>
                                <div class="flex justify-between items-start">
                                    <span
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                        >Number of Guests</span
                                    >
                                    <span
                                        class="text-sm font-medium text-gray-900 dark:text-white"
                                        >{{ booking.guests }}</span
                                    >
                                </div>
                                <div
                                    v-if="booking.user"
                                    class="flex justify-between items-start"
                                >
                                    <span
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                        >User Account</span
                                    >
                                    <span
                                        class="text-sm font-medium text-gray-900 dark:text-white"
                                        >{{ booking.user.email }}</span
                                    >
                                </div>
                            </div>

                            <!-- Special Requests -->
                            <div
                                v-if="booking.special_requests"
                                class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-900"
                            >
                                <p
                                    class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center"
                                >
                                    <MessageSquare class="w-4 h-4 mr-2" />
                                    Special Requests
                                </p>
                                <p
                                    class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 p-4 rounded-xl"
                                >
                                    {{ booking.special_requests }}
                                </p>
                            </div>

                        </div>

                        <!-- Property & Stay Details -->
                        <div
                            class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6"
                        >
                            <h2
                                class="text-xl font-medium text-gray-900 dark:text-white mb-6 flex items-center"
                            >
                                <HomeIcon class="w-5 h-5 mr-2" />
                                Property & Stay Details
                            </h2>

                            <!-- Property Info -->
                            <div class="mb-6">
                                <p
                                    class="text-sm text-gray-600 dark:text-gray-400 mb-2"
                                >
                                    Property
                                </p>
                                <p
                                    class="font-medium text-gray-900 dark:text-white"
                                >
                                    {{ booking.unit_type.name }}
                                </p>
                                <p
                                    class="text-sm text-gray-600 dark:text-gray-400 mt-1"
                                >
                                    {{ booking.building.name }}
                                </p>
                                <p
                                    class="text-sm text-gray-600 dark:text-gray-400"
                                >
                                    {{ booking.building.address }},
                                    {{ booking.building.city }}
                                </p>
                            </div>

                            <!-- Unit Assignment -->
                            <div class="mb-6">
                                <p
                                    class="text-sm text-gray-600 dark:text-gray-400 mb-2"
                                >
                                    Unit Assignment
                                </p>
                                <p
                                    class="font-medium text-gray-900 dark:text-white"
                                >
                                    Unit {{ booking.unit.unit_number }} •
                                    {{ booking.unit.floor }} Floor
                                </p>
                            </div>

                            <!-- Stay Dates -->
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <p
                                        class="text-sm text-gray-600 dark:text-gray-400 mb-2"
                                    >
                                        Check-in
                                    </p>
                                    <p
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {{ formatDate(booking.check_in) }}
                                    </p>
                                    <p
                                        class="text-xs text-gray-500 dark:text-gray-500 mt-1"
                                    >
                                        After 2:00 PM
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-sm text-gray-600 dark:text-gray-400 mb-2"
                                    >
                                        Check-out
                                    </p>
                                    <p
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {{ formatDate(booking.check_out) }}
                                    </p>
                                    <p
                                        class="text-xs text-gray-500 dark:text-gray-500 mt-1"
                                    >
                                        Before 12:00 PM
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <p
                                    class="text-sm text-gray-600 dark:text-gray-400"
                                >
                                    Duration
                                </p>
                                <p
                                    class="font-medium text-gray-900 dark:text-white"
                                >
                                    {{ booking.nights }} night{{
                                        booking.nights > 1 ? "s" : ""
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Guest Messages -->
                        <div
                            class="border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden"
                        >
                            <div
                                class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2"
                            >
                                <MessageSquare
                                    class="w-4 h-4 text-gray-400"
                                />
                                <h2
                                    class="text-sm font-semibold text-gray-900 dark:text-white"
                                >
                                    Guest Messages
                                </h2>
                                <span
                                    v-if="unreadMessageCount > 0"
                                    class="ml-2 text-[10px] font-bold bg-red-500 text-white px-1.5 py-0.5 rounded-full"
                                >
                                        {{ unreadMessageCount }} new
                                    </span>
                            </div>

                            <div
                                class="divide-y divide-gray-50 dark:divide-gray-800/50 max-h-80 overflow-y-auto"
                            >
                                <div
                                    v-if="!booking.messages?.length"
                                    class="px-6 py-8 text-center"
                                >
                                    <p
                                        class="text-sm text-gray-400 dark:text-gray-500"
                                    >
                                        No messages for this booking.
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
                                    <div
                                        class="flex items-center gap-2 mb-1.5"
                                    >
                                            <span
                                                class="text-xs font-semibold"
                                                :class="
                                                    msg.sender_type === 'guest'
                                                        ? 'text-gray-900 dark:text-white'
                                                        : 'text-blue-600 dark:text-blue-400'
                                                "
                                            >
                                                {{
                                                    msg.sender_type === "guest"
                                                        ? booking.guest_name
                                                        : (msg.sender?.name ??
                                                            "Staff")
                                                }}
                                            </span>
                                        <span
                                            class="text-[10px] text-gray-400"
                                        >
                                                {{
                                                new Date(
                                                    msg.created_at,
                                                ).toLocaleString("en-GB", {
                                                    day: "2-digit",
                                                    month: "short",
                                                    hour: "2-digit",
                                                    minute: "2-digit",
                                                })
                                            }}
                                            </span>
                                        <span
                                            v-if="
                                                    msg.sender_type ===
                                                        'guest' && !msg.read_at
                                                "
                                            class="ml-auto text-[9px] font-bold text-red-500 uppercase tracking-wider"
                                        >Unread</span
                                        >
                                    </div>
                                    <p
                                        class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed"
                                    >
                                        {{ msg.body }}
                                    </p>
                                </div>
                            </div>

                            <!-- Reply input -->
                            <div
                                class="px-6 py-4 border-t border-gray-100 dark:border-gray-800"
                            >
                                <form @submit.prevent="sendReply">
                                        <textarea
                                            v-model="replyBody"
                                            rows="2"
                                            placeholder="Reply to guest..."
                                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none transition-all"
                                        />
                                    <div class="flex justify-end mt-2">
                                        <button
                                            type="submit"
                                            :disabled="
                                                    sendingReply ||
                                                    !replyBody.trim()
                                                "
                                            class="px-5 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-xl hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all"
                                        >
                                            {{
                                                sendingReply
                                                    ? "Sending..."
                                                    : "Send Reply"
                                            }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Payment Information -->
                        <div
                            class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6"
                        >
                            <h2
                                class="text-xl font-medium text-gray-900 dark:text-white mb-6 flex items-center"
                            >
                                <CreditCard class="w-5 h-5 mr-2" />
                                Payment Information
                            </h2>

                            <div class="space-y-3 mb-6">
                                <div class="flex justify-between text-sm">
                                    <span
                                        class="text-gray-600 dark:text-gray-400"
                                        >{{ booking.nights }} night{{
                                            booking.nights > 1 ? "s" : ""
                                        }}
                                        ×
                                        {{
                                            formatPrice(
                                                booking.subtotal /
                                                    booking.nights,
                                            )
                                        }}</span
                                    >
                                    <span
                                        class="text-gray-900 dark:text-white"
                                        >{{
                                            formatPrice(booking.subtotal)
                                        }}</span
                                    >
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span
                                        class="text-gray-600 dark:text-gray-400"
                                        >Cleaning fee</span
                                    >
                                    <span
                                        class="text-gray-900 dark:text-white"
                                        >{{
                                            formatPrice(booking.cleaning_fee)
                                        }}</span
                                    >
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span
                                        class="text-gray-600 dark:text-gray-400"
                                        >Service charge</span
                                    >
                                    <span
                                        class="text-gray-900 dark:text-white"
                                        >{{
                                            formatPrice(booking.service_charge)
                                        }}</span
                                    >
                                </div>
                                <div
                                    v-if="booking.discount_amount > 0"
                                    class="flex justify-between items-center mt-2"
                                >
                                    <span
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                    >
                                        Discount ({{
                                            booking.discount_percent
                                        }}% -
                                        {{
                                            booking.discount_type ===
                                            "long_stay"
                                                ? "Long stay"
                                                : "Bulk booking"
                                        }})
                                    </span>
                                    <span
                                        class="text-sm font-medium text-emerald-600 dark:text-emerald-400"
                                    >
                                        − ₦{{
                                            Number(
                                                booking.discount_amount,
                                            ).toLocaleString()
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div
                                class="pt-4 border-t border-gray-200 dark:border-gray-800"
                            >
                                <div
                                    class="flex justify-between items-baseline mb-3"
                                >
                                    <span
                                        class="text-lg font-medium text-gray-900 dark:text-white"
                                        >Total Amount</span
                                    >
                                    <span
                                        class="text-2xl font-light text-gray-900 dark:text-white"
                                        >{{
                                            formatPrice(booking.total_amount)
                                        }}</span
                                    >
                                </div>
                                <div class="flex justify-between items-center">
                                    <span
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                        >Payment Status</span
                                    >
                                    <span
                                        :class="
                                            booking.payment_status === 'paid'
                                                ? 'text-green-600 dark:text-green-400'
                                                : 'text-yellow-600 dark:text-yellow-400'
                                        "
                                        class="text-sm font-medium"
                                    >
                                        {{
                                            booking.payment_status === "paid"
                                                ? "Paid"
                                                : "Pending Payment"
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-if="booking.paid_at"
                                    class="flex justify-between items-center mt-2"
                                >
                                    <span
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                        >Paid At</span
                                    >
                                    <span
                                        class="text-sm text-gray-900 dark:text-white"
                                        >{{
                                            formatDateTime(booking.paid_at)
                                        }}</span
                                    >
                                </div>
                                <div
                                    v-if="booking.paystack_reference"
                                    class="flex justify-between items-center mt-2"
                                >
                                    <span
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                        >Reference</span
                                    >
                                    <span
                                        class="text-sm font-mono text-gray-900 dark:text-white"
                                        >{{ booking.paystack_reference }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Quick Actions -->
                        <div
                            class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6"
                        >
                            <h3
                                class="text-lg font-medium text-gray-900 dark:text-white mb-4"
                            >
                                Quick Actions
                            </h3>
                            <div class="space-y-3">
                                <button
                                    @click="window.print()"
                                    class="w-full flex items-center justify-center px-4 py-2.5 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 text-white dark:text-gray-900 text-sm font-medium rounded-xl transition-all"
                                >
                                    <Download class="w-4 h-4 mr-2" />
                                    Print Details
                                </button>

                                <a
                                    :href="`mailto:${booking.guest_email}`"
                                    class="w-full flex items-center justify-center px-4 py-2.5 border-2 border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 text-gray-900 dark:text-white text-sm font-medium rounded-xl transition-all"
                                >
                                    <Mail class="w-4 h-4 mr-2" />
                                    Email Guest
                                </a>

                                <a
                                    :href="`tel:${booking.guest_phone}`"
                                    class="w-full flex items-center justify-center px-4 py-2.5 border-2 border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 text-gray-900 dark:text-white text-sm font-medium rounded-xl transition-all"
                                >
                                    <Phone class="w-4 h-4 mr-2" />
                                    Call Guest
                                </a>

                                <button
                                    v-if="canCancel()"
                                    @click="openCancelModal"
                                    class="w-full flex items-center justify-center px-4 py-2.5 border-2 border-red-200 dark:border-red-800 hover:border-red-300 dark:hover:border-red-700 text-red-600 dark:text-red-400 text-sm font-medium rounded-xl transition-all"
                                >
                                    <XCircle class="w-4 h-4 mr-2" />
                                    Cancel Booking
                                </button>
                            </div>
                        </div>

                        <!-- Check-in Confirmation -->
                        <div
                            v-if="
                                booking.status === 'confirmed' &&
                                booking.payment_status === 'paid'
                            "
                            class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6 mt-6"
                        >
                            <h3
                                class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-2"
                            >
                                <LogIn class="w-5 h-5" />
                                Check-in Guest
                            </h3>

                            <form
                                @submit.prevent="submitCheckIn"
                                class="space-y-4"
                            >
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        Amount Received (₦)
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="checkInForm.amount_received"
                                        type="number"
                                        step="0.01"
                                        :placeholder="booking.total_amount"
                                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                                        required
                                    />
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        Payment Method
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="
                                            checkInForm.checkin_payment_method
                                        "
                                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                                        required
                                    >
                                        <option value="">Select method</option>
                                        <option value="cash">Cash</option>
                                        <option value="pos">POS</option>
                                        <option value="bank_transfer">
                                            Bank Transfer
                                        </option>
                                        <option value="paystack">
                                            Paystack
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        Notes (optional)
                                    </label>
                                    <textarea
                                        v-model="checkInForm.checkin_notes"
                                        rows="2"
                                        placeholder="Any remarks..."
                                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none"
                                    />
                                </div>

                                <button
                                    type="submit"
                                    :disabled="checkInForm.processing"
                                    class="w-full px-4 py-3 bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white font-medium rounded-xl text-sm transition-all flex items-center justify-center gap-2"
                                >
                                    <LogIn class="w-4 h-4" />
                                    Confirm Check-in
                                </button>
                            </form>
                        </div>

                        <!-- Already checked in -->
                        <div
                            v-if="booking.status === 'checked_in'"
                            class="border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/20 rounded-2xl p-6 mt-6"
                        >
                            <div class="flex items-center gap-2 mb-3">
                                <LogIn
                                    class="w-5 h-5 text-violet-600 dark:text-violet-400"
                                />
                                <h3
                                    class="text-sm font-semibold text-violet-700 dark:text-violet-400"
                                >
                                    Checked In
                                </h3>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                        >Time</span
                                    >
                                    <span
                                        class="text-gray-900 dark:text-white"
                                        >{{
                                            formatDateTime(
                                                booking.checked_in_at,
                                            )
                                        }}</span
                                    >
                                </div>
                                <div class="flex justify-between">
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                        >By</span
                                    >
                                    <span
                                        class="text-gray-900 dark:text-white"
                                        >{{
                                            booking.checked_in_by_name ?? "—"
                                        }}</span
                                    >
                                </div>
                                <div class="flex justify-between">
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                        >Amount Received</span
                                    >
                                    <span
                                        class="font-medium text-gray-900 dark:text-white"
                                        >₦{{
                                            Number(
                                                booking.amount_received,
                                            ).toLocaleString()
                                        }}</span
                                    >
                                </div>
                                <div class="flex justify-between">
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                        >Method</span
                                    >
                                    <span
                                        class="capitalize text-gray-900 dark:text-white"
                                        >{{
                                            booking.checkin_payment_method?.replace(
                                                "_",
                                                " ",
                                            )
                                        }}</span
                                    >
                                </div>
                                <div
                                    v-if="booking.checkin_notes"
                                    class="pt-2 border-t border-violet-200 dark:border-violet-800"
                                >
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                        >Notes:
                                    </span>
                                    <span
                                        class="text-gray-900 dark:text-white"
                                        >{{ booking.checkin_notes }}</span
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Late Checkout -->
                        <div
                            class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6"
                        >
                            <h3
                                class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-2"
                            >
                                <Clock class="w-5 h-5" />
                                Late Checkout
                            </h3>

                            <!-- Can request -->
                            <div v-if="!booking.late_checkout_requested">
                                <p
                                    class="text-sm text-gray-500 dark:text-gray-400 mb-4"
                                >
                                    Standard checkout is 12:00 PM. A late
                                    checkout fee of
                                    <span
                                        class="font-medium text-gray-900 dark:text-white"
                                        >₦{{
                                            Number(20000).toLocaleString()
                                        }}</span
                                    >
                                    applies.
                                </p>
                                <Link
                                    v-if="
                                        ['confirmed', 'checked_in'].includes(
                                            booking.status,
                                        )
                                    "
                                    :href="
                                        route(
                                            'manage.bookings.late-checkout.request',
                                            booking.id,
                                        )
                                    "
                                    method="post"
                                    as="button"
                                    class="w-full text-center px-4 py-3 border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900 text-gray-900 dark:text-white rounded-xl text-sm font-medium transition-all"
                                >
                                    Request Late Checkout
                                </Link>
                            </div>

                            <!-- Pending approval -->
                            <div
                                v-else-if="
                                    booking.late_checkout_status === 'pending'
                                "
                                class="space-y-3"
                            >
                                <div
                                    class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl px-4 py-3"
                                >
                                    <p
                                        class="text-sm font-medium text-amber-700 dark:text-amber-400"
                                    >
                                        Awaiting Manager Approval
                                    </p>
                                    <p
                                        class="text-sm text-amber-600 dark:text-amber-500 mt-1"
                                    >
                                        Fee: ₦{{
                                            Number(
                                                booking.late_checkout_fee,
                                            ).toLocaleString()
                                        }}
                                    </p>
                                </div>
                                <!-- Manager/Admin approve or reject -->
                                <div
                                    v-if="
                                        $page.props.auth.user.roles.includes(
                                            'manager',
                                        ) ||
                                        $page.props.auth.user.roles.includes(
                                            'super-admin',
                                        )
                                    "
                                    class="flex gap-2"
                                >
                                    <Link
                                        :href="
                                            route(
                                                'manage.bookings.late-checkout.approve',
                                                booking.id,
                                            )
                                        "
                                        method="post"
                                        as="button"
                                        :data="{ action: 'approved' }"
                                        class="flex-1 text-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-medium transition-all"
                                    >
                                        Approve
                                    </Link>
                                    <Link
                                        :href="
                                            route(
                                                'manage.bookings.late-checkout.approve',
                                                booking.id,
                                            )
                                        "
                                        method="post"
                                        as="button"
                                        :data="{ action: 'rejected' }"
                                        class="flex-1 text-center px-4 py-2.5 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl text-sm font-medium transition-all"
                                    >
                                        Reject
                                    </Link>
                                </div>
                            </div>

                            <!-- Approved — awaiting payment -->
                            <div
                                v-else-if="
                                    booking.late_checkout_status === 'approved'
                                "
                                class="space-y-3"
                            >
                                <div
                                    class="bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800 rounded-xl px-4 py-3"
                                >
                                    <p
                                        class="text-sm font-medium text-violet-700 dark:text-violet-400"
                                    >
                                        Late Checkout Approved
                                    </p>
                                    <p
                                        class="text-sm text-violet-600 dark:text-violet-500 mt-1"
                                    >
                                        Fee of ₦{{
                                            Number(
                                                booking.late_checkout_fee,
                                            ).toLocaleString()
                                        }}
                                        added to total.
                                    </p>
                                </div>
                                <Link
                                    :href="
                                        route(
                                            'manage.bookings.late-checkout.settle',
                                            booking.id,
                                        )
                                    "
                                    method="post"
                                    as="button"
                                    class="w-full text-center px-4 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-sm font-medium hover:opacity-90 transition-all"
                                >
                                    Mark Fee as Settled
                                </Link>
                            </div>

                            <!-- Settled -->
                            <div
                                v-else-if="
                                    booking.late_checkout_status === 'settled'
                                "
                                class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl px-4 py-3"
                            >
                                <p
                                    class="text-sm font-medium text-emerald-700 dark:text-emerald-400"
                                >
                                    ✓ Late Checkout Fee Settled
                                </p>
                                <p
                                    class="text-sm text-emerald-600 dark:text-emerald-500 mt-1"
                                >
                                    ₦{{
                                        Number(
                                            booking.late_checkout_fee,
                                        ).toLocaleString()
                                    }}
                                    collected.
                                </p>
                            </div>

                            <!-- Rejected -->
                            <div
                                v-else-if="
                                    booking.late_checkout_status === 'rejected'
                                "
                                class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3"
                            >
                                <p
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Late checkout request was rejected.
                                </p>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div
                            class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6"
                        >
                            <h3
                                class="text-lg font-medium text-gray-900 dark:text-white mb-4"
                            >
                                Booking Timeline
                            </h3>
                            <div class="space-y-4">
                                <div class="flex items-start">
                                    <div
                                        class="w-2 h-2 rounded-full bg-green-500 mt-2 mr-3"
                                    ></div>
                                    <div class="flex-1">
                                        <p
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            Booking Created
                                        </p>
                                        <p
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            {{
                                                formatDateTime(
                                                    booking.created_at,
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <div
                                    v-if="booking.paid_at"
                                    class="flex items-start"
                                >
                                    <div
                                        class="w-2 h-2 rounded-full bg-green-500 mt-2 mr-3"
                                    ></div>
                                    <div class="flex-1">
                                        <p
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            Payment Confirmed
                                        </p>
                                        <p
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            {{
                                                formatDateTime(booking.paid_at)
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <div
                                    v-if="booking.cancelled_at"
                                    class="flex items-start"
                                >
                                    <div
                                        class="w-2 h-2 rounded-full bg-red-500 mt-2 mr-3"
                                    ></div>
                                    <div class="flex-1">
                                        <p
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            Booking Cancelled
                                        </p>
                                        <p
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            {{
                                                formatDateTime(
                                                    booking.cancelled_at,
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cancellation Modal -->
                <ConfirmationModal
                    :show="showCancelModal"
                    :processing="isCancelling"
                    title="Cancel This Booking?"
                    message="Are you sure you want to cancel this booking? This will free up the unit and notify the guest. This action cannot be undone."
                    confirm-text="Yes, Cancel Booking"
                    cancel-text="Keep Booking"
                    variant="danger"
                    @confirm="cancelBooking"
                    @close="closeCancelModal"
                />
            </div>
        </div>
    </ManageLayout>
</template>
