<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Plus, FileText, ChevronRight } from 'lucide-vue-next'

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
    open:      { label: 'Open',      class: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400' },
    responded: { label: 'Responded', class: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400' },
    closed:    { label: 'Closed',    class: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' },
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}
</script>

<template>
    <ManageLayout>
        <Head title="Staff Queries" />

        <div class="p-6 lg:p-8">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Staff Queries</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">HR records — confidential</p>
                </div>
                <Link :href="route('manage.staff-queries.create')"
                      class="flex items-center gap-2 px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-sm font-medium hover:opacity-90 transition-all">
                    <Plus class="w-4 h-4" />
                    New Query
                </Link>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-3 gap-3 mb-6">
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 rounded-2xl p-4">
                    <p class="text-xs text-red-600 dark:text-red-400 mb-1">Open</p>
                    <p class="text-2xl font-semibold text-red-700 dark:text-red-400">{{ counts.open }}</p>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl p-4">
                    <p class="text-xs text-blue-600 dark:text-blue-400 mb-1">Responded</p>
                    <p class="text-2xl font-semibold text-blue-700 dark:text-blue-400">{{ counts.responded }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ queries.total }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mb-6">
                <select v-model="buildingId"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                    <option value="">All Buildings</option>
                    <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <select v-model="staffId"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                    <option value="">All Staff</option>
                    <option v-for="s in staffMembers" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <select v-model="type"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                    <option value="">All Types</option>
                    <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                </select>
                <select v-model="status"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                    <option value="">All Status</option>
                    <option value="open">Open</option>
                    <option value="responded">Responded</option>
                    <option value="closed">Closed</option>
                </select>
            </div>

            <!-- List -->
            <div class="space-y-3">
                <Link v-for="q in queries.data" :key="q.id"
                      :href="route('manage.staff-queries.show', q.id)"
                      class="block bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 hover:border-gray-300 dark:hover:border-gray-700 transition-all">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span :class="[statusConfig[q.status].class, 'text-xs font-medium px-2.5 py-1 rounded-full']">
                                    {{ statusConfig[q.status].label }}
                                </span>
                                <span class="text-xs text-gray-400">{{ types[q.type] }}</span>
                                <span class="text-xs text-gray-400">{{ q.building?.name }}</span>
                            </div>
                            <p class="font-medium text-gray-900 dark:text-white truncate">{{ q.subject }}</p>
                            <div class="flex flex-wrap gap-3 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                <span>Staff: <span class="font-medium text-gray-700 dark:text-gray-300">{{ q.staff?.name }}</span></span>
                                <span>By: {{ q.issued_by?.name }}</span>
                                <span>{{ formatDate(q.created_at) }}</span>
                            </div>
                        </div>
                        <ChevronRight class="w-4 h-4 text-gray-400 shrink-0 mt-1" />
                    </div>
                </Link>

                <div v-if="queries.data.length === 0" class="text-center py-16">
                    <FileText class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                    <p class="text-gray-500 dark:text-gray-400">No staff queries recorded.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="queries.last_page > 1" class="flex justify-center gap-2 mt-6">
                <Link v-for="link in queries.links" :key="link.label"
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
