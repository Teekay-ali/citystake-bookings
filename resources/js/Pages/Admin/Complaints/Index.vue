<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Plus, AlertTriangle, CheckCircle2, Clock, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
    complaints: Object,
    buildings: Array,
    filters: Object,
    counts: Object,
})

const buildingId = ref(props.filters.building_id || '')
const status     = ref(props.filters.status || '')
const severity   = ref(props.filters.severity || '')

watch([buildingId, status, severity], () => {
    router.get(route('manage.complaints.index'), {
        building_id: buildingId.value || undefined,
        status:      status.value || undefined,
        severity:    severity.value || undefined,
    }, { preserveState: true, replace: true })
})

const severityConfig = {
    low:    { label: 'Low',    class: 'bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-400' },
    medium: { label: 'Medium', class: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400' },
    high:   { label: 'High',   class: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400' },
    urgent: { label: 'Urgent', class: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400' },
}

const statusConfig = {
    open:        { label: 'Open',        icon: AlertTriangle, class: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400' },
    in_progress: { label: 'In Progress', icon: Clock,         class: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400' },
    resolved:    { label: 'Resolved',    icon: CheckCircle2,  class: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400' },
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}
</script>

<template>
    <ManageLayout>
        <Head title="Complaint Reports" />

        <div class="p-6 lg:p-8">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-2">Complaint Reports</h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">Staff-reported issues and resolutions</p>
                </div>
                <Link :href="route('manage.complaints.create')"
                      class="flex items-center gap-2 px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-sm font-medium hover:opacity-90 transition-all">
                    <Plus class="w-4 h-4" />
                    Report Issue
                </Link>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 rounded-2xl p-4">
                    <p class="text-xs text-red-600 dark:text-red-400 mb-1">Open</p>
                    <p class="text-2xl font-semibold text-red-700 dark:text-red-400">{{ counts.open }}</p>
                </div>
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 rounded-2xl p-4">
                    <p class="text-xs text-amber-600 dark:text-amber-400 mb-1">In Progress</p>
                    <p class="text-2xl font-semibold text-amber-700 dark:text-amber-400">{{ counts.in_progress }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ complaints.total }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mb-6">
                <select v-model="buildingId"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                    <option value="">All Buildings</option>
                    <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <select v-model="status"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                    <option value="">All Status</option>
                    <option value="open">Open</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                </select>
                <select v-model="severity"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                    <option value="">All Severity</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>

            <!-- List -->
            <div class="space-y-3">
                <Link v-for="complaint in complaints.data" :key="complaint.id"
                      :href="route('manage.complaints.show', complaint.id)"
                      class="block bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 hover:border-gray-300 dark:hover:border-gray-700 transition-all">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span :class="[severityConfig[complaint.severity].class, 'text-xs font-medium px-2.5 py-1 rounded-full']">
                                    {{ severityConfig[complaint.severity].label }}
                                </span>
                                <span :class="[statusConfig[complaint.status].class, 'text-xs font-medium px-2.5 py-1 rounded-full']">
                                    {{ statusConfig[complaint.status].label }}
                                </span>
                                <span class="text-xs text-gray-400">{{ complaint.building?.name }}</span>
                            </div>
                            <p class="font-medium text-gray-900 dark:text-white truncate">{{ complaint.title }}</p>
                            <div class="flex items-center gap-3 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ complaint.submitted_by?.name }}</span>
                                <span>{{ formatDate(complaint.created_at) }}</span>
                                <span v-if="complaint.location">📍 {{ complaint.location }}</span>
                                <span v-if="complaint.photos?.length">📎 {{ complaint.photos.length }} photo{{ complaint.photos.length > 1 ? 's' : '' }}</span>
                            </div>
                        </div>
                        <ChevronRight class="w-4 h-4 text-gray-400 shrink-0 mt-1" />
                    </div>
                </Link>

                <!-- Empty -->
                <div v-if="complaints.data.length === 0" class="text-center py-16">
                    <AlertTriangle class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                    <p class="text-gray-500 dark:text-gray-400">No complaints found.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="complaints.last_page > 1" class="flex justify-center gap-2 mt-6">
                <Link v-for="link in complaints.links" :key="link.label"
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
