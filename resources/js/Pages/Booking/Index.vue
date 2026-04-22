<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import {
    Calendar, MapPin, Users, Clock,
    CheckCircle, XCircle, AlertCircle,
    ChevronRight, ArrowRight
} from 'lucide-vue-next'

const props = defineProps({
    bookings: Object,
})

const activeTab = ref('upcoming')

const tabs = [
    { key: 'upcoming',  label: 'Upcoming'  },
    { key: 'active',    label: 'Active'    },
    { key: 'past',      label: 'Past'      },
    { key: 'cancelled', label: 'Cancelled' },
]

const currentBookings = computed(() => props.bookings[activeTab.value] ?? [])

const totalCount = computed(() =>
    tabs.reduce((sum, t) => sum + (props.bookings[t.key]?.length ?? 0), 0)
)

const formatPrice = (price) => new Intl.NumberFormat('en-NG', {
    style: 'currency', currency: 'NGN',
    minimumFractionDigits: 0, maximumFractionDigits: 0,
}).format(price)

const formatDate = (date) => new Date(date).toLocaleDateString('en-GB', {
    day: '2-digit', month: 'short', year: 'numeric'
})

const formatDateShort = (date) => new Date(date).toLocaleDateString('en-GB', {
    day: '2-digit', month: 'short'
})

const nights = (checkIn, checkOut) => {
    const diff = new Date(checkOut) - new Date(checkIn)
    return Math.round(diff / (1000 * 60 * 60 * 24))
}

const statusMap = {
    cancelled:       { icon: XCircle,    text: 'Cancelled',       style: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800' },
    payment_pending: { icon: AlertCircle, text: 'Payment Pending', style: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800' },
    active:          { icon: Clock,       text: 'Active Stay',     style: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800' },
    checked_in:      { icon: Clock,       text: 'Checked In',      style: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800' },
    completed:       { icon: CheckCircle, text: 'Completed',       style: 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700' },
    confirmed:       { icon: CheckCircle, text: 'Confirmed',       style: 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800' },
}

const getStatus = (booking) => statusMap[booking.display_status] ?? statusMap['confirmed']
</script>

<template>
    <AppLayout :hide-footer="true">
        <Head title="My Bookings" />

        <div class="bg-white dark:bg-gray-950 min-h-screen">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 py-10">

                <!-- ── Header ── -->
                <div class="mb-8">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight">
                        My Bookings
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ totalCount }} booking{{ totalCount !== 1 ? 's' : '' }} across all stays
                    </p>
                </div>

                <!-- ── Tabs ── -->
                <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-900 rounded-xl p-1 mb-6 w-fit">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="activeTab === tab.key
                            ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all">
                        {{ tab.label }}
                        <span :class="activeTab === tab.key
                            ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                            : 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400'"
                              class="text-xs font-semibold px-1.5 py-0.5 rounded-md min-w-[20px] text-center">
                            {{ bookings[tab.key]?.length ?? 0 }}
                        </span>
                    </button>
                </div>

                <!-- ── Booking list ── -->
                <div v-if="currentBookings.length > 0" class="space-y-3">
                    <Link
                        v-for="booking in currentBookings"
                        :key="booking.id"
                        :href="route('bookings.show', booking.id)"
                        class="group flex flex-col sm:flex-row gap-0 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden hover:border-gray-300 dark:hover:border-gray-700 transition-all bg-white dark:bg-gray-900">

                        <!-- Image -->
                        <div class="sm:w-44 h-48 sm:h-auto bg-gray-100 dark:bg-gray-800 flex-shrink-0 overflow-hidden">
                            <img
                                v-if="booking.unit_type.images?.[0]"
                                :src="booking.unit_type.images[0].image_path"
                                :alt="booking.unit_type.name"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <Calendar class="w-8 h-8 text-gray-300 dark:text-gray-600" />
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0 p-5 flex flex-col justify-between">
                            <div>
                                <!-- Top row: title + status -->
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors">
                                            {{ booking.unit_type.name }}
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-1">
                                            <MapPin class="w-3 h-3 flex-shrink-0" />
                                            {{ booking.building.name }}
                                        </p>
                                    </div>
                                    <span :class="getStatus(booking).style"
                                          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium flex-shrink-0">
                                        <component :is="getStatus(booking).icon" class="w-3 h-3" />
                                        {{ getStatus(booking).text }}
                                    </span>
                                </div>

                                <!-- Date + nights strip -->
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                        <Calendar class="w-3 h-3" />
                                        {{ formatDateShort(booking.check_in) }}
                                        <span class="text-gray-300 dark:text-gray-600">→</span>
                                        {{ formatDateShort(booking.check_out) }}
                                    </div>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">·</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ nights(booking.check_in, booking.check_out) }} night{{ nights(booking.check_in, booking.check_out) !== 1 ? 's' : '' }}
                                    </span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">·</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                        <Users class="w-3 h-3" />
                                        {{ booking.guests }} guest{{ booking.guests !== 1 ? 's' : '' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Bottom row: ref + amount + arrow -->
                            <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800">
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-gray-400 dark:text-gray-500 font-mono">
                                        {{ booking.booking_reference }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ formatPrice(booking.total_amount) }}
                                    </span>
                                    <ArrowRight class="w-3.5 h-3.5 text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-200 group-hover:translate-x-0.5 transition-all" />
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- ── Empty state ── -->
                <div v-else class="text-center py-20">
                    <div class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-4">
                        <Calendar class="w-6 h-6 text-gray-400" />
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">
                        No {{ activeTab }} bookings
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                        {{ activeTab === 'upcoming'
                        ? 'Start planning your next stay.'
                        : `You don't have any ${activeTab} bookings.` }}
                    </p>
                    <Link
                        v-if="activeTab === 'upcoming'"
                        :href="route('properties.index')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 transition-all">
                        Browse Properties
                        <ArrowRight class="w-3.5 h-3.5" />
                    </Link>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
