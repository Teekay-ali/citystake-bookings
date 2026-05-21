<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Banknote, CheckCircle, Clock, ArrowRight } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    deposits: Object,
    summary:  Object,
    buildings: Array,
    filters:  Object,
})

const filter = ref(props.filters.filter ?? 'outstanding')

function applyFilter(val) {
    filter.value = val
    router.get(route('manage.financials.deposits'), { filter: val }, {
        preserveState: true, replace: true,
    })
}

const formatPrice = (n) => '₦' + Number(n).toLocaleString('en-NG', { minimumFractionDigits: 0 })
const formatDate  = (d) => new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
</script>

<template>
    <Head title="Security Deposits" />

    <div class="p-6 lg:p-8">

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Security Deposits</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Track collected and refunded deposits across all bookings</p>
            </div>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                <p class="text-xs font-medium text-amber-600 dark:text-amber-400 uppercase tracking-wider mb-2">Outstanding</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ formatPrice(summary.total_outstanding) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ summary.count_outstanding }} booking{{ summary.count_outstanding !== 1 ? 's' : '' }} pending refund</p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
                <p class="text-xs font-medium text-green-600 dark:text-green-400 uppercase tracking-wider mb-2">Refunded</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ formatPrice(summary.total_refunded) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Total Collected</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ formatPrice(summary.total_outstanding + summary.total_refunded) }}
                </p>
            </div>
        </div>

        <!-- Filter tabs -->
        <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-900 rounded-xl p-1 w-fit mb-6">
            <button
                v-for="tab in [
                    { label: 'Outstanding', value: 'outstanding' },
                    { label: 'Refunded', value: 'refunded' },
                    { label: 'All', value: 'all' },
                ]"
                :key="tab.value"
                @click="applyFilter(tab.value)"
                :class="filter === tab.value
                    ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm'
                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- Table -->
        <div class="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Reference</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Guest</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Property</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Checkout</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Deposit</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-950">
                    <tr v-if="!deposits.data.length">
                        <td colspan="7" class="px-5 py-16 text-center text-sm text-gray-400">
                            No deposits found.
                        </td>
                    </tr>
                    <tr v-for="booking in deposits.data" :key="booking.id"
                        class="hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors">
                        <td class="px-5 py-3.5">
                            <span class="text-xs font-mono font-medium text-gray-900 dark:text-white">{{ booking.booking_reference }}</span>
                        </td>
                        <td class="px-5 py-3.5">
                            <p class="text-sm text-gray-900 dark:text-white">{{ booking.guest_name }}</p>
                            <p class="text-xs text-gray-400">{{ booking.guest_phone }}</p>
                        </td>
                        <td class="px-5 py-3.5">
                            <p class="text-sm text-gray-900 dark:text-white">{{ booking.unit_type?.name }}</p>
                            <p class="text-xs text-gray-400">{{ booking.building?.name }} · Unit {{ booking.unit?.unit_number }}</p>
                        </td>
                        <td class="px-5 py-3.5 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                            {{ formatDate(booking.check_out) }}
                        </td>
                        <td class="px-5 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                            {{ formatPrice(booking.security_deposit) }}
                        </td>
                        <td class="px-5 py-3.5">
                                <span v-if="booking.security_deposit_refunded"
                                      class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400">
                                    <CheckCircle class="w-3 h-3" />
                                    Refunded
                                </span>
                            <span v-else
                                  class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400">
                                    <Clock class="w-3 h-3" />
                                    Outstanding
                                </span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <Link :href="route('manage.bookings.show', booking.id)"
                                  class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                View <ArrowRight class="w-3 h-3" />
                            </Link>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile cards -->
            <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-800">
                <div v-if="!deposits.data.length" class="p-8 text-center text-sm text-gray-400">No deposits found.</div>
                <Link v-for="booking in deposits.data" :key="booking.id"
                      :href="route('manage.bookings.show', booking.id)"
                      class="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors">
                    <div class="min-w-0">
                        <p class="text-xs font-mono font-medium text-gray-900 dark:text-white">{{ booking.booking_reference }}</p>
                        <p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ booking.guest_name }}</p>
                        <p class="text-xs text-gray-400">{{ booking.building?.name }} · Checkout {{ formatDate(booking.check_out) }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-1.5 flex-shrink-0 ml-4">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatPrice(booking.security_deposit) }}</p>
                        <span :class="booking.security_deposit_refunded
                            ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400'
                            : 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400'"
                              class="text-xs px-2 py-0.5 rounded-lg font-medium">
                            {{ booking.security_deposit_refunded ? 'Refunded' : 'Outstanding' }}
                        </span>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="deposits.last_page > 1" class="flex justify-center gap-1.5 mt-6">
            <Link v-for="link in deposits.links" :key="link.label"
                  :href="link.url || '#'"
                  :class="[
                      'min-w-[36px] h-9 flex items-center justify-center px-3 rounded-lg text-sm transition-all',
                      link.active ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium' : 'border border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800',
                      !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                  ]"
                  v-html="link.label" />
        </div>
    </div>
</template>
