<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import {
    Plus, Clock, CheckCircle, XCircle, Banknote,
    ChevronRight, Building2, Filter
} from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    approvals: Object,
    summary:   Object,
    buildings: Array,
    filters:   Object,
})

const statusFilter   = ref(props.filters?.status ?? '')
const buildingFilter = ref(props.filters?.building ?? '')

const formatPrice = (v) => new Intl.NumberFormat('en-NG', {
    style: 'currency', currency: 'NGN', minimumFractionDigits: 0,
}).format(v)

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-NG', {
    day: 'numeric', month: 'short', year: 'numeric',
}) : '—'

const statusConfig = {
    pending:  { label: 'Pending',  color: 'amber',  icon: Clock },
    approved: { label: 'Approved', color: 'blue',   icon: CheckCircle },
    declined: { label: 'Declined', color: 'red',    icon: XCircle },
    paid:     { label: 'Paid',     color: 'green',  icon: Banknote },
}

const statusClass = (status) => ({
    amber: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
    blue:  'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800',
    red:   'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800',
    green: 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800',
}[statusConfig[status]?.color ?? 'amber'])

function applyFilters() {
    router.get(route('manage.payment-approvals.index'), {
        status:   statusFilter.value || undefined,
        building: buildingFilter.value || undefined,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    statusFilter.value   = ''
    buildingFilter.value = ''
    applyFilters()
}
</script>

<template>

    <Head title="Payment Approvals" />

    <div class="p-6 lg:p-8 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-light text-gray-900 dark:text-white">Payment Approvals</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Manage payment requests and approvals
                </p>
            </div>
            <Link
                v-if="$page.props.auth.user.roles?.includes('accountant') || $page.props.auth.user.is_admin"
                :href="route('manage.payment-approvals.create')"
                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg transition-all"
            >
                <Plus class="w-3.5 h-3.5" />
                New Request
            </Link>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-3 gap-3 mb-6">
            <button
                v-for="item in [
            { key: 'pending',  label: 'Pending' },
            { key: 'approved', label: 'Approved' },
            { key: 'paid',     label: 'Paid' },
        ]"
                :key="item.key"
                @click="statusFilter = statusFilter === item.key ? '' : item.key; applyFilters()"
                :class="statusFilter === item.key
            ? 'ring-2 ring-gray-900 dark:ring-white border-transparent'
            : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
                class="bg-white dark:bg-gray-900 border rounded-xl p-4 text-left transition-all"
            >
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">{{ item.label }}</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ summary[item.key] ?? 0 }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    {{ statusFilter === item.key ? 'Click to clear' : 'Click to filter' }}
                </p>
            </button>
        </div>

        <!-- Filters -->
        <div class="flex items-center gap-3 flex-wrap mb-6">
            <select
                v-if="buildings.length > 1"
                v-model="buildingFilter"
                @change="applyFilters"
                class="px-3 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none"
            >
                <option value="">All buildings</option>
                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>

            <button
                v-if="statusFilter || buildingFilter"
                @click="clearFilters"
                class="text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors"
            >
                Clear filters
            </button>
        </div>

        <!-- Table / Cards -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">

            <div v-if="approvals.data.length === 0" class="text-center py-16">
                <Banknote class="w-10 h-10 text-gray-300 dark:text-gray-700 mx-auto mb-3" />
                <p class="text-gray-500 dark:text-gray-400">No payment requests found.</p>
            </div>

            <template v-else>
                <!-- Desktop table -->
                <table class="hidden md:table w-full text-sm">
                    <thead class="border-b border-gray-100 dark:border-gray-800">
                    <tr class="text-left">
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Request</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Recipient</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Amount</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Date</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <tr
                        v-for="approval in approvals.data"
                        :key="approval.id"
                        class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                    >
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900 dark:text-white">{{ approval.type_label }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ approval.building?.name }}</p>
                        </td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ approval.recipient_name }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ formatPrice(approval.amount) }}</td>
                        <td class="px-6 py-4">
                        <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border', statusClass(approval.status)]">
                            <component :is="statusConfig[approval.status]?.icon" class="w-3 h-3" />
                            {{ statusConfig[approval.status]?.label }}
                        </span>
                        </td>
                        <td class="px-6 py-4 text-gray-500 dark:text-gray-400 text-xs">{{ formatDate(approval.created_at) }}</td>
                        <td class="px-6 py-4">
                            <Link
                                :href="route('manage.payment-approvals.show', approval.id)"
                                class="p-2 text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                            >
                                <ChevronRight class="w-4 h-4" />
                            </Link>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <!-- Mobile cards -->
                <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-800">
                    <Link
                        v-for="approval in approvals.data"
                        :key="approval.id"
                        :href="route('manage.payment-approvals.show', approval.id)"
                        class="flex items-start justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors"
                    >
                        <div class="space-y-1 min-w-0 flex-1 pr-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ approval.type_label }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ approval.recipient_name }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ approval.building?.name }} · {{ formatDate(approval.created_at) }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatPrice(approval.amount) }}</p>
                            <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium border', statusClass(approval.status)]">
                        <component :is="statusConfig[approval.status]?.icon" class="w-3 h-3" />
                        {{ statusConfig[approval.status]?.label }}
                    </span>
                        </div>
                    </Link>
                </div>
            </template>

            <!-- Pagination -->
            <div v-if="approvals.last_page > 1" class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <p class="text-sm text-gray-500">
                    Showing {{ approvals.from }}–{{ approvals.to }} of {{ approvals.total }}
                </p>
                <div class="flex gap-2">
                    <Link
                        v-if="approvals.prev_page_url"
                        :href="approvals.prev_page_url"
                        class="px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 transition-colors"
                    >Previous</Link>
                    <Link
                        v-if="approvals.next_page_url"
                        :href="approvals.next_page_url"
                        class="px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 transition-colors"
                    >Next</Link>
                </div>
            </div>
        </div>

    </div>

</template>
