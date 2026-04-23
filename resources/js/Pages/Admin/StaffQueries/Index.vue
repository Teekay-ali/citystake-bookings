<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Plus, FileText, ChevronRight } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    queries:      Object,
    buildings:    Array,
    staffMembers: Array,
    types:        Object,
    filters:      Object,
    counts:       Object,
})

const buildingId = ref(props.filters.building_id || '')
const status     = ref(props.filters.status || '')
const type       = ref(props.filters.type || '')
const staffId    = ref(props.filters.staff_id || '')

watch([buildingId, status, type, staffId], () => {
    router.get(route('manage.staff-queries.index'), {
        building_id: buildingId.value || undefined,
        status:      status.value || undefined,
        type:        type.value || undefined,
        staff_id:    staffId.value || undefined,
    }, { preserveState: true, replace: true })
})

const statusConfig = {
    open:      { label: 'Open',      cls: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800' },
    responded: { label: 'Responded', cls: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800' },
    closed:    { label: 'Closed',    cls: 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700' },
}

const pipeline = [
    { key: 'open',      label: 'Open'      },
    { key: 'responded', label: 'Responded' },
]

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

const hasActiveFilters = () => buildingId.value || status.value || type.value || staffId.value

const selectClass = "pl-3 pr-8 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
</script>

<template>
    <Head title="Staff Queries" />

    <div class="p-6 lg:p-8">

        <!-- ── Header ── -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Staff Queries</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">HR records — confidential</p>
            </div>
            <Link :href="route('manage.staff-queries.create')"
                  class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg transition-all">
                <Plus class="w-3.5 h-3.5" />
                New Query
            </Link>
        </div>

        <!-- ── Summary cards — clickable filters ── -->
        <div class="grid grid-cols-3 gap-3 mb-6">
            <button
                v-for="item in pipeline"
                :key="item.key"
                @click="status = status === item.key ? '' : item.key"
                :class="status === item.key
                    ? 'ring-2 ring-gray-900 dark:ring-white border-transparent'
                    : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
                class="bg-white dark:bg-gray-900 border rounded-xl p-4 text-left transition-all">
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">{{ item.label }}</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ counts[item.key] ?? 0 }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    {{ status === item.key ? 'Click to clear' : 'Click to filter' }}
                </p>
            </button>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">Total</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ queries.total }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">All queries</p>
            </div>
        </div>

        <!-- ── Filters ── -->
        <div class="flex flex-wrap gap-2 mb-6">
            <select v-model="buildingId" :class="selectClass" style="width: auto">
                <option value="">All buildings</option>
                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
            <select v-model="staffId" :class="selectClass" style="width: auto">
                <option value="">All staff</option>
                <option v-for="s in staffMembers" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
            <select v-model="type" :class="selectClass" style="width: auto">
                <option value="">All types</option>
                <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
            </select>
            <select v-model="status" :class="selectClass" style="width: auto">
                <option value="">All statuses</option>
                <option v-for="(cfg, key) in statusConfig" :key="key" :value="key">{{ cfg.label }}</option>
            </select>
            <button
                v-if="hasActiveFilters()"
                @click="buildingId = ''; status = ''; type = ''; staffId = ''"
                class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                Clear filters
            </button>
        </div>

        <!-- ── Query list ── -->
        <div class="space-y-2">
            <Link
                v-for="q in queries.data"
                :key="q.id"
                :href="route('manage.staff-queries.show', q.id)"
                class="flex items-start justify-between gap-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 hover:border-gray-300 dark:hover:border-gray-700 transition-all">

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                        <span :class="[statusConfig[q.status]?.cls, 'text-xs font-medium px-2 py-0.5 rounded-lg']">
                            {{ statusConfig[q.status]?.label }}
                        </span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ types[q.type] }}</span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ q.building?.name }}</span>
                    </div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate mb-1">{{ q.subject }}</p>
                    <div class="flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-gray-400 dark:text-gray-500">
                        <span>Staff: <span class="font-medium text-gray-600 dark:text-gray-300">{{ q.staff?.name }}</span></span>
                        <span>·</span>
                        <span>By: {{ q.issued_by?.name }}</span>
                        <span>·</span>
                        <span>{{ formatDate(q.created_at) }}</span>
                    </div>
                </div>
                <ChevronRight class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" />
            </Link>

            <!-- Empty state -->
            <div v-if="queries.data.length === 0" class="text-center py-20">
                <div class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-4">
                    <FileText class="w-6 h-6 text-gray-400" />
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">No staff queries recorded.</p>
            </div>
        </div>

        <!-- ── Pagination ── -->
        <div v-if="queries.last_page > 1" class="flex justify-center gap-1.5 mt-6">
            <Link
                v-for="link in queries.links"
                :key="link.label"
                :href="link.url || '#'"
                :class="[
                    'min-w-[36px] h-9 flex items-center justify-center px-3 rounded-lg text-sm transition-all',
                    link.active
                        ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium'
                        : 'border border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800',
                    !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                ]"
                v-html="link.label" />
        </div>

    </div>
</template>
