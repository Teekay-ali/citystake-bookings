<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import {
    Plus, Wrench, ChevronRight,
    FileText, User, Banknote, CircleDot, CalendarDays, Clock,
} from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    reports:    Object,
    buildings:  Array,
    issueTypes: Object,
    filters:    Object,
    counts:     Object,
})

const buildingId = ref(props.filters.building_id || '')
const status     = ref(props.filters.status || '')
const issueType  = ref(props.filters.issue_type || '')

watch([buildingId, status, issueType], () => {
    router.get(route('manage.maintenance.index'), {
        building_id: buildingId.value || undefined,
        status:      status.value || undefined,
        issue_type:  issueType.value || undefined,
    }, { preserveState: true, replace: true })
})

const statusConfig = {
    pending:             { label: 'Awaiting Manager',    cls: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' },
    manager_approved:    { label: 'Awaiting Accountant', cls: 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400' },
    accountant_approved: { label: 'Awaiting CEO',        cls: 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400' },
    ceo_approved:        { label: 'Awaiting Payment',    cls: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400' },
    completed:           { label: 'Completed',           cls: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' },
    rejected:            { label: 'Rejected',            cls: 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400' },
}

const pipeline = [
    { key: 'pending',             label: 'Awaiting Manager',    labelCls: 'text-gray-500' },
    { key: 'manager_approved',    label: 'Awaiting Accountant', labelCls: 'text-blue-500' },
    { key: 'accountant_approved', label: 'Awaiting CEO',        labelCls: 'text-violet-500' },
    { key: 'ceo_approved',        label: 'Awaiting Payment',    labelCls: 'text-amber-500' },
]

// Terminal states read as "done" - dim the row
const isTerminal = (s) => s === 'completed' || s === 'rejected'

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatTime(d) {
    return d ? new Date(d).toLocaleTimeString('en-NG', { hour: '2-digit', minute: '2-digit', hour12: true }) : '-'
}

function formatAmount(n) {
    return n ? '₦' + Number(n).toLocaleString('en-NG') : '-'
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

const selectClass = "pl-3 pr-8 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
</script>

<template>
    <Head title="Maintenance Requests" />

    <div class="p-4 lg:p-6">

        <!-- ── Header ── -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Maintenance Requests</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Track and approve maintenance work</p>
            </div>
            <Link :href="route('manage.maintenance.create')"
                  class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg shadow-sm transition-all">
                <Plus class="w-3.5 h-3.5" />
                New Request
            </Link>
        </div>

        <!-- ── Pipeline cards - clickable filters ── -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <button
                v-for="item in pipeline"
                :key="item.key"
                @click="status = status === item.key ? '' : item.key"
                :class="status === item.key
                    ? 'ring-2 ring-gray-900 dark:ring-white border-transparent'
                    : 'border-gray-100 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
                class="bg-white dark:bg-gray-900 border rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none px-4 py-3 text-left transition-all flex items-center justify-between gap-3">
                <span class="text-xs font-medium uppercase tracking-wider truncate" :class="item.labelCls">{{ item.label }}</span>
                <span class="text-xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ counts[item.key] ?? 0 }}</span>
            </button>
        </div>

        <!-- ── Filters ── -->
        <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2 mb-4">
            <select v-model="buildingId" :class="[selectClass, 'w-full sm:w-auto']">
                <option value="">All buildings</option>
                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>

            <select v-model="issueType" :class="[selectClass, 'w-full sm:w-auto']">
                <option value="">All issue types</option>
                <option v-for="(label, key) in issueTypes" :key="key" :value="key">{{ label }}</option>
            </select>

            <select v-model="status" :class="[selectClass, 'w-full sm:w-auto']">
                <option value="">All statuses</option>
                <option v-for="(cfg, key) in statusConfig" :key="key" :value="key">{{ cfg.label }}</option>
            </select>

            <button
                v-if="status || buildingId || issueType"
                @click="status = ''; buildingId = ''; issueType = ''"
                class="w-full sm:w-auto px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                Clear filters
            </button>
        </div>

        <!-- ── Table / Cards ── -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden">

            <!-- Empty state -->
            <div v-if="reports.data.length === 0" class="text-center py-16">
                <Wrench class="w-10 h-10 text-gray-300 dark:text-gray-700 mx-auto mb-3" />
                <p class="text-gray-500 dark:text-gray-400">
                    {{ status || buildingId || issueType ? 'No reports match these filters.' : 'No maintenance reports yet.' }}
                </p>
                <Link
                    v-if="!status && !buildingId && !issueType"
                    :href="route('manage.maintenance.create')"
                    class="inline-flex items-center gap-2 mt-4 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg shadow-sm transition-all">
                    <Plus class="w-3.5 h-3.5" /> New Request
                </Link>
                <button
                    v-else
                    @click="status = ''; buildingId = ''; issueType = ''"
                    class="mt-3 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white underline transition-colors">
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
                            <span class="inline-flex items-center gap-1.5"><User class="w-3.5 h-3.5" /> Submitted by</span>
                        </th>
                        <th class="px-4 py-2.5 text-right font-medium">
                            <span class="inline-flex items-center gap-1.5"><Banknote class="w-3.5 h-3.5" /> Est. cost</span>
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
                        v-for="report in reports.data"
                        :key="report.id"
                        @click="router.visit(route('manage.maintenance.show', report.id))"
                        :class="isTerminal(report.status) && 'text-gray-400 dark:text-gray-600'"
                        class="group cursor-pointer hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                        <td class="px-4 py-2.5">
                            <p class="font-medium text-gray-900 dark:text-white truncate max-w-xs">{{ report.title }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ issueTypes[report.issue_type] }}</p>
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <p class="text-gray-600 dark:text-gray-400">{{ report.submitted_by?.name }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ report.building?.name }}</p>
                        </td>
                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                            <p class="font-semibold tabular-nums text-gray-900 dark:text-white">{{ formatAmount(report.estimated_cost) }}</p>
                            <p v-if="report.vendor" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ report.vendor.name }}</p>
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <span :class="['inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium', statusConfig[report.status]?.cls]">
                                {{ statusConfig[report.status]?.label }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-gray-400 dark:text-gray-500 text-xs whitespace-nowrap" :title="formatDate(report.created_at)">
                            {{ formatRelative(report.created_at) }}
                        </td>
                        <td class="px-4 py-2.5 text-gray-400 dark:text-gray-500 text-xs whitespace-nowrap tabular-nums">
                            {{ formatTime(report.created_at) }}
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
                        v-for="report in reports.data"
                        :key="report.id"
                        :href="route('manage.maintenance.show', report.id)"
                        :class="isTerminal(report.status) && 'opacity-60'"
                        class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ issueTypes[report.issue_type] }}</span>
                            <span :class="['inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium', statusConfig[report.status]?.cls]">
                                {{ statusConfig[report.status]?.label }}
                            </span>
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ report.title }}</p>
                        <div class="flex items-center justify-between gap-2 mt-1">
                            <p class="text-xs text-gray-400 dark:text-gray-500 truncate">
                                {{ report.building?.name }} · {{ report.submitted_by?.name }} · {{ formatRelative(report.created_at) }}
                            </p>
                            <p class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white shrink-0">{{ formatAmount(report.estimated_cost) }}</p>
                        </div>
                    </Link>
                </div>
            </template>

            <!-- Pagination -->
            <div v-if="reports.last_page > 1" class="px-4 py-3 border-t border-gray-100 dark:border-gray-800 flex justify-center gap-1.5">
                <Link
                    v-for="link in reports.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    :class="[
                        'min-w-[32px] h-8 flex items-center justify-center px-2.5 rounded-lg text-xs transition-all',
                        link.active
                            ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium'
                            : 'border border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800',
                        !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                    ]"
                    v-html="link.label" />
            </div>
        </div>

    </div>
</template>
