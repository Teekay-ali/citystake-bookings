<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Clock, CheckCircle2, XCircle, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
    requests: Object,
    filters: Object,
    counts: Object,
})

const status = ref(props.filters.status || '')

watch(status, () => {
    router.get(route('manage.bookings.late-checkout.index'), {
        status: status.value || undefined,
    }, { preserveState: true, replace: true })
})

const statusConfig = {
    pending:  { label: 'Pending',  bg: 'bg-amber-50 dark:bg-amber-900/20',   text: 'text-amber-700 dark:text-amber-400',   border: 'border-amber-200 dark:border-amber-800' },
    approved: { label: 'Approved', bg: 'bg-violet-50 dark:bg-violet-900/20', text: 'text-violet-700 dark:text-violet-400', border: 'border-violet-200 dark:border-violet-800' },
    settled:  { label: 'Settled',  bg: 'bg-emerald-50 dark:bg-emerald-900/20', text: 'text-emerald-700 dark:text-emerald-400', border: 'border-emerald-200 dark:border-emerald-800' },
    rejected: { label: 'Rejected', bg: 'bg-red-50 dark:bg-red-900/20',       text: 'text-red-700 dark:text-red-400',       border: 'border-red-200 dark:border-red-800' },
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatAmount(n) {
    return '₦' + Number(n).toLocaleString('en-NG')
}
</script>

<template>
    <ManageLayout>
        <Head title="Late Checkout Requests" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-8">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">

                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-1">
                            Late Checkout Requests
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400">Review and approve guest late checkout requests</p>
                    </div>
                    <div v-if="counts.pending > 0"
                         class="px-4 py-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-full">
                        <span class="text-sm font-medium text-amber-700 dark:text-amber-400">
                            {{ counts.pending }} pending
                        </span>
                    </div>
                </div>

                <!-- Filter tabs -->
                <div class="flex items-center gap-2 mb-8 flex-wrap">
                    <button v-for="tab in [
                        { value: '', label: 'All' },
                        { value: 'pending', label: 'Pending' },
                        { value: 'approved', label: 'Approved' },
                        { value: 'settled', label: 'Settled' },
                        { value: 'rejected', label: 'Rejected' },
                    ]" :key="tab.value"
                            @click="status = tab.value"
                            :class="status === tab.value
                            ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                            : 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800'"
                            class="px-4 py-2 rounded-full text-sm font-medium transition-all">
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Requests list -->
                <div class="space-y-4">
                    <div v-for="booking in requests.data" :key="booking.id"
                         class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6 hover:border-gray-300 dark:hover:border-gray-700 transition-all">

                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="text-sm font-mono font-medium text-gray-900 dark:text-white">
                                        {{ booking.booking_reference }}
                                    </span>
                                    <span :class="[
                                        statusConfig[booking.late_checkout_status].bg,
                                        statusConfig[booking.late_checkout_status].text,
                                        statusConfig[booking.late_checkout_status].border,
                                        'text-xs font-medium px-2.5 py-1 rounded-full border'
                                    ]">
                                        {{ statusConfig[booking.late_checkout_status].label }}
                                    </span>
                                </div>

                                <p class="font-medium text-gray-900 dark:text-white mb-1">{{ booking.guest_name }}</p>

                                <div class="flex flex-wrap gap-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span>{{ booking.building?.name }}</span>
                                    <span>Unit {{ booking.unit?.unit_number }}</span>
                                    <span>Checkout: {{ formatDate(booking.check_out) }}</span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">
                                        Fee: {{ formatAmount(booking.late_checkout_fee) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 shrink-0">
                                <!-- Approve/Reject for pending -->
                                <template v-if="booking.late_checkout_status === 'pending'">
                                    <Link
                                        :href="route('manage.bookings.late-checkout.approve', booking.id)"
                                        method="post"
                                        as="button"
                                        :data="{ action: 'approved' }"
                                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-medium transition-all flex items-center gap-1">
                                        <CheckCircle2 class="w-4 h-4" />
                                        Approve
                                    </Link>
                                    <Link
                                        :href="route('manage.bookings.late-checkout.approve', booking.id)"
                                        method="post"
                                        as="button"
                                        :data="{ action: 'rejected' }"
                                        class="px-4 py-2 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl text-sm font-medium transition-all flex items-center gap-1">
                                        <XCircle class="w-4 h-4" />
                                        Reject
                                    </Link>
                                </template>

                                <!-- Settle for approved -->
                                <Link
                                    v-else-if="booking.late_checkout_status === 'approved'"
                                    :href="route('manage.bookings.late-checkout.settle', booking.id)"
                                    method="post"
                                    as="button"
                                    class="px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-sm font-medium hover:opacity-90 transition-all">
                                    Mark Settled
                                </Link>

                                <!-- View booking -->
                                <Link
                                    :href="route('manage.bookings.show', booking.id)"
                                    class="p-2 border border-gray-200 dark:border-gray-800 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-900 text-gray-500 transition-all">
                                    <ChevronRight class="w-4 h-4" />
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-if="requests.data.length === 0" class="text-center py-16">
                        <Clock class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                        <p class="text-gray-500 dark:text-gray-400">No late checkout requests found.</p>
                    </div>
                </div>
            </div>
        </div>
    </ManageLayout>
</template>
