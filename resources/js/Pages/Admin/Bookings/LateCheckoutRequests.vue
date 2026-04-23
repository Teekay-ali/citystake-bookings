<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Clock, CheckCircle2, XCircle, ChevronRight } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    requests: Object,
    filters:  Object,
    counts:   Object,
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

const tabs = [
    { value: '',         label: 'All'      },
    { value: 'pending',  label: 'Pending'  },
    { value: 'approved', label: 'Approved' },
    { value: 'settled',  label: 'Settled'  },
    { value: 'rejected', label: 'Rejected' },
]

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatAmount(n) {
    return '₦' + Number(n).toLocaleString('en-NG')
}
</script>

<template>
    <Head title="Late Checkout Requests" />

    <div class="p-6 lg:p-8">

        <!-- ── Header ── -->
        <div class="flex items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Late Checkout Requests</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Review and approve guest late checkout requests
                </p>
            </div>
            <div v-if="counts.pending > 0"
                 class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                <Clock class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" />
                <span class="text-xs font-medium text-amber-700 dark:text-amber-400">
                    {{ counts.pending }} pending
                </span>
            </div>
        </div>

        <!-- ── Filter tabs ── -->
        <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-900 rounded-xl p-1 w-fit mb-6">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                @click="status = tab.value"
                :class="status === tab.value
                    ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm'
                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-all">
                {{ tab.label }}
                <span v-if="tab.value === 'pending' && counts.pending > 0"
                      class="ml-1.5 text-xs font-semibold bg-amber-500 text-white px-1.5 py-0.5 rounded-md">
                    {{ counts.pending }}
                </span>
            </button>
        </div>

        <!-- ── Requests list ── -->
        <div class="space-y-2">
            <div
                v-for="booking in requests.data"
                :key="booking.id"
                class="border border-gray-200 dark:border-gray-800 rounded-xl p-5 hover:border-gray-300 dark:hover:border-gray-700 bg-white dark:bg-gray-900 transition-all">

                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">

                        <!-- Reference + status -->
                        <div class="flex items-center gap-2.5 mb-2">
                            <span class="text-xs font-mono font-medium text-gray-900 dark:text-white">
                                {{ booking.booking_reference }}
                            </span>
                            <span :class="[
                                statusConfig[booking.late_checkout_status].bg,
                                statusConfig[booking.late_checkout_status].text,
                                statusConfig[booking.late_checkout_status].border,
                                'text-xs font-medium px-2 py-0.5 rounded-lg border'
                            ]">
                                {{ statusConfig[booking.late_checkout_status].label }}
                            </span>
                        </div>

                        <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1.5">
                            {{ booking.guest_name }}
                        </p>

                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
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
                        <template v-if="booking.late_checkout_status === 'pending'">
                            <Link
                                :href="route('manage.bookings.late-checkout.approve', booking.id)"
                                method="post"
                                as="button"
                                :data="{ action: 'approved' }"
                                class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-xs font-medium hover:bg-gray-700 dark:hover:bg-gray-100 transition-all">
                                <CheckCircle2 class="w-3.5 h-3.5" />
                                Approve
                            </Link>
                            <Link
                                :href="route('manage.bookings.late-checkout.approve', booking.id)"
                                method="post"
                                as="button"
                                :data="{ action: 'rejected' }"
                                class="inline-flex items-center gap-1.5 px-3 py-2 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg text-xs font-medium transition-all">
                                <XCircle class="w-3.5 h-3.5" />
                                Reject
                            </Link>
                        </template>

                        <Link
                            v-else-if="booking.late_checkout_status === 'approved'"
                            :href="route('manage.bookings.late-checkout.settle', booking.id)"
                            method="post"
                            as="button"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-xs font-medium hover:bg-gray-700 dark:hover:bg-gray-100 transition-all">
                            Mark Settled
                        </Link>

                        <Link
                            :href="route('manage.bookings.show', booking.id)"
                            class="p-2 border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-all">
                            <ChevronRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="requests.data.length === 0" class="text-center py-20">
                <div class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-4">
                    <Clock class="w-6 h-6 text-gray-400" />
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">No late checkout requests found.</p>
            </div>
        </div>

    </div>
</template>
