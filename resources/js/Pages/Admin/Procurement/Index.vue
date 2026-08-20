<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import FacetedFilter from '@/Components/DataTable/FacetedFilter.vue'
import SortableHeader from '@/Components/DataTable/SortableHeader.vue'
import ViewOptions from '@/Components/DataTable/ViewOptions.vue'
import DataTablePagination from '@/Components/DataTable/DataTablePagination.vue'
import {
    Plus, ShoppingCart, ChevronRight, Search, X,
    FileText, User, Banknote, CircleDot, CalendarDays,
} from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    requests:     Object,
    buildings:    Array,
    filters:      Object,
    counts:       Object,
    statusCounts: Object,
})

// ── Filter / sort state (server-driven) ──
const search     = ref(props.filters.search || '')
const status     = ref([...(props.filters.status || [])])
const buildingId = ref([...(props.filters.building_id || [])])
const sortBy     = ref(props.filters.sort_by || 'created_at')
const sortOrder  = ref(props.filters.sort_order || 'desc')
const perPage    = ref(props.filters.per_page || 10)

const statusConfig = {
    pending:             { label: 'Awaiting Proc. Officer', cls: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' },
    officer_approved:    { label: 'Awaiting Accountant',    cls: 'bg-sky-50 dark:bg-sky-500/10 text-sky-700 dark:text-sky-400' },
    accountant_approved: { label: 'Awaiting CEO',           cls: 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400' },
    ceo_approved:        { label: 'Awaiting Purchase',      cls: 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400' },
    purchased:           { label: 'Awaiting Receipt',       cls: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400' },
    completed:           { label: 'Completed',              cls: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' },
    rejected:            { label: 'Rejected',               cls: 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400' },
}

const pipeline = [
    { key: 'pending',             label: 'Awaiting Proc. Officer', labelCls: 'text-gray-500' },
    { key: 'officer_approved',    label: 'Awaiting Accountant',    labelCls: 'text-sky-500' },
    { key: 'accountant_approved', label: 'Awaiting CEO',           labelCls: 'text-blue-500' },
    { key: 'ceo_approved',        label: 'Awaiting Purchase',      labelCls: 'text-violet-500' },
    { key: 'purchased',           label: 'Awaiting Receipt',       labelCls: 'text-amber-500' },
]

// Faceted filter option sets (with live counts).
const statusOptions = computed(() =>
    Object.entries(statusConfig).map(([value, cfg]) => ({
        value, label: cfg.label, count: props.statusCounts?.[value] ?? 0,
    }))
)
const buildingOptions = computed(() =>
    props.buildings.map(b => ({ value: b.id, label: b.name }))
)

// ── Column visibility (View menu, persisted) ──
const allColumns = [
    { key: 'request',      label: 'Request',      locked: true },
    { key: 'submitted_by', label: 'Submitted by' },
    { key: 'status',       label: 'Status' },
    { key: 'created',      label: 'Submitted' },
    { key: 'amount',       label: 'Amount' },
]
const visibleCols = ref(allColumns.map(c => c.key))
const shown = (key) => visibleCols.value.includes(key)

const hasFilters = computed(() =>
    !!search.value || status.value.length > 0 || buildingId.value.length > 0
)

// Terminal states read as "done" - dim the row
const isTerminal = (s) => s === 'completed' || s === 'rejected'

let searchTimeout = null
watch(search, () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(applyFilters, 400)
})
watch([status, buildingId, sortBy, sortOrder, perPage], applyFilters, { deep: true })

function applyFilters() {
    router.get(route('manage.procurement.index'), {
        search:      search.value || undefined,
        status:      status.value.length ? status.value : undefined,
        building_id: buildingId.value.length ? buildingId.value : undefined,
        sort_by:     sortBy.value,
        sort_order:  sortOrder.value,
        per_page:    perPage.value,
    }, { preserveState: true, replace: true, preserveScroll: true })
}

function onSort({ sort_by, sort_order }) {
    sortBy.value = sort_by
    sortOrder.value = sort_order
}

function toggleStatus(key) {
    const i = status.value.indexOf(key)
    i === -1 ? status.value.push(key) : status.value.splice(i, 1)
}

function clearFilters() {
    search.value = ''
    status.value = []
    buildingId.value = []
}

function formatAmount(n) {
    return '₦' + Number(n).toLocaleString('en-NG')
}
function formatDate(d) {
    return new Date(d).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}
function formatTime(d) {
    return d ? new Date(d).toLocaleTimeString('en-NG', { hour: '2-digit', minute: '2-digit', hour12: true }) : '-'
}
function formatRelative(d) {
    if (!d) return '-'
    const diff = Math.floor((Date.now() - new Date(d)) / 1000)
    if (diff < 60)     return 'just now'
    if (diff < 3600)   return `${Math.floor(diff / 60)}m ago`
    if (diff < 86400)  return `${Math.floor(diff / 3600)}h ago`
    if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`
    return formatDate(d)
}
</script>

<template>
    <Head title="Procurement" />

    <div class="p-4 lg:p-6">

        <!-- ── Header ── -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Procurement</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Manage purchase requests and approvals</p>
            </div>
            <Link v-if="$page.props.auth.user.permissions?.includes('submit-procurement')"
                  :href="route('manage.procurement.create')"
                  class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg shadow-sm transition-all">
                <Plus class="w-3.5 h-3.5" />
                New Request
            </Link>
        </div>

        <!-- ── Pipeline cards ── -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
            <button
                v-for="item in pipeline"
                :key="item.key"
                @click="toggleStatus(item.key)"
                :class="status.includes(item.key)
                    ? 'ring-2 ring-gray-900 dark:ring-white border-transparent'
                    : 'border-gray-100 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
                class="bg-white dark:bg-gray-900 border rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none px-4 py-3 text-left transition-all flex items-center justify-between gap-3">
                <span class="text-xs font-medium uppercase tracking-wider truncate" :class="item.labelCls">{{ item.label }}</span>
                <span class="text-xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ counts[item.key] ?? 0 }}</span>
            </button>
        </div>

        <!-- ── Toolbar ── -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-2 mb-4">
            <div class="relative flex-1 sm:max-w-xs">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search title or reference..."
                    class="w-full h-9 pl-9 pr-4 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
                />
            </div>

            <FacetedFilter label="Status" :options="statusOptions" v-model="status" />
            <FacetedFilter v-if="buildings.length > 1" label="Building" :options="buildingOptions" v-model="buildingId" />

            <button
                v-if="hasFilters"
                @click="clearFilters"
                class="inline-flex items-center gap-1.5 h-9 px-2.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                Reset <X class="w-3.5 h-3.5" />
            </button>

            <div class="sm:ml-auto">
                <ViewOptions :columns="allColumns" v-model="visibleCols" storage-key="procurement.columns" />
            </div>
        </div>

        <!-- ── Table / Cards ── -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden">

            <!-- Empty state -->
            <div v-if="requests.data.length === 0" class="text-center py-16">
                <ShoppingCart class="w-10 h-10 text-gray-300 dark:text-gray-700 mx-auto mb-3" />
                <p class="text-gray-500 dark:text-gray-400">
                    {{ hasFilters ? 'No requests match these filters.' : 'No procurement requests yet.' }}
                </p>
                <Link
                    v-if="!hasFilters && $page.props.auth.user.permissions?.includes('submit-procurement')"
                    :href="route('manage.procurement.create')"
                    class="inline-flex items-center gap-2 mt-4 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg shadow-sm transition-all">
                    <Plus class="w-3.5 h-3.5" /> New Request
                </Link>
                <button
                    v-else-if="hasFilters"
                    @click="clearFilters"
                    class="mt-3 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white underline transition-colors">
                    Clear filters
                </button>
            </div>

            <template v-else>
                <!-- Desktop table -->
                <table class="hidden md:table w-full text-[13px]">
                    <thead class="border-b border-gray-100 dark:border-gray-800">
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th v-if="shown('request')" class="px-4 py-2.5">
                            <SortableHeader label="Request" field="reference" :icon="FileText" :sort-by="sortBy" :sort-order="sortOrder" @sort="onSort" />
                        </th>
                        <th v-if="shown('submitted_by')" class="px-4 py-2.5">
                            <SortableHeader label="Submitted by" field="submitted_by" :icon="User" :sort-by="sortBy" :sort-order="sortOrder" @sort="onSort" />
                        </th>
                        <th v-if="shown('status')" class="px-4 py-2.5">
                            <SortableHeader label="Status" field="status" :icon="CircleDot" :sort-by="sortBy" :sort-order="sortOrder" @sort="onSort" />
                        </th>
                        <th v-if="shown('created')" class="px-4 py-2.5">
                            <SortableHeader label="Submitted" field="created_at" :icon="CalendarDays" :sort-by="sortBy" :sort-order="sortOrder" @sort="onSort" />
                        </th>
                        <th v-if="shown('amount')" class="px-4 py-2.5 text-right">
                            <SortableHeader label="Amount" field="total_amount" align="right" :icon="Banknote" :sort-by="sortBy" :sort-order="sortOrder" @sort="onSort" />
                        </th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <tr
                        v-for="req in requests.data"
                        :key="req.id"
                        @click="router.visit(route('manage.procurement.show', req.id))"
                        :class="isTerminal(req.status) && 'text-gray-400 dark:text-gray-600'"
                        class="group cursor-pointer hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                        <td v-if="shown('request')" class="px-4 py-2.5">
                            <p class="font-medium text-gray-900 dark:text-white truncate max-w-xs">{{ req.title }}</p>
                            <p class="text-xs font-mono text-gray-400 dark:text-gray-500 mt-0.5">{{ req.reference }}</p>
                        </td>
                        <td v-if="shown('submitted_by')" class="px-4 py-2.5 whitespace-nowrap">
                            <p class="text-gray-600 dark:text-gray-400">{{ req.submitted_by?.name }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ req.building?.name }}</p>
                        </td>
                        <td v-if="shown('status')" class="px-4 py-2.5 whitespace-nowrap">
                            <span :class="['inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium', statusConfig[req.status]?.cls]">
                                {{ statusConfig[req.status]?.label }}
                            </span>
                        </td>
                        <td v-if="shown('created')" class="px-4 py-2.5 whitespace-nowrap" :title="formatRelative(req.created_at)">
                            <p class="text-gray-600 dark:text-gray-400">{{ formatDate(req.created_at) }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 tabular-nums">{{ formatTime(req.created_at) }}</p>
                        </td>
                        <td v-if="shown('amount')" class="px-4 py-2.5 text-right whitespace-nowrap">
                            <p class="font-semibold tabular-nums text-gray-900 dark:text-white">{{ formatAmount(req.total_amount) }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ req.items?.length }} item{{ req.items?.length !== 1 ? 's' : '' }}</p>
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <ChevronRight class="inline w-4 h-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-all" />
                        </td>
                    </tr>
                    </tbody>
                </table>

                <!-- Mobile cards -->
                <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-800">
                    <Link
                        v-for="req in requests.data"
                        :key="req.id"
                        :href="route('manage.procurement.show', req.id)"
                        :class="isTerminal(req.status) && 'opacity-60'"
                        class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <span class="text-xs font-mono text-gray-400 dark:text-gray-500">{{ req.reference }}</span>
                            <span :class="['inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium', statusConfig[req.status]?.cls]">
                                {{ statusConfig[req.status]?.label }}
                            </span>
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ req.title }}</p>
                        <div class="flex items-center justify-between gap-2 mt-1">
                            <p class="text-xs text-gray-400 dark:text-gray-500 truncate">
                                {{ req.building?.name }} · {{ req.submitted_by?.name }} · {{ formatRelative(req.created_at) }}
                            </p>
                            <p class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white shrink-0">{{ formatAmount(req.total_amount) }}</p>
                        </div>
                    </Link>
                </div>
            </template>

            <!-- Pagination -->
            <DataTablePagination
                v-if="requests.data.length"
                :paginator="requests"
                v-model:per-page="perPage" />
        </div>

    </div>
</template>
