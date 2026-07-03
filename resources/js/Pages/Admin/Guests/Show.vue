<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import { ArrowLeft, Mail, Phone, Calendar, CreditCard, TrendingUp, UserX, UserCheck } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    guest:    Object,
    bookings: Array,
    stats:    Object,
})

const showToggleModal = ref(false)
const isToggling      = ref(false)

const formatPrice = (v) => new Intl.NumberFormat('en-NG', {
    style: 'currency', currency: 'NGN', minimumFractionDigits: 0,
}).format(v || 0)

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-NG', {
    day: 'numeric', month: 'short', year: 'numeric',
}) : '—'

const statusClass = (status) => ({
    confirmed:   'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800',
    checked_in:  'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800',
    completed:   'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700',
    cancelled:   'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800',
    pending:     'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
}[status] ?? 'bg-gray-50 text-gray-700 border-gray-200')

function toggleActive() {
    isToggling.value = true
    router.post(route('manage.guests.toggle-active', props.guest.id), {}, {
        onFinish: () => { isToggling.value = false; showToggleModal.value = false },
    })
}
</script>

<template>
    <Head :title="guest.name" />

    <div class="p-6 lg:p-8 max-w-4xl space-y-6">

        <!-- Header -->
        <div class="flex items-center gap-4">
            <Link
                :href="route('manage.guests.index')"
                class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 transition-colors"
            >
                <ArrowLeft class="w-5 h-5" />
            </Link>
            <div class="flex-1">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">{{ guest.name }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Guest since {{ formatDate(guest.created_at) }}</p>
            </div>
            <button
                @click="showToggleModal = true"
                :class="guest.is_active
                    ? 'border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20'
                    : 'border-green-200 dark:border-green-800 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20'"
                class="flex items-center gap-2 px-4 py-2 border text-sm font-medium rounded-xl transition-all"
            >
                <component :is="guest.is_active ? UserX : UserCheck" class="w-4 h-4" />
                {{ guest.is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </div>

        <!-- Profile + Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <!-- Profile card -->
            <div class="md:col-span-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 space-y-4">
                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Profile</h2>

                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <Mail class="w-4 h-4 text-gray-400 flex-shrink-0" />
                        <p class="text-sm text-gray-900 dark:text-white break-all">{{ guest.email }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Phone class="w-4 h-4 text-gray-400 flex-shrink-0" />
                        <p class="text-sm text-gray-900 dark:text-white">{{ guest.phone ?? 'Not provided' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Calendar class="w-4 h-4 text-gray-400 flex-shrink-0" />
                        <p class="text-sm text-gray-900 dark:text-white">Joined {{ formatDate(guest.created_at) }}</p>
                    </div>
                </div>

                <div class="pt-2 border-t border-gray-100 dark:border-gray-900">
                    <span :class="guest.is_active
                        ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800'
                        : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800'"
                          class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border"
                    >
                        {{ guest.is_active ? 'Active Account' : 'Deactivated' }}
                    </span>
                </div>
            </div>

            <!-- Stats -->
            <div class="md:col-span-2 grid grid-cols-2 gap-4">
                <div
                    v-for="stat in [
                        { label: 'Total Bookings',  value: stats.total_bookings },
                        { label: 'Completed Stays', value: stats.completed_stays },
                        { label: 'Cancelled',       value: stats.cancelled },
                        { label: 'Last Stay',       value: formatDate(stats.last_stay) },
                    ]"
                    :key="stat.label"
                    class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-4"
                >
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">{{ stat.label }}</p>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ stat.value }}</p>
                </div>

                <!-- Total spend full width -->
                <div class="col-span-2 bg-gray-900 dark:bg-white rounded-xl p-4">
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">Total Spend</p>
                    <p class="text-2xl font-light text-white dark:text-gray-900">{{ formatPrice(stats.total_spend) }}</p>
                </div>
            </div>
        </div>

        <!-- Booking History -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                <h2 class="text-sm font-medium text-gray-900 dark:text-white">Booking History</h2>
            </div>

            <div v-if="!bookings.length" class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400 text-sm">No bookings yet.</p>
            </div>

            <table v-else class="hidden md:table w-full text-sm">
                <thead class="border-b border-gray-100 dark:border-gray-800">
                <tr class="text-left">
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Reference</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Property</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Check-in</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Check-out</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Amount</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-for="booking in bookings" :key="booking.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                    <td class="px-6 py-3">
                        <Link :href="route('manage.bookings.show', booking.booking_reference)"
                              class="font-mono text-xs text-gray-900 dark:text-white hover:underline">
                            {{ booking.booking_reference }}
                        </Link>
                    </td>
                    <td class="px-6 py-3 text-gray-600 dark:text-gray-400 text-xs">
                        {{ booking.building?.name }} · {{ booking.unit_type?.name }}
                    </td>
                    <td class="px-6 py-3 text-gray-600 dark:text-gray-400 text-xs">{{ formatDate(booking.check_in) }}</td>
                    <td class="px-6 py-3 text-gray-600 dark:text-gray-400 text-xs">{{ formatDate(booking.check_out) }}</td>
                    <td class="px-6 py-3 font-medium text-gray-900 dark:text-white text-xs">{{ formatPrice(booking.total_amount) }}</td>
                    <td class="px-6 py-3">
                            <span :class="['inline-flex px-2 py-0.5 rounded-full text-xs font-medium border', statusClass(booking.status)]">
                                {{ booking.status }}
                            </span>
                    </td>
                </tr>
                </tbody>
            </table>

            <!-- Mobile bookings -->
            <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-800">
                <Link v-for="booking in bookings" :key="booking.id"
                      :href="route('manage.bookings.show', booking.booking_reference)"
                      class="flex items-start justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors">
                    <div class="space-y-0.5">
                        <p class="text-xs font-mono font-medium text-gray-900 dark:text-white">{{ booking.booking_reference }}</p>
                        <p class="text-xs text-gray-500">{{ booking.building?.name }}</p>
                        <p class="text-xs text-gray-400">{{ formatDate(booking.check_in) }} → {{ formatDate(booking.check_out) }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatPrice(booking.total_amount) }}</p>
                        <span :class="['inline-flex px-2 py-0.5 rounded-full text-xs font-medium border', statusClass(booking.status)]">
                            {{ booking.status }}
                        </span>
                    </div>
                </Link>
            </div>
        </div>
    </div>

    <!-- Toggle Active Modal -->
    <ConfirmationModal
        :show="showToggleModal"
        :title="guest.is_active ? 'Deactivate Account' : 'Activate Account'"
        :message="guest.is_active
            ? `Deactivating ${guest.name}'s account will prevent them from logging in or making new bookings.`
            : `This will restore ${guest.name}'s access to the platform.`"
        :confirm-text="guest.is_active ? 'Deactivate' : 'Activate'"
        :variant="guest.is_active ? 'danger' : 'default'"
        :processing="isToggling"
        @confirm="toggleActive"
        @close="showToggleModal = false"
    />
</template>
