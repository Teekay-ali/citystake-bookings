<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import {
    Plus, Clock, CheckCircle, XCircle, Banknote,
    ChevronRight, Building2, Filter,
    FileText, User, CalendarDays, CircleDot
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

const formatTime = (d) => d ? new Date(d).toLocaleTimeString('en-NG', { hour: '2-digit', minute: '2-digit', hour12: true }) : '—'

const formatRelative = (d) => {
    if (!d) return '—'
    const diff = Math.floor((Date.now() - new Date(d)) / 1000)
    if (diff < 60)    return 'just now'
    if (diff < 3600)  return `${Math.floor(diff / 60)}m ago`
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
    if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`
    return formatDate(d)
}

const statusConfig = {
    pending:  { label: 'Pending',  color: 'amber',  icon: Clock },
    approved: { label: 'Approved', color: 'blue',   icon: CheckCircle },
    declined: { label: 'Declined', color: 'red',    icon: XCircle },
    paid:     { label: 'Paid',     color: 'green',  icon: Banknote },
}

const statusClass = (status) => ({
    amber: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400',
    blue:  'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400',
    red:   'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400',
    green: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
}[statusConfig[status]?.color ?? 'amber'])

// Terminal states read as "done" — dim the row like completed bookings
const isTerminal = (status) => status === 'paid' || status === 'declined'

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
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Payment Approvals</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Manage payment requests and approvals
                </p>
            </div>
            <Link
                v-if="$page.props.auth.user.roles?.includes('accountant') || $page.props.auth.user.is_admin"
                :href="route('manage.payment-approvals.create')"
                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg shadow-sm transition-all"
            >
                <Plus class="w-3.5 h-3.5" />
                New Request
            </Link>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-3 gap-3 mb-6">
            <button
                v-for="item in [
            { key: 'pending',  label: 'Pending',  labelCls: 'text-amber-500' },
            { key: 'approved', label: 'Approved', labelCls: 'text-blue-500' },
            { key: 'paid',     label: 'Paid',     labelCls: 'text-emerald-500' },
        ]"
                :key="item.key"
                @click="statusFilter = statusFilter === item.key ? '' : item.key; applyFilters()"
                :class="statusFilter === item.key
            ? 'ring-2 ring-gray-900 dark:ring-white border-transparent'
            : 'border-gray-100 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
                class="group bg-white dark:bg-gray-900 border rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none px-4 py-3 text-left transition-all flex items-center justify-between gap-3"
            >
                <span class="text-xs font-medium uppercase tracking-wider" :class="item.labelCls">{{ item.label }}</span>
                <span class="text-xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ summary[item.key] ?? 0 }}</span>
            </button>
        </div>

        <!-- Filters -->
        <div v-if="buildings.length > 1 || statusFilter || buildingFilter" class="grid grid-cols-2 sm:flex sm:items-center gap-2 mb-6">
            <select
                v-if="buildings.length > 1"
                v-model="buildingFilter"
                @change="applyFilters"
                class="w-full sm:w-auto px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
            >
                <option value="">All buildings</option>
                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>

            <button
                v-if="statusFilter || buildingFilter"
                @click="clearFilters"
                class="w-full sm:w-auto px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all sm:border-0 sm:px-0 sm:text-sm sm:font-normal"
            >
                Clear filters
            </button>
        </div>

        <!-- Table / Cards -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden">

            <div v-if="approvals.data.length === 0" class="text-center py-16">
                <Banknote class="w-10 h-10 text-gray-300 dark:text-gray-700 mx-auto mb-3" />
                <p class="text-gray-500 dark:text-gray-400">
                    {{ statusFilter || buildingFilter ? 'No payment requests match these filters.' : 'No payment requests yet.' }}
                </p>
                <Link
                    v-if="!statusFilter && !buildingFilter && ($page.props.auth.user.roles?.includes('accountant') || $page.props.auth.user.is_admin)"
                    :href="route('manage.payment-approvals.create')"
                    class="inline-flex items-center gap-2 mt-4 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg shadow-sm transition-all"
                >
                    <Plus class="w-3.5 h-3.5" /> New Request
                </Link>
                <button
                    v-else-if="statusFilter || buildingFilter"
                    @click="clearFilters"
                    class="mt-3 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white underline transition-colors"
                >
                    Clear filters
                </button>
            </div>

            <template v-else>
                <!-- Desktop table -->
                <table class="hidden md:table w-full text-[13px]">
                    <thead class="border-b border-gray-100 dark:border-gray-800">
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-2.5 font-medium">
                            <span class="inline-flex items-center gap-1.5"><FileText class="w-3.5 h-3.5" /> Request</span>
                        </th>
                        <th class="px-4 py-2.5 font-medium">
                            <span class="inline-flex items-center gap-1.5"><User class="w-3.5 h-3.5" /> Recipient</span>
                        </th>
                        <th class="px-4 py-2.5 text-right font-medium">
                            <span class="inline-flex items-center gap-1.5"><Banknote class="w-3.5 h-3.5" /> Amount</span>
                        </th>
                        <th class="px-4 py-2.5 font-medium">
                            <span class="inline-flex items-center gap-1.5"><CircleDot class="w-3.5 h-3.5" /> Status</span>
                        </th>
                        <th class="px-4 py-2.5 font-medium">
                            <span class="inline-flex items-center gap-1.5"><CalendarDays class="w-3.5 h-3.5" /> Date</span>
                        </th>
                        <th class="px-4 py-2.5 font-medium">
                            <span class="inline-flex items-center gap-1.5"><Clock class="w-3.5 h-3.5" /> Time</span>
                        </th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <tr
                        v-for="approval in approvals.data"
                        :key="approval.id"
                        @click="router.visit(route('manage.payment-approvals.show', approval.id))"
                        :class="isTerminal(approval.status) && 'text-gray-400 dark:text-gray-600'"
                        class="group cursor-pointer hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors"
                    >
                        <td class="px-4 py-2.5">
                            <p class="font-medium text-gray-900 dark:text-white">{{ approval.type_label }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ approval.building?.name }}</p>
                        </td>
                        <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ approval.recipient_name }}</td>
                        <td class="px-4 py-2.5 text-right font-semibold tabular-nums text-gray-900 dark:text-white whitespace-nowrap">{{ formatPrice(approval.amount) }}</td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                        <span :class="['inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-medium', statusClass(approval.status)]">
                            <component :is="statusConfig[approval.status]?.icon" class="w-3 h-3" />
                            {{ statusConfig[approval.status]?.label }}
                        </span>
                        </td>
                        <td class="px-4 py-2.5 text-gray-400 dark:text-gray-500 text-xs whitespace-nowrap" :title="formatDate(approval.created_at)">{{ formatRelative(approval.created_at) }}</td>
                        <td class="px-4 py-2.5 text-gray-400 dark:text-gray-500 text-xs whitespace-nowrap tabular-nums">{{ formatTime(approval.created_at) }}</td>
                        <td class="px-4 py-2.5 text-right">
                            <Link
                                :href="route('manage.payment-approvals.show', approval.id)"
                                class="inline-flex p-1.5 text-gray-400 hover:text-gray-900 dark:hover:text-white opacity-0 group-hover:opacity-100 focus:opacity-100 transition-all"
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
                            <p class="text-xs text-gray-400 dark:text-gray-500" :title="formatDate(approval.created_at)">{{ approval.building?.name }} · {{ formatRelative(approval.created_at) }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatPrice(approval.amount) }}</p>
                            <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium', statusClass(approval.status)]">
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
