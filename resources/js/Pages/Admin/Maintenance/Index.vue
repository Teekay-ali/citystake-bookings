<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Plus, Wrench, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
    reports: Object,
    buildings: Array,
    issueTypes: Object,
    filters: Object,
    counts: Object,
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
    pending:              { label: 'Awaiting Manager',    class: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' },
    manager_approved:     { label: 'Awaiting Accountant', class: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400' },
    accountant_approved:  { label: 'Awaiting CEO',        class: 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-400' },
    ceo_approved:         { label: 'Awaiting Payment',    class: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400' },
    completed:            { label: 'Completed',           class: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400' },
    rejected:             { label: 'Rejected',            class: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400' },
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatAmount(n) {
    return n ? '₦' + Number(n).toLocaleString('en-NG') : '—'
}
</script>

<template>
    <ManageLayout>
        <Head title="Maintenance Reports" />

        <div class="p-6 lg:p-8">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-2">Maintenance Reports</h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">Track and approve maintenance work</p>
                </div>
                <Link :href="route('manage.maintenance.create')"
                      class="flex items-center gap-2 px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-sm font-medium hover:opacity-90 transition-all">
                    <Plus class="w-4 h-4" />
                    New Report
                </Link>
            </div>

            <!-- Pipeline summary -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                <div v-for="(label, key) in {
                    pending: 'Awaiting Manager',
                    manager_approved: 'Awaiting Accountant',
                    accountant_approved: 'Awaiting CEO',
                    ceo_approved: 'Awaiting Payment'
                }" :key="key"
                     class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 cursor-pointer transition-all"
                     :class="status === key ? 'ring-2 ring-gray-900 dark:ring-white' : ''"
                     @click="status = status === key ? '' : key">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ label }}</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ counts[key] }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mb-6">
                <select v-model="buildingId"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                    <option value="">All Buildings</option>
                    <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <select v-model="issueType"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                    <option value="">All Issue Types</option>
                    <option v-for="(label, key) in issueTypes" :key="key" :value="key">{{ label }}</option>
                </select>
                <button v-if="status || buildingId || issueType"
                        @click="status = ''; buildingId = ''; issueType = ''"
                        class="px-4 py-2 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white transition-all">
                    Clear filters
                </button>
            </div>

            <!-- List -->
            <div class="space-y-3">
                <Link v-for="report in reports.data" :key="report.id"
                      :href="route('manage.maintenance.show', report.id)"
                      class="block bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 hover:border-gray-300 dark:hover:border-gray-700 transition-all">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span :class="[statusConfig[report.status]?.class, 'text-xs font-medium px-2.5 py-1 rounded-full']">
                                    {{ statusConfig[report.status]?.label }}
                                </span>
                                <span class="text-xs text-gray-400">{{ issueTypes[report.issue_type] }}</span>
                                <span class="text-xs text-gray-400">{{ report.building?.name }}</span>
                            </div>
                            <p class="font-medium text-gray-900 dark:text-white truncate">{{ report.title }}</p>
                            <div class="flex flex-wrap gap-3 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ report.submitted_by?.name }}</span>
                                <span>{{ formatDate(report.created_at) }}</span>
                                <span v-if="report.estimated_cost">Est. {{ formatAmount(report.estimated_cost) }}</span>
                                <span v-if="report.vendor">{{ report.vendor.name }}</span>
                            </div>
                        </div>
                        <ChevronRight class="w-4 h-4 text-gray-400 shrink-0 mt-1" />
                    </div>
                </Link>

                <div v-if="reports.data.length === 0" class="text-center py-16">
                    <Wrench class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                    <p class="text-gray-500 dark:text-gray-400">No maintenance reports found.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="reports.last_page > 1" class="flex justify-center gap-2 mt-6">
                <Link v-for="link in reports.links" :key="link.label"
                      :href="link.url || '#'"
                      :class="[
                        'px-3 py-1.5 rounded-lg text-sm transition-all',
                        link.active ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium' : 'border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800',
                        !link.url ? 'opacity-40 cursor-not-allowed' : ''
                    ]"
                      v-html="link.label" />
            </div>
        </div>
    </ManageLayout>
</template>
