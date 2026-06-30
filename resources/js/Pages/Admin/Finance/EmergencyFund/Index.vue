<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Plus, ShieldAlert, ChevronRight, Clock, CheckCircle, XCircle, Banknote, AlertTriangle } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

import ManageLayout from '@/Layouts/ManageLayout.vue'

const props = defineProps({
    requests:     Object,
    fundSummary:  Array,
    buildings:    Array,
    months:       Array,
    filters:      Object,
    currentMonth: String,
})

const statusFilter   = ref(props.filters?.status ?? '')
const buildingFilter = ref(props.filters?.building ?? '')
const monthFilter    = ref(props.filters?.month ?? '')

const formatPrice = (v) => new Intl.NumberFormat('en-NG', {
    style: 'currency', currency: 'NGN', minimumFractionDigits: 0,
}).format(v || 0)

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-NG', {
    day: 'numeric', month: 'short', year: 'numeric',
}) : '—'

const statusConfig = {
    pending:          { label: 'Pending',          color: 'amber',  icon: Clock },
    manager_approved: { label: 'Manager Approved', color: 'blue',   icon: CheckCircle },
    approved:         { label: 'CEO Approved',     color: 'indigo', icon: CheckCircle },
    declined:         { label: 'Declined',         color: 'red',    icon: XCircle },
    paid:             { label: 'Paid',             color: 'green',  icon: Banknote },
}

const statusClass = (status) => ({
    amber:  'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
    blue:   'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800',
    indigo: 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800',
    red:    'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800',
    green:  'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800',
}[statusConfig[status]?.color ?? 'amber'])

function applyFilters() {
    router.get(route('manage.emergency-fund.index'), {
        status:   statusFilter.value || undefined,
        building: buildingFilter.value || undefined,
        month:    monthFilter.value || undefined,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    statusFilter.value   = ''
    buildingFilter.value = ''
    monthFilter.value    = ''
    applyFilters()
}
</script>

<template>
    <Head title="Emergency Fund" />

    <div class="p-6 lg:p-8 space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-light text-gray-900 dark:text-white flex items-center gap-2">
                        <ShieldAlert class="w-6 h-6 text-red-500" />
                        Emergency Fund
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Monthly emergency fund requests and approvals
                    </p>
                </div>
                <Link
                    v-if="$page.props.auth.user.roles?.includes('accountant') || $page.props.auth.user.is_admin"
                    :href="route('manage.emergency-fund.create')"
                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg shadow-sm transition-all"
                >
                    <Plus class="w-3.5 h-3.5" />
                    New Request
                </Link>
            </div>

            <!-- Fund Balance Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    v-for="fund in fundSummary"
                    :key="fund.building_id"
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6"
                >
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">{{ fund.building_name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Monthly Emergency Fund</p>
                        </div>
                        <div :class="[
                            'px-2.5 py-1 rounded-full text-xs font-medium border',
                            fund.percent >= 90 ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800' :
                            fund.percent >= 60 ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800' :
                            'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800'
                        ]">
                            {{ fund.percent }}% used
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="relative h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden mb-4">
                        <div
                            :style="{ width: fund.percent + '%' }"
                            :class="[
                                'absolute inset-y-0 left-0 rounded-full transition-all duration-500',
                                fund.percent >= 90 ? 'bg-red-500' :
                                fund.percent >= 60 ? 'bg-amber-500' : 'bg-green-500'
                            ]"
                        />
                    </div>

                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Limit</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatPrice(fund.limit) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Used</p>
                            <p class="text-sm font-semibold text-red-600 dark:text-red-400">{{ formatPrice(fund.used) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Remaining</p>
                            <p class="text-sm font-semibold text-green-600 dark:text-green-400">{{ formatPrice(fund.remaining) }}</p>
                        </div>
                    </div>

                    <!-- Warning if nearly exhausted -->
                    <div v-if="fund.percent >= 90"
                         class="mt-4 flex items-center gap-2 px-3 py-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-xs text-red-700 dark:text-red-400">
                        <AlertTriangle class="w-3.5 h-3.5 flex-shrink-0" />
                        Emergency fund nearly exhausted for this month.
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex items-center gap-3 flex-wrap">
                <select
                    v-model="statusFilter"
                    @change="applyFilters"
                    class="px-3 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none"
                >
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="manager_approved">Manager Approved</option>
                    <option value="approved">CEO Approved</option>
                    <option value="declined">Declined</option>
                    <option value="paid">Paid</option>
                </select>

                <select
                    v-if="buildings.length > 1"
                    v-model="buildingFilter"
                    @change="applyFilters"
                    class="px-3 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none"
                >
                    <option value="">All buildings</option>
                    <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>

                <select
                    v-model="monthFilter"
                    @change="applyFilters"
                    class="px-3 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none"
                >
                    <option value="">All months</option>
                    <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                </select>

                <button
                    v-if="statusFilter || buildingFilter || monthFilter"
                    @click="clearFilters"
                    class="text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors"
                >Clear</button>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">

                <div v-if="!requests.data.length" class="text-center py-16">
                    <ShieldAlert class="w-10 h-10 text-gray-300 dark:text-gray-700 mx-auto mb-3" />
                    <p class="text-gray-500 dark:text-gray-400">No emergency fund requests found.</p>
                </div>

                <template v-else>
                    <!-- Desktop -->
                    <table class="hidden md:table w-full text-sm">
                        <thead class="border-b border-gray-100 dark:border-gray-800">
                        <tr class="text-left">
                            <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Request</th>
                            <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Amount</th>
                            <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Requested By</th>
                            <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Date</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr
                            v-for="req in requests.data"
                            :key="req.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                        >
                            <td class="px-6 py-4">
                                <p class="font-medium text-gray-900 dark:text-white">{{ req.reason }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ req.building?.name }}</p>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ formatPrice(req.amount) }}</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400 text-xs">{{ req.requested_by?.name }}</td>
                            <td class="px-6 py-4">
                                    <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border', statusClass(req.status)]">
                                        <component :is="statusConfig[req.status]?.icon" class="w-3 h-3" />
                                        {{ statusConfig[req.status]?.label }}
                                    </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs">{{ formatDate(req.created_at) }}</td>
                            <td class="px-6 py-4">
                                <Link
                                    :href="route('manage.emergency-fund.show', req.id)"
                                    class="p-2 text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                                >
                                    <ChevronRight class="w-4 h-4" />
                                </Link>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <!-- Mobile -->
                    <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-800">
                        <Link
                            v-for="req in requests.data"
                            :key="req.id"
                            :href="route('manage.emergency-fund.show', req.id)"
                            class="flex items-start justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors"
                        >
                            <div class="space-y-0.5 min-w-0 flex-1 pr-3">
                                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ req.reason }}</p>
                                <p class="text-xs text-gray-500">{{ req.building?.name }} · {{ req.requested_by?.name }}</p>
                                <p class="text-xs text-gray-400">{{ formatDate(req.created_at) }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatPrice(req.amount) }}</p>
                                <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium border', statusClass(req.status)]">
                                    <component :is="statusConfig[req.status]?.icon" class="w-3 h-3" />
                                    {{ statusConfig[req.status]?.label }}
                                </span>
                            </div>
                        </Link>
                    </div>
                </template>

                <!-- Pagination -->
                <div v-if="requests.last_page > 1" class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <p class="text-sm text-gray-500">Showing {{ requests.from }}–{{ requests.to }} of {{ requests.total }}</p>
                    <div class="flex gap-2">
                        <Link v-if="requests.prev_page_url" :href="requests.prev_page_url"
                              class="px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 transition-colors">Previous</Link>
                        <Link v-if="requests.next_page_url" :href="requests.next_page_url"
                              class="px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 transition-colors">Next</Link>
                    </div>
                </div>
            </div>
</div>

</template>
