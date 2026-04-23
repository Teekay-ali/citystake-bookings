<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import {
    Search, Plus, Download, Eye,
    CheckCircle, XCircle, Clock, AlertCircle,
    ChevronRight, ChevronLeft,
} from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    bookings:  Object,
    buildings: Array,
    filters:   Object,
})

const search        = ref(props.filters.search || '')
const status        = ref(props.filters.status || '')
const building      = ref(props.filters.building || '')
const paymentStatus = ref(props.filters.payment_status || '')
const sortBy        = ref(props.filters.sort_by || 'created_at')
const sortOrder     = ref(props.filters.sort_order || 'desc')

let searchTimeout = null

watch(search, () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(applyFilters, 500)
})

watch([status, building, paymentStatus, sortBy, sortOrder], applyFilters)

function applyFilters() {
    router.get(route('manage.bookings.index'), {
        search:         search.value,
        status:         status.value,
        building:       building.value,
        payment_status: paymentStatus.value,
        sort_by:        sortBy.value,
        sort_order:     sortOrder.value,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value        = ''
    status.value        = ''
    building.value      = ''
    paymentStatus.value = ''
}

function exportBookings() {
    const params = new URLSearchParams()
    if (status.value)        params.append('status', status.value)
    if (paymentStatus.value) params.append('payment_status', paymentStatus.value)
    if (building.value)      params.append('building_id', building.value)
    if (search.value)        params.append('search', search.value)
    window.location.href = route('manage.bookings.export') + (params.toString() ? '?' + params.toString() : '')
}

const formatPrice = (price) => new Intl.NumberFormat('en-NG', {
    style: 'currency', currency: 'NGN',
    minimumFractionDigits: 0, maximumFractionDigits: 0,
}).format(price)

const formatDate = (date) => new Date(date).toLocaleDateString('en-GB', {
    day: '2-digit', month: 'short', year: 'numeric'
})

const statusMap = {
    cancelled:       { icon: XCircle,     text: 'Cancelled',       cls: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800' },
    payment_pending: { icon: AlertCircle, text: 'Payment Pending',  cls: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800' },
    active:          { icon: Clock,       text: 'Active Stay',      cls: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800' },
    checked_in:      { icon: Clock,       text: 'Checked In',       cls: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800' },
    completed:       { icon: CheckCircle, text: 'Completed',        cls: 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700' },
    confirmed:       { icon: CheckCircle, text: 'Confirmed',        cls: 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800' },
}

const getStatus = (booking) => statusMap[booking.display_status] ?? statusMap['confirmed']

const hasActiveFilters = () => status.value || building.value || paymentStatus.value || search.value

const selectClass = "w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
</script>

<template>
    <Head title="All Bookings" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 p-6 lg:p-8">

        <!-- ── Header ── -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">All Bookings</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ bookings.total }} total bookings
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    @click="exportBookings"
                    class="inline-flex items-center gap-2 px-3 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                    <Download class="w-3.5 h-3.5" />
                    Export
                </button>
                <Link
                    :href="route('manage.bookings.create')"
                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg transition-all">
                    <Plus class="w-3.5 h-3.5" />
                    New Booking
                </Link>
            </div>
        </div>

        <!-- ── Filters ── -->
        <div class="flex flex-wrap items-center gap-2 mb-4">

            <!-- Search -->
            <div class="relative">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search reference, name..."
                    class="pl-9 pr-4 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all w-56" />
            </div>

            <select v-model="status" :class="selectClass" style="width: auto">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="upcoming">Upcoming</option>
                <option value="past">Past</option>
                <option value="pending">Pending</option>
                <option value="cancelled">Cancelled</option>
            </select>

            <select v-model="building" :class="selectClass" style="width: auto">
                <option value="">All properties</option>
                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>

            <select v-model="paymentStatus" :class="selectClass" style="width: auto">
                <option value="">All payments</option>
                <option value="paid">Paid</option>
                <option value="pending">Pending</option>
            </select>

            <button
                v-if="hasActiveFilters()"
                @click="clearFilters"
                class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                Clear filters
            </button>

            <span class="ml-auto text-xs text-gray-400 dark:text-gray-500">
                {{ bookings.data.length }} of {{ bookings.total }} shown
            </span>
        </div>

        <!-- ── Table ── -->
        <div class="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">

            <!-- Desktop table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Reference</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Guest</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Property</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Dates</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-950 divide-y divide-gray-100 dark:divide-gray-800">
                    <tr
                        v-for="booking in bookings.data"
                        :key="booking.id"
                        class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                        <td class="px-5 py-3.5">
                            <p class="text-xs font-mono font-medium text-gray-900 dark:text-white">{{ booking.booking_reference }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ formatDate(booking.created_at) }}</p>
                        </td>
                        <td class="px-5 py-3.5">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ booking.guest_name }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ booking.guests }} guest{{ booking.guests > 1 ? 's' : '' }}</p>
                        </td>
                        <td class="px-5 py-3.5">
                            <p class="text-sm text-gray-900 dark:text-white">{{ booking.unit_type.name }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ booking.building.name }}</p>
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <p class="text-sm text-gray-900 dark:text-white">{{ formatDate(booking.check_in) }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ booking.nights }} night{{ booking.nights > 1 ? 's' : '' }}</p>
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatPrice(booking.total_amount) }}</p>
                            <p class="text-xs mt-0.5" :class="booking.payment_status === 'paid' ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'">
                                {{ booking.payment_status === 'paid' ? 'Paid' : 'Pending' }}
                            </p>
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap">
                                <span :class="getStatus(booking).cls"
                                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium">
                                    <component :is="getStatus(booking).icon" class="w-3 h-3" />
                                    {{ getStatus(booking).text }}
                                </span>
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap">
                            <Link
                                :href="route('manage.bookings.show', booking.id)"
                                class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                <Eye class="w-3.5 h-3.5" />
                                View
                            </Link>
                        </td>
                    </tr>

                    <!-- Empty state inside table -->
                    <tr v-if="bookings.data.length === 0">
                        <td colspan="7" class="px-5 py-16 text-center">
                            <p class="text-sm text-gray-400 dark:text-gray-500">No bookings found</p>
                            <button
                                v-if="hasActiveFilters()"
                                @click="clearFilters"
                                class="mt-2 text-xs text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white underline transition-colors">
                                Clear filters
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile card list -->
            <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-800">
                <Link
                    v-for="booking in bookings.data"
                    :key="booking.id"
                    :href="route('manage.bookings.show', booking.id)"
                    class="flex items-start justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors">
                    <div class="space-y-0.5 min-w-0 flex-1 pr-3">
                        <p class="text-xs font-mono font-medium text-gray-900 dark:text-white truncate">{{ booking.booking_reference }}</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ booking.guest_name }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ booking.building?.name }} · {{ booking.unit_type?.name }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ formatDate(booking.check_in) }} → {{ formatDate(booking.check_out) }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-1.5 shrink-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatPrice(booking.total_amount) }}</p>
                        <span :class="[getStatus(booking).cls, 'inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-medium']">
                            {{ getStatus(booking).text }}
                        </span>
                    </div>
                </Link>

                <div v-if="bookings.data.length === 0" class="p-10 text-center text-sm text-gray-400">
                    No bookings found
                </div>
            </div>
        </div>

        <!-- ── Pagination ── -->
        <div v-if="bookings.last_page > 1" class="flex justify-center items-center gap-1 mt-6">
            <button
                @click="bookings.prev_page_url && router.visit(bookings.prev_page_url)"
                :disabled="!bookings.prev_page_url"
                class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-900 hover:text-gray-900 dark:hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition-all">
                <ChevronLeft class="w-3.5 h-3.5" />
            </button>

            <template v-for="link in bookings.links.slice(1, -1)" :key="link.label">
                <button
                    v-if="link.label !== '...'"
                    @click="link.url && router.visit(link.url)"
                    :disabled="!link.url"
                    :class="[
                        'w-8 h-8 flex items-center justify-center rounded-lg text-sm font-medium transition-all',
                        link.active
                            ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                            : 'border border-gray-200 dark:border-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-900 hover:text-gray-900 dark:hover:text-white'
                    ]">
                    {{ link.label }}
                </button>
                <span v-else class="w-8 h-8 flex items-center justify-center text-sm text-gray-400">…</span>
            </template>

            <button
                @click="bookings.next_page_url && router.visit(bookings.next_page_url)"
                :disabled="!bookings.next_page_url"
                class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-900 hover:text-gray-900 dark:hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition-all">
                <ChevronRight class="w-3.5 h-3.5" />
            </button>
        </div>

    </div>
</template>
